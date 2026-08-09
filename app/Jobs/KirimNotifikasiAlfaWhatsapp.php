<?php

namespace App\Jobs;

use App\Models\NotifikasiAlfaTerkirim;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
 */
class KirimNotifikasiAlfaWhatsapp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Maksimal percobaan kalau gagal (mis. WA API timeout/down sementara). */
    public int $tries = 3;

    /** Jeda antar percobaan ulang (detik): 15 detik, lalu 1 menit, lalu 5 menit. */
    public array $backoff = [15, 60, 300];

    /** Job dianggap gagal total kalau lebih dari ini (mencegah nyangkut lama). */
    public int $timeout = 30;

    public function __construct(
        public int $siswaId,
        public string $tanggal,   // format Y-m-d
        public ?string $mapel = null,
        public ?int $jamKe = null,
    ) {
        // Dikirim ke antrian 'notifikasi' (terpisah dari antrian 'default'
        // kalau nanti ada job lain), supaya bisa diatur prioritas/worker
        // sendiri tanpa saling mengganggu.
        $this->onQueue('notifikasi');
    }

    public function handle(): void
    {
        $siswa = Siswa::find($this->siswaId);

        // Tidak ada data siswa / nomor WA ortu kosong -> lewati diam-diam,
        // tandai gagal di tabel pelacak supaya tidak dicoba berulang kali.
        if (!$siswa || empty($siswa->no_wa_ortu)) {
            $this->tandaiStatus('gagal');
            return;
        }

        $nomor = $this->normalisasiNomor($siswa->no_wa_ortu);
        $pesan = $this->susunPesan($siswa);

        $response = Http::timeout(15)
            ->asForm()
            ->withHeaders(['Authorization' => config('services.fonnte.token')])
            ->post(config('services.fonnte.url'), [
                'target' => $nomor,
                'message' => $pesan,
            ]);

        if (!$response->successful()) {
            // Lempar exception supaya mekanisme retry Laravel jalan
            // ($tries & $backoff di atas), bukan gagal diam-diam.
            throw new \RuntimeException("Gagal kirim WA (HTTP {$response->status()}): {$response->body()}");
        }

        $this->tandaiStatus('terkirim');
    }

    /** Kalau job gagal terus sampai batas $tries habis. */
    public function failed(\Throwable $e): void
    {
        Log::warning("Notifikasi WA Alfa gagal untuk siswa #{$this->siswaId} tanggal {$this->tanggal}: {$e->getMessage()}");
        $this->tandaiStatus('gagal');
    }

    private function tandaiStatus(string $status): void
    {
        NotifikasiAlfaTerkirim::where('siswa_id', $this->siswaId)
            ->whereDate('tanggal', $this->tanggal)
            ->update(['status_kirim' => $status, 'dikirim_at' => $status === 'terkirim' ? now() : null]);
    }

    private function susunPesan(Siswa $siswa): string
    {
        $tanggalIndo = Carbon::parse($this->tanggal)->translatedFormat('d F Y');
        $sapaan = $siswa->nama_ortu ? "Bapak/Ibu {$siswa->nama_ortu}" : 'Bapak/Ibu orang tua/wali';

        $mapelInfo = $this->mapel ? " pada mata pelajaran *{$this->mapel}*" . ($this->jamKe ? " (jam ke-{$this->jamKe})" : '') : '';

        return "Assalamu'alaikum, {$sapaan}.\n\n"
            . "Kami dari SMP Negeri 3 Bumiayu menginformasikan bahwa pada tanggal *{$tanggalIndo}*, "
            . "ananda *{$siswa->nama}* tercatat *ALFA* (tidak hadir tanpa keterangan){$mapelInfo}.\n\n"
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
