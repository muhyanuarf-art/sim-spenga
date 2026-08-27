<?php

namespace App\Jobs;

use App\Models\NotifikasiAlfaTerkirim;
use App\Models\Siswa;
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
 * Job ini SENGAJA dijalankan lewat QUEUE (background worker), bukan
 * langsung di request guru menyimpan absensi. Alasannya:
 *
 * - Memanggil API WhatsApp itu "menunggu jaringan luar" (bisa 1-5 detik,
 *   bahkan lebih kalau providernya lambat/timeout). Kalau ini dijalankan
 *   langsung saat guru klik simpan, guru harus menunggu itu juga.
 * - Dengan queue, guru klik simpan -> selesai dalam hitungan milidetik
 *   (cuma nulis ke database) -> Job ini diproses TERPISAH oleh
 *   `php artisan queue:work` di belakang layar, kapan saja dia sempat.
 * - Kalau WA API gagal/down, otomatis dicoba ulang ($tries) TANPA
 *   mengganggu guru sama sekali & tanpa membuat request web jadi lambat.
 *
 * ADA 2 JENIS RETRY YANG DIPISAH (penting supaya sesuai aturan sekolah):
 * 1. Retry TEKNIS ($tries/$backoff Laravel di bawah) — untuk gangguan
 *    sesaat: device Fonnte terputus, timeout jaringan, kuota habis, dsb.
 *    Ini transparan, tidak dihitung sebagai "percobaan" versi sekolah.
 * 2. Retry BISNIS (kolom percobaan_ke di tabel notifikasi_alfa_terkirims,
 *    maks NotifikasiAlfaTerkirim::MAKS_PERCOBAAN = 2 kali total) — khusus
 *    saat Fonnte bilang nomornya sendiri yang bermasalah ("target invalid").
 *    Begini caranya: percobaan ke-1 gagal -> job baru didispatch lagi
 *    (percobaan ke-2) -> kalau gagal lagi, BERHENTI PERMANEN, tidak retry
 *    otomatis Laravel lagi untuk baris itu (kemungkinan nomor bukan WA aktif).
 */
class KirimNotifikasiAlfaWhatsapp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Maksimal percobaan TEKNIS kalau gagal (mis. WA API timeout/down sementara). */
    public int $tries = 3;

    /** Jeda antar percobaan ulang TEKNIS (detik): 15 detik, lalu 1 menit, lalu 5 menit. */
    public array $backoff = [15, 60, 300];

    /** Job dianggap gagal total kalau lebih dari ini (mencegah nyangkut lama). */
    public int $timeout = 30;

    /**
     * Alasan gagal dari Fonnte yang menandakan masalah ada di NOMOR itu
     * sendiri (bukan gangguan sesaat) — ini yang dihitung sebagai retry
     * BISNIS (maks 2x), bukan dilempar ke retry teknis Laravel.
     */
    private const ALASAN_MASALAH_NOMOR = ['target invalid', 'nomor', 'invalid'];

    public function __construct(
        public int $siswaId,
        public string $tanggal,   // format Y-m-d
        public ?string $mapel = null,
        public ?int $jamKe = null,
        // Diisi kalau Alfa-nya terjadi pada KEGIATAN SEKOLAH di luar jam
        // KBM (lomba, asesmen, classmeeting, pesantren Ramadan, dsb) yang
        // absensinya diisi wali kelas. Kalau terisi, pesan WA menyebut
        // nama kegiatan, bukan mata pelajaran.
        public ?string $kegiatan = null,
    ) {
        // Dikirim ke antrian 'notifikasi' (terpisah dari antrian 'default'
        // kalau nanti ada job lain), supaya bisa diatur prioritas/worker
        // sendiri tanpa saling mengganggu.
        $this->onQueue('notifikasi');
    }

    /**
     * Batasi laju kirim supaya aman meskipun Fonnte sendiri sudah cukup
     * longgar (~10 pesan/detik) — jaga-jaga kalau banyak siswa Alfa
     * sekaligus (misal alfa massal 1 kelas) dikirim ke queue bersamaan.
     */
    public function middleware(): array
    {
        return [new RateLimited('notifikasi-wa')];
    }

    public function handle(): void
    {
        $baris = NotifikasiAlfaTerkirim::where('siswa_id', $this->siswaId)
            ->whereDate('tanggal', $this->tanggal)
            ->first();

        // Baris mungkin sudah dihapus, atau statusnya sudah bukan 'pending'
        // (misal sudah keburu ditangani job lain, atau sudah gagal permanen
        // di percobaan sebelumnya) — tidak perlu dikirim lagi.
        if (! $baris || $baris->status_kirim !== 'pending') {
            return;
        }

        $siswa = Siswa::find($this->siswaId);

        if (! $siswa || empty($siswa->no_wa_ortu)) {
            $baris->update([
                'status_kirim' => 'gagal',
                'keterangan_gagal' => 'Nomor WhatsApp orang tua belum diisi di data siswa.',
            ]);
            return;
        }

        $nomor = $this->normalisasiNomor($siswa->no_wa_ortu);
        $pesan = $this->susunPesan($siswa);

        try {
            $response = Http::timeout(15)
                ->asForm()
                ->withHeaders(['Authorization' => config('services.fonnte.token')])
                ->post(config('services.fonnte.url'), [
                    'target' => $nomor,
                    'message' => $pesan,
                ]);
        } catch (ConnectionException $e) {
            // Gagal konek sama sekali (mis. tidak ada internet) -> lempar
            // supaya retry TEKNIS Laravel yang menangani ($tries/$backoff).
            throw $e;
        }

        // PENTING: Fonnte SERING mengembalikan HTTP 200 walaupun pesan
        // sebenarnya GAGAL diproses (mis. nomor tidak valid) — jadi
        // suksesnya request HTTP saja tidak cukup, harus dicek field
        // "status" di body JSON respons.
        $body = $response->json() ?? [];
        $suksesMenurutFonnte = $response->successful() && (($body['status'] ?? false) === true);

        if ($suksesMenurutFonnte) {
            $baris->update([
                'status_kirim' => 'terkirim',
                'dikirim_at' => now(),
                'keterangan_gagal' => null,
            ]);
            return;
        }

        $alasan = $body['reason'] ?? ('HTTP ' . $response->status() . ': ' . $response->body());
        $ituMasalahNomor = collect(self::ALASAN_MASALAH_NOMOR)
            ->contains(fn ($kata) => str_contains(strtolower($alasan), $kata));

        if ($ituMasalahNomor) {
            $this->tanganiGagalNomor($baris, $alasan);
            return;
        }

        // Kegagalan TEKNIS lain (device Fonnte disconnect, kuota habis,
        // format request salah, dsb) -> lempar supaya job di-retry
        // otomatis oleh Laravel sesuai $tries/$backoff di atas.
        throw new RuntimeException("Fonnte gagal kirim: {$alasan}");
    }

    /**
     * Kegagalan yang kemungkinan besar karena nomornya sendiri bermasalah.
     * TIDAK dilempar ulang ke Laravel (supaya tidak kena retry teknis) —
     * retry-nya manual, sesuai aturan sekolah: maks 2x percobaan total.
     */
    private function tanganiGagalNomor(NotifikasiAlfaTerkirim $baris, string $alasan): void
    {
        if ($baris->percobaan_ke < NotifikasiAlfaTerkirim::MAKS_PERCOBAAN) {
            $baris->update([
                'percobaan_ke' => $baris->percobaan_ke + 1,
                'keterangan_gagal' => $alasan,
                // status_kirim tetap 'pending' — job pengganti di bawah
                // akan memprosesnya lagi sebagai percobaan berikutnya.
            ]);

            // Beri jeda 2 menit sebelum coba lagi (jaga-jaga kalau
            // penyebabnya sesaat, mis. device Fonnte baru saja reconnect).
            static::dispatch($this->siswaId, $this->tanggal, $this->mapel, $this->jamKe, $this->kegiatan)
                ->delay(now()->addMinutes(2));

            return;
        }

        // Sudah mencapai batas MAKS_PERCOBAAN (2x) dan masih gagal juga
        // -> berhenti PERMANEN, kemungkinan besar nomor bukan WhatsApp aktif.
        $baris->update([
            'status_kirim' => 'gagal',
            'keterangan_gagal' => "{$alasan} (sudah dicoba {$baris->percobaan_ke}x, kemungkinan nomor bukan WhatsApp aktif)",
        ]);
    }

    /** Kalau job gagal terus sampai batas $tries TEKNIS habis. */
    public function failed(?Throwable $e): void
    {
        Log::warning("Notifikasi WA Alfa gagal (teknis) untuk siswa #{$this->siswaId} tanggal {$this->tanggal}: " . $e?->getMessage());

        $baris = NotifikasiAlfaTerkirim::where('siswa_id', $this->siswaId)
            ->whereDate('tanggal', $this->tanggal)
            ->first();

        if ($baris && $baris->status_kirim === 'pending') {
            $baris->update([
                'status_kirim' => 'gagal',
                'keterangan_gagal' => 'Gagal terhubung ke Fonnte setelah beberapa kali percobaan teknis: ' . $e?->getMessage(),
            ]);
        }
    }

    private function susunPesan(Siswa $siswa): string
    {
        $tanggalIndo = Carbon::parse($this->tanggal)->translatedFormat('d F Y');
        $sapaan = $siswa->nama_ortu ? "Bapak/Ibu {$siswa->nama_ortu}" : 'Bapak/Ibu orang tua/wali';

        // Hari kegiatan sekolah (di luar KBM) menyebut nama kegiatannya,
        // hari biasa menyebut mata pelajaran + jam ke berapa.
        if ($this->kegiatan) {
            $mapelInfo = "Kegiatan : *{$this->kegiatan}*";
        } elseif ($this->mapel) {
            $mapelInfo = "Mata pelajaran : *{$this->mapel}*" . ($this->jamKe ? " (jam ke-{$this->jamKe})" : '');
        } else {
            $mapelInfo = '';
        }

        return "Assalamu'alaikum, {$sapaan}.\n\n"
            . "Kami dari SMP Negeri 3 Bumiayu menginformasikan bahwa pada :\n"
        . "Tanggal : {$tanggalIndo}\n"
        . "Nama : *{$siswa->nama}*\n"
        . "Kehadiran : *ALFA (tidak hadir tanpa keterangan)*\n"
        . "{$mapelInfo}\n\n"
        . "Mohon konfirmasi ke wali kelas/pihak sekolah apabila ada keterangan. Terima kasih.\n\n"
        . "_Pesan ini dikirim otomatis oleh sistem sekolah, mohon tidak membalas ke nomor ini._";
    }

    /**
     * Rapikan nomor WA ke format 62xxxx (tanpa spasi/strip/tanda +, tanpa
     * awalan 0). Sesuaikan lagi kalau provider WA Anda butuh format lain.
     */
    private function normalisasiNomor(string $nomor): string
    {
        $nomor = preg_replace('/[^0-9]/', '', $nomor);
        if (str_starts_with($nomor, '0')) {
            $nomor = '62' . substr($nomor, 1);
        }
        return $nomor;
    }
}
