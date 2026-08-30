<?php

namespace App\Jobs;

use App\Models\JurnalMengajarSlot;
use App\Models\PengaturanNotifikasiGuru;
use App\Models\PengaturanSekolah;
use App\Models\PengingatJurnal;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * PENGINGAT JURNAL & ABSENSI KE NOMOR GURU.
 *
 * =====================================================================
 * HUBUNGANNYA DENGAN NOTIFIKASI ALFA
 * =====================================================================
 * Job ini SENGAJA dibuat terpisah dari KirimNotifikasiAlfaWhatsapp dan
 * tidak mewarisi apa pun darinya, walaupun alur teknisnya mirip. Yang
 * membedakan bukan cara kerjanya, melainkan SIAPA PENGIRIMNYA:
 *
 *   Alfa     -> perangkat 1 Fonnte (nomor sekolah)      -> ke orang tua
 *   Pengingat-> perangkat 2 Fonnte (nomor kepala sekolah)-> ke guru
 *
 * Token perangkat 1 tetap dibaca dari config('services.fonnte.token') dan
 * TIDAK tersentuh berkas ini. Token perangkat 2 diambil dari Pengaturan.
 * Keduanya juga memakai penahan laju (rate limiter) yang berbeda supaya
 * antrian pengingat guru tidak pernah memperlambat pemberitahuan Alfa
 * kepada orang tua, dan sebaliknya.
 *
 * =====================================================================
 * DUA JENIS KEGAGALAN, SAMA SEPERTI NOTIFIKASI ALFA
 * =====================================================================
 * 1. Teknis  — jaringan mati, Fonnte timeout, perangkat terputus.
 *    Ditangani $tries/$backoff bawaan Laravel, tidak dihitung sebagai
 *    percobaan versi sekolah.
 * 2. Nomor   — Fonnte bilang nomor tujuannya bermasalah. Dicoba paling
 *    banyak PengingatJurnal::MAKS_PERCOBAAN kali, lalu berhenti permanen
 *    supaya tidak mengejar nomor yang memang bukan WhatsApp aktif.
 */
class KirimPengingatJurnalWhatsapp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [15, 60, 300];

    public int $timeout = 30;

    private const ALASAN_MASALAH_NOMOR = ['target invalid', 'nomor', 'invalid'];

    public function __construct(public int $pengingatId)
    {
        // Antrian sendiri, terpisah dari 'notifikasi' milik Alfa.
        $this->onQueue('pengingat-guru');
    }

    public function middleware(): array
    {
        return [new RateLimited('pengingat-guru')];
    }

    public function handle(): void
    {
        $baris = PengingatJurnal::with(['guru', 'kelas', 'mapel', 'jadwal.jamPelajaran'])
            ->find($this->pengingatId);

        if (! $baris || $baris->status_kirim !== 'pending') {
            return;
        }

        // Guru mungkin baru saja mengisi jurnalnya justru saat pesan ini
        // menunggu di antrian. Memarahi orang yang sudah mengerjakan
        // tugasnya adalah cara tercepat membuat fitur ini dibenci, jadi
        // diperiksa sekali lagi tepat sebelum kirim.
        if ($this->sudahDiisi($baris)) {
            $baris->update([
                'status_kirim' => 'dilewati',
                'keterangan_gagal' => 'Jurnal sudah diisi sebelum pesan sempat dikirim.',
            ]);

            return;
        }

        $pengaturan = PengaturanNotifikasiGuru::current();
        $token = $pengaturan->token();

        if (! $pengaturan->aktif) {
            $baris->update([
                'status_kirim' => 'dilewati',
                'keterangan_gagal' => 'Pengingat dimatikan admin sebelum pesan dikirim.',
            ]);

            return;
        }

        if (! $token) {
            $baris->update([
                'status_kirim' => 'gagal',
                'keterangan_gagal' => 'Token perangkat WhatsApp kepala sekolah belum diisi di Pengaturan.',
            ]);

            return;
        }

        $nomorGuru = $this->normalisasiNomor((string) $baris->guru?->no_hp);

        if ($nomorGuru === '') {
            $baris->update([
                'status_kirim' => 'gagal',
                'keterangan_gagal' => 'Nomor WhatsApp guru belum diisi di menu Kelola Pengguna.',
            ]);

            return;
        }

        $pesan = self::susunPesan($baris, $pengaturan);

        try {
            $response = Http::timeout(15)
                ->asForm()
                ->withHeaders(['Authorization' => $token])
                ->post(config('services.fonnte.url'), [
                    'target' => $nomorGuru,
                    'message' => $pesan,
                ]);
        } catch (ConnectionException $e) {
            throw $e;
        }

        // Fonnte kerap membalas HTTP 200 walau pesannya sebenarnya gagal,
        // jadi field "status" di badan JSON yang menentukan — sama seperti
        // pada notifikasi Alfa.
        $body = $response->json() ?? [];

        if ($response->successful() && (($body['status'] ?? false) === true)) {
            $baris->update([
                'status_kirim' => 'terkirim',
                'dikirim_at' => now(),
                'keterangan_gagal' => null,
            ]);

            return;
        }

        $alasan = $body['reason'] ?? ('HTTP '.$response->status().': '.$response->body());

        $masalahNomor = collect(self::ALASAN_MASALAH_NOMOR)
            ->contains(fn ($kata) => str_contains(strtolower($alasan), $kata));

        if ($masalahNomor) {
            $this->tanganiGagalNomor($baris, $alasan);

            return;
        }

        throw new RuntimeException("Fonnte gagal kirim pengingat: {$alasan}");
    }

    /**
     * Apakah sesi ini sudah terisi? Cukup memeriksa jam PERTAMA sesi:
     * satu sesi selalu disimpan sekaligus oleh MengajarController, jadi
     * jam pertama terisi berarti seluruh sesinya terisi.
     */
    private function sudahDiisi(PengingatJurnal $baris): bool
    {
        return JurnalMengajarSlot::where('jadwal_pelajaran_id', $baris->jadwal_pelajaran_id)
            ->whereDate('tanggal', $baris->tanggal)
            ->exists();
    }

    private function tanganiGagalNomor(PengingatJurnal $baris, string $alasan): void
    {
        if ($baris->percobaan_ke < PengingatJurnal::MAKS_PERCOBAAN) {
            $baris->update([
                'percobaan_ke' => $baris->percobaan_ke + 1,
                'keterangan_gagal' => $alasan,
            ]);

            static::dispatch($baris->id)->delay(now()->addMinutes(2));

            return;
        }

        $baris->update([
            'status_kirim' => 'gagal',
            'keterangan_gagal' => "{$alasan} (sudah dicoba {$baris->percobaan_ke}x, kemungkinan nomor guru bukan WhatsApp aktif)",
        ]);
    }

    public function failed(?Throwable $e): void
    {
        Log::warning("Pengingat jurnal #{$this->pengingatId} gagal (teknis): ".$e?->getMessage());

        $baris = PengingatJurnal::find($this->pengingatId);

        if ($baris && $baris->status_kirim === 'pending') {
            $baris->update([
                'status_kirim' => 'gagal',
                'keterangan_gagal' => 'Gagal terhubung ke Fonnte setelah beberapa kali percobaan teknis: '.$e?->getMessage(),
            ]);
        }
    }

    /**
     * Susun pesan dari naskah yang diatur admin.
     *
     * Dibuat `public static` supaya halaman Pengaturan bisa menampilkan
     * PRATINJAU pesan memakai fungsi yang sama persis dengan yang benar-
     * benar dikirim — pratinjau yang disusun terpisah cepat sekali menjadi
     * tidak sesuai kenyataan begitu naskahnya berubah.
     */
    public static function susunPesan(PengingatJurnal $baris, ?PengaturanNotifikasiGuru $pengaturan = null): string
    {
        $pengaturan ??= PengaturanNotifikasiGuru::current();
        $sekolah = PengaturanSekolah::current();

        $jamAwal = $baris->jadwal?->jamPelajaran;
        $waktu = $jamAwal
            ? Carbon::parse($jamAwal->jam_mulai)->format('H.i').'-'.Carbon::parse($baris->jadwal->jamPelajaran->jam_selesai)->format('H.i')
            : '-';

        $gantian = [
            '{guru}' => $baris->guru?->name ?? 'Bapak/Ibu Guru',
            '{tanggal}' => Carbon::parse($baris->tanggal)->translatedFormat('l, d F Y'),
            '{kelas}' => $baris->kelas?->nama_kelas ?? '-',
            '{mapel}' => $baris->mapel?->nama_mapel ?? '-',
            '{jam}' => $baris->labelJam(),
            '{waktu}' => $waktu,
            '{sekolah}' => $sekolah->nama_sekolah ?: 'sekolah',
            '{aplikasi}' => rtrim((string) config('app.url'), '/'),
        ];

        return strtr($pengaturan->template(), $gantian);
    }

    /** Rapikan nomor ke format 62xxxx, sama seperti notifikasi Alfa. */
    private function normalisasiNomor(string $nomor): string
    {
        $nomor = preg_replace('/[^0-9]/', '', $nomor) ?? '';

        if ($nomor === '') {
            return '';
        }

        if (str_starts_with($nomor, '0')) {
            $nomor = '62'.substr($nomor, 1);
        }

        return $nomor;
    }
}
