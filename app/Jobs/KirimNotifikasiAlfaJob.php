<?php

namespace App\Jobs;

use App\Models\NotifikasiWa;
use App\Services\WhatsAppCloudService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class KirimNotifikasiAlfaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * tries/backoff ini HANYA untuk kegagalan TEKNIS (timeout, koneksi
     * putus, dsb) pada 1x percobaan kirim. Ini beda dengan "percobaan_ke"
     * di tabel notifikasi_was, yang menghitung retry BISNIS (nomor WA
     * gagal terkirim menurut Meta) — maksimal 2x sesuai aturan sekolah.
     */
    public int $tries = 3;

    public array $backoff = [15, 60, 180];

    public function __construct(public int $notifikasiWaId)
    {
    }

    /**
     * Batasi laju kirim supaya tidak melanggar rate limit WhatsApp Cloud
     * API kalau banyak siswa Alfa sekaligus (misal alfa massal 1 kelas).
     */
    public function middleware(): array
    {
        return [new RateLimited('whatsapp-kirim')];
    }

    public function handle(WhatsAppCloudService $wa): void
    {
        $notif = NotifikasiWa::find($this->notifikasiWaId);

        // Record mungkin sudah dihapus, atau statusnya sudah berubah dari
        // "menunggu" (misal sudah keburu ditangani job lain) — tidak perlu
        // dikirim lagi.
        if (! $notif || $notif->status !== 'menunggu') {
            return;
        }

        if (! $notif->no_hp_tujuan) {
            $notif->update([
                'status' => 'gagal',
                'keterangan_gagal' => 'Nomor WhatsApp orang tua belum diisi di data siswa.',
                'gagal_at' => now(),
            ]);
            return;
        }

        try {
            $waMessageId = $wa->kirimTemplate(
                $notif->no_hp_tujuan,
                'info_alfa_siswa',
                [$notif->siswa->nama, $notif->kelas->nama_kelas, $notif->tanggal->translatedFormat('d F Y')]
            );

            $notif->update([
                'status' => 'terkirim',
                'wa_message_id' => $waMessageId,
                'terkirim_at' => now(),
            ]);
        } catch (RuntimeException $e) {
            // Kegagalan BISNIS (nomor tidak valid/bukan WA dsb) — langsung
            // tandai gagal, JANGAN dilempar ulang supaya Laravel tidak
            // retry otomatis. Retry untuk kasus ini ditangani lewat
            // percobaan_ke (lihat NotifikasiAlfaDispatcher::cobaLagi()).
            $notif->update([
                'status' => 'gagal',
                'keterangan_gagal' => $e->getMessage(),
                'gagal_at' => now(),
            ]);
        } catch (ConnectionException|RequestException $e) {
            // Kegagalan TEKNIS — lempar ulang supaya job di-retry otomatis
            // oleh Laravel sesuai $tries/$backoff di atas.
            throw $e;
        }
    }

    /**
     * Dipanggil kalau job gagal permanen setelah seluruh $tries teknis habis.
     */
    public function failed(?Throwable $exception): void
    {
        $notif = NotifikasiWa::find($this->notifikasiWaId);

        if ($notif && $notif->status === 'menunggu') {
            $notif->update([
                'status' => 'gagal',
                'keterangan_gagal' => 'Gagal terhubung ke WhatsApp Cloud API setelah beberapa kali percobaan teknis.',
                'gagal_at' => now(),
            ]);
        }
    }
}
