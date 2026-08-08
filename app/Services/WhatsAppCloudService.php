<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsAppCloudService
{
    /**
     * Kirim pesan template WhatsApp lewat Meta Cloud API.
     *
     * Sengaja pakai TEMPLATE message (bukan pesan teks bebas), karena Cloud
     * API hanya mengizinkan kirim pesan teks bebas dalam window 24 jam
     * setelah orang tua membalas chat — kalau tidak, harus pakai template
     * yang sudah disetujui Meta. Untuk notifikasi 1 arah seperti ini,
     * template adalah satu-satunya cara yang selalu bisa terkirim.
     *
     * @return string  wa_message_id (wamid...) dari response Meta
     *
     * @throws RuntimeException  kalau nomor tidak valid / bukan format WA
     *                           (business failure, TIDAK perlu retry job)
     * @throws \Illuminate\Http\Client\ConnectionException  kalau gagal
     *                           teknis (timeout dsb, job akan retry otomatis)
     */
    public function kirimTemplate(string $noHpTujuan, string $namaTemplate, array $parameterBody = []): string
    {
        $phoneId = config('services.whatsapp.phone_id');
        $token = config('services.whatsapp.token');

        $response = Http::withToken($token)
            ->timeout(15)
            ->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $noHpTujuan,
                'type' => 'template',
                'template' => [
                    'name' => $namaTemplate,
                    'language' => ['code' => 'id'],
                    'components' => empty($parameterBody) ? [] : [[
                        'type' => 'body',
                        'parameters' => array_map(
                            fn ($teks) => ['type' => 'text', 'text' => (string) $teks],
                            $parameterBody
                        ),
                    ]],
                ],
            ]);

        if ($response->successful()) {
            return $response->json('messages.0.id');
        }

        // Kode error Meta untuk nomor tidak valid / tidak terdaftar di WhatsApp,
        // dsb — ini kegagalan BISNIS (nomor bermasalah), bukan kegagalan
        // teknis, jadi TIDAK boleh masuk retry otomatis Laravel.
        $kodeNonRetryable = [131026, 131047, 131051, 100];
        $kodeError = $response->json('error.code');

        if (in_array($kodeError, $kodeNonRetryable, true)) {
            throw new RuntimeException(
                $response->json('error.message', 'Nomor WhatsApp tidak valid.'),
                previous: null
            );
        }

        // Error lain (rate limit, server Meta bermasalah, dsb) dianggap
        // gagal teknis supaya job di-retry otomatis oleh Laravel.
        throw new \Illuminate\Http\Client\RequestException($response);
    }
}
