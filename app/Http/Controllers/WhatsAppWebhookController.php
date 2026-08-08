<?php

namespace App\Http\Controllers;

use App\Models\NotifikasiWa;
use App\Support\NotifikasiAlfaDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Verifikasi webhook oleh Meta saat pertama kali didaftarkan
     * (GET /webhook/whatsapp?hub.mode=subscribe&hub.verify_token=...&hub.challenge=...).
     */
    public function verifikasi(Request $request)
    {
        $token = $request->get('hub_verify_token', $request->get('hub.verify_token'));
        $challenge = $request->get('hub_challenge', $request->get('hub.challenge'));

        if ($request->get('hub_mode', $request->get('hub.mode')) === 'subscribe'
            && $token === config('services.whatsapp.webhook_verify_token')) {
            return response($challenge, 200);
        }

        return response('Verifikasi gagal.', 403);
    }

    /**
     * Terima callback status pesan (sent/delivered/read/failed) dari Meta.
     * Endpoint ini SENGAJA ringan — cuma update 1 baris per callback, tidak
     * ada proses berat, supaya aman dipanggil langsung tanpa queue.
     */
    public function terimaStatus(Request $request, NotifikasiAlfaDispatcher $dispatcher)
    {
        $statusList = $request->input('entry.0.changes.0.value.statuses', []);

        foreach ($statusList as $item) {
            $waMessageId = $item['id'] ?? null;
            $statusMeta = $item['status'] ?? null; // sent|delivered|read|failed

            if (! $waMessageId || ! $statusMeta) {
                continue;
            }

            $notif = NotifikasiWa::where('wa_message_id', $waMessageId)->first();
            if (! $notif) {
                continue;
            }

            match ($statusMeta) {
                'sent' => $notif->update(['status' => 'terkirim', 'terkirim_at' => $notif->terkirim_at ?? now()]),
                'delivered' => $notif->update(['status' => 'diterima', 'diterima_at' => now()]),
                'read' => $notif->update(['status' => 'dibaca', 'dibaca_at' => now()]),
                'failed' => $this->tandaiGagal($notif, $item, $dispatcher),
                default => Log::info("Status WA tidak dikenal: {$statusMeta}"),
            };
        }

        // Meta mengharuskan respons 200 secepatnya, apa pun isinya.
        return response('OK', 200);
    }

    private function tandaiGagal(NotifikasiWa $notif, array $item, NotifikasiAlfaDispatcher $dispatcher): void
    {
        $pesanError = $item['errors'][0]['title'] ?? 'Pesan gagal terkirim (kemungkinan nomor bukan WhatsApp).';

        $notif->update([
            'status' => 'gagal',
            'keterangan_gagal' => $pesanError,
            'gagal_at' => now(),
        ]);

        // Coba kirim ulang, tapi dispatcher akan menolak sendiri kalau
        // percobaan_ke sudah mencapai batas maksimal (2x).
        $dispatcher->cobaLagiJikaBelumMentok($notif->fresh());
    }
}
