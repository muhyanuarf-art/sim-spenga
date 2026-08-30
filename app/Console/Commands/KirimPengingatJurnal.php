<?php

namespace App\Console\Commands;

use App\Jobs\KirimPengingatJurnalWhatsapp;
use App\Models\JadwalPelajaran;
use App\Models\KegiatanSekolah;
use App\Models\PengaturanNotifikasiGuru;
use App\Models\PengingatJurnal;
use App\Models\TahunAjaran;
use App\Support\SesiMengajarGrouper;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;

/**
 * MENCARI SESI MENGAJAR YANG JURNAL & ABSENSINYA BELUM DIISI.
 *
 * =====================================================================
 * KAPAN SEBUAH SESI DIANGGAP TERLAMBAT
 * =====================================================================
 * Sesi dianggap terlambat bila jam pelajaran TERAKHIRNYA sudah berakhir
 * lebih dari `jeda_menit` yang lalu (bawaan 30 menit) dan belum ada satu
 * pun baris `jurnal_mengajar_slots` untuk jadwal itu pada tanggal ini.
 *
 * Yang dihitung adalah jam SELESAI sesi, bukan jam mulai. Guru yang
 * mengajar tiga jam berturut-turut baru diingatkan 30 menit setelah jam
 * ketiganya usai — bukan di tengah-tengah ia masih mengajar.
 *
 * =====================================================================
 * KENAPA SATU PESAN PER SESI, BUKAN PER JAM
 * =====================================================================
 * Pengelompokan sesi memakai SesiMengajarGrouper — kelas yang sama dipakai
 * halaman "Absensi & Jurnal Mengajar" yang dilihat guru. Dengan begitu
 * yang disebut "satu sesi" di pesan WhatsApp sama persis dengan satu baris
 * yang harus guru klik di layar, dan guru yang mengajar 3 jam berturut-
 * turut menerima satu pesan, bukan tiga.
 *
 * =====================================================================
 * AMAN DIJALANKAN BERULANG
 * =====================================================================
 * Perintah ini dirancang untuk dipanggil penjadwal tiap 5 menit. Yang
 * mencegah pesan ganda adalah indeks unik (jadwal_pelajaran_id, tanggal)
 * di tabel `pengingat_jurnals` — bukan pengecekan di dalam kode ini —
 * sehingga dua proses yang kebetulan berjalan bersamaan pun tidak bisa
 * menghasilkan dua pesan untuk sesi yang sama.
 */
class KirimPengingatJurnal extends Command
{
    protected $signature = 'pengingat:jurnal
        {--tanggal= : Tanggal yang diperiksa (Y-m-d). Bawaannya hari ini}
        {--lihat : Tampilkan temuannya saja, tidak mengirim apa pun}
        {--abaikan-jam-kirim : Lewati pemeriksaan jendela jam kirim (dipakai saat menguji)}';

    protected $description = 'Kirim pengingat WhatsApp ke guru yang belum mengisi jurnal & absensi';

    private const HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 0 => 'Minggu'];

    public function handle(): int
    {
        $pengaturan = PengaturanNotifikasiGuru::current();
        $lihatSaja = $this->option('lihat');

        if (! $lihatSaja && ! $pengaturan->aktif) {
            $this->line('Pengingat sedang dimatikan di Pengaturan — tidak ada yang dikerjakan.');

            return self::SUCCESS;
        }

        $tanggal = $this->option('tanggal')
            ? Carbon::parse($this->option('tanggal'))->startOfDay()
            : Carbon::today();

        $hari = self::HARI[$tanggal->dayOfWeek] ?? 'Senin';

        if ($hari === 'Minggu') {
            $this->line('Hari Minggu — tidak ada jadwal pelajaran.');

            return self::SUCCESS;
        }

        // PENGINGAT HANYA UNTUK HARI INI.
        //
        // `--tanggal` memang berguna untuk memeriksa hari lampau, tetapi
        // hanya untuk DILIHAT. Mengirim sungguhan pengingat tentang hari
        // yang sudah lewat tidak ada gunanya bagi siapa pun: jurnalnya
        // sudah tidak bisa diisi tepat waktu lagi, dan pesannya tiba
        // sebagai teguran atas sesuatu yang sudah berlalu — bisa-bisa di
        // hari libur.
        //
        // Job pengiriman juga memeriksa hal yang sama sekali lagi tepat
        // sebelum pesan keluar (lihat KirimPengingatJurnalWhatsapp::
        // kadaluwarsa()), karena antrian bisa tertahan berjam-jam. Yang di
        // sini mencegah barisnya dibuat sama sekali; yang di sana menjaga
        // baris yang sudah terlanjur dibuat.
        if (! $lihatSaja && ! $tanggal->isToday()) {
            $this->warn('Tanggal '.$tanggal->translatedFormat('l, d F Y').' bukan hari ini.');
            $this->line('  Pengingat hanya dikirim pada hari mengajarnya. Tambahkan --lihat');
            $this->line('  untuk memeriksa hari itu tanpa mengirim apa pun.');

            return self::SUCCESS;
        }

        // Jendela jam kirim hanya berlaku untuk pengiriman sungguhan pada
        // HARI INI. Memeriksa tanggal lampau (mis. saat menguji) tidak
        // masuk akal dibatasi jam.
        if (! $lihatSaja && ! $this->option('abaikan-jam-kirim')
            && $tanggal->isToday() && ! $pengaturan->didalamJamKirim()) {
            $this->line('Di luar jendela jam kirim ('
                .substr((string) $pengaturan->jam_mulai_kirim, 0, 5).'-'
                .substr((string) $pengaturan->jam_akhir_kirim, 0, 5).') — ditunda.');

            return self::SUCCESS;
        }

        $periode = TahunAjaran::aktif();

        if (! $periode) {
            $this->warn('Belum ada periode/tahun ajaran yang aktif.');

            return self::SUCCESS;
        }

        $batas = $tanggal->isToday()
            ? now()->subMinutes($pengaturan->jeda_menit)
            // Untuk tanggal yang sudah lewat, seluruh jam pelajarannya
            // sudah pasti berakhir — jadi batasnya akhir hari itu.
            : $tanggal->copy()->endOfDay();

        $terlambat = $this->cariSesiTerlambat($periode, $hari, $tanggal, $batas);

        if ($terlambat->isEmpty()) {
            // Kalau seluruh sesi tersaring habis oleh Kegiatan Sekolah,
            // tabel rinciannya tidak pernah sempat tampil — jadi nama
            // kegiatan & kelasnya disebutkan di sini. Tanpa itu Admin cuma
            // membaca "tidak ada apa-apa" dan tidak tahu apa sebabnya.
            if (KegiatanSekolah::kelasIdBerkegiatanPada($tanggal) !== []) {
                $this->info('Tidak ada sesi yang perlu diingatkan.');
                $this->tampilkanKegiatan($tanggal);
            } else {
                $this->info('Tidak ada sesi yang terlambat diisi. Semua jurnal & absensi sudah terisi.');
            }

            return self::SUCCESS;
        }

        $this->tampilkan($terlambat, $tanggal, $pengaturan);

        if ($lihatSaja) {
            $this->newLine();
            $this->info('Mode lihat saja — tidak ada pesan yang dikirim.');

            return self::SUCCESS;
        }

        if (! $pengaturan->token()) {
            $this->error('Token perangkat WhatsApp kepala sekolah belum diisi di Pengaturan — tidak ada yang dikirim.');

            return self::FAILURE;
        }

        $dikirim = 0;
        $dilewati = 0;

        foreach ($terlambat as $sesi) {
            $baris = $this->catat($sesi, $periode, $tanggal);

            if ($baris === null) {
                // Indeks unik menolak: sesi ini sudah pernah diingatkan.
                $dilewati++;

                continue;
            }

            KirimPengingatJurnalWhatsapp::dispatch($baris->id);
            $dikirim++;
        }

        $this->newLine();
        $this->info("{$dikirim} pengingat dimasukkan ke antrian."
            .($dilewati > 0 ? " {$dilewati} sesi dilewati karena sudah pernah diingatkan." : ''));

        return self::SUCCESS;
    }

    /**
     * Sebutkan kelas mana yang dikecualikan hari itu karena ada Kegiatan
     * Sekolah, beserta nama kegiatannya.
     *
     * Dipakai di DUA tempat — saat ada temuan (di atas tabel) dan saat
     * temuannya nihil — karena keduanya sama-sama membingungkan bila tidak
     * dijelaskan: Admin yang tahu persis ada kelas belum mengisi jurnal
     * akan mengira pengingatnya rusak.
     */
    private function tampilkanKegiatan(Carbon $tanggal): void
    {
        $kelasId = KegiatanSekolah::kelasIdBerkegiatanPada($tanggal);

        if ($kelasId === []) {
            return;
        }

        $namaKelas = \App\Models\Kelas::whereIn('id', $kelasId)
            ->orderBy('nama_kelas')->pluck('nama_kelas')->implode(', ');
        $namaKegiatan = KegiatanSekolah::berlangsungPadaTanggal($tanggal->toDateString())
            ->pluck('nama')->implode(', ');

        $this->newLine();
        $this->line('Dikecualikan : '.$namaKelas);
        $this->line('               ada Kegiatan Sekolah ('.$namaKegiatan.') — kehadirannya');
        $this->line('               diisi wali kelas, bukan guru mata pelajaran.');
    }

    /**
     * Semua sesi pada $hari yang jam terakhirnya sudah lewat $batas dan
     * belum punya jurnal.
     *
     * @return \Illuminate\Support\Collection<int, array>
     */
    private function cariSesiTerlambat(TahunAjaran $periode, string $hari, Carbon $tanggal, Carbon $batas)
    {
        $jadwal = JadwalPelajaran::with(['kelas', 'mapel', 'jamPelajaran', 'guru'])
            ->where('tahun_ajaran_id', $periode->id)
            ->where('hari', $hari)
            // Jam pelajaran yang dinonaktifkan tidak lagi dipakai mengajar,
            // jadi tidak ada yang perlu diingatkan untuknya.
            ->whereHas('jamPelajaran', fn ($q) => $q->where('is_active', true))
            ->get();

        if ($jadwal->isEmpty()) {
            return collect();
        }

        // Dikelompokkan PER GURU lebih dulu: SesiMengajarGrouper menggabung
        // jam berurutan pada kelas & mapel yang sama, dan tanpa pemisahan
        // ini jadwal dua guru berbeda yang kebetulan berurutan pada kelas &
        // mapel yang sama bisa tergabung menjadi satu sesi milik siapa pun.
        $sesi = $jadwal->groupBy('guru_id')
            ->flatMap(fn ($milikGuru) => SesiMengajarGrouper::kelompokkan($milikGuru));

        $sesi = SesiMengajarGrouper::tandaiSudahDiisi($sesi, $jadwal, $tanggal->toDateString());

        // KELAS YANG SEDANG BERKEGIATAN DIKELUARKAN.
        //
        // Pada hari kegiatan sekolah (lomba, classmeeting, pesantren, tryout)
        // KBM biasa tidak berjalan: yang mengisi kehadiran adalah WALI KELAS
        // lewat Absensi Kegiatan, bukan guru mapel lewat jurnal mengajar.
        // Jadi jurnal yang kosong pada hari itu memang sudah semestinya —
        // menagihnya berarti menyalahkan guru atas sesuatu yang bukan
        // tugasnya hari itu.
        //
        // Cakupan kegiatan dihormati apa adanya: kegiatan khusus kelas 7
        // tidak membebaskan kelas 8 dan 9.
        $kelasBerkegiatan = KegiatanSekolah::kelasIdBerkegiatanPada($tanggal);

        return $sesi
            ->filter(fn ($s) => ! $s['sudah_diisi'])
            ->filter(fn ($s) => ! in_array($s['slots']->first()->kelas_id, $kelasBerkegiatan, true))
            ->filter(function ($s) use ($tanggal, $batas) {
                // Selesainya jam TERAKHIR pada sesi ini.
                $selesai = $tanggal->copy()->setTimeFromTimeString($s['jam_akhir']->jam_selesai);

                return $selesai->lte($batas);
            })
            // Guru yang barisnya sudah dihapus tidak bisa dihubungi.
            ->filter(fn ($s) => $s['slots']->first()->guru !== null)
            ->sortBy(fn ($s) => $s['jam_awal']->jam_ke)
            ->values();
    }

    /**
     * Catat sesi ini sebagai "sudah diingatkan". Mengembalikan null bila
     * ternyata sudah pernah dicatat — inilah pengaman anti-kirim-ganda,
     * dan sengaja mengandalkan indeks unik database, bukan pengecekan
     * terpisah yang bisa kalah cepat oleh proses lain.
     */
    private function catat(array $sesi, TahunAjaran $periode, Carbon $tanggal): ?PengingatJurnal
    {
        $pertama = $sesi['slots']->first();

        try {
            return PengingatJurnal::create([
                'guru_id' => $pertama->guru_id,
                'jadwal_pelajaran_id' => $pertama->id,
                'tahun_ajaran_id' => $periode->id,
                'kelas_id' => $pertama->kelas_id,
                'mata_pelajaran_id' => $pertama->mata_pelajaran_id,
                'tanggal' => $tanggal->toDateString(),
                'jam_ke_awal' => $sesi['jam_awal']->jam_ke,
                'jam_ke_akhir' => $sesi['jam_akhir']->jam_ke,
                'status_kirim' => 'pending',
                'percobaan_ke' => 1,
            ]);
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                return null;
            }

            throw $e;
        }
    }

    private function tampilkan($terlambat, Carbon $tanggal, PengaturanNotifikasiGuru $pengaturan): void
    {
        $this->newLine();
        $this->line('Tanggal   : '.$tanggal->translatedFormat('l, d F Y'));
        $this->line('Jeda      : '.$pengaturan->jeda_menit.' menit setelah jam pelajaran selesai');
        $this->line('Ditemukan : '.$terlambat->count().' sesi belum terisi');

        $this->tampilkanKegiatan($tanggal);

        $this->newLine();

        $this->table(
            ['Guru', 'Kelas', 'Mata Pelajaran', 'Jam ke', 'Selesai', 'No. WhatsApp'],
            $terlambat->map(fn ($s) => [
                $s['slots']->first()->guru?->name ?? '-',
                $s['kelas']->nama_kelas ?? '-',
                $s['mapel']->nama_mapel ?? '-',
                $s['jam_awal']->jam_ke === $s['jam_akhir']->jam_ke
                    ? $s['jam_awal']->jam_ke
                    : $s['jam_awal']->jam_ke.'-'.$s['jam_akhir']->jam_ke,
                substr((string) $s['jam_akhir']->jam_selesai, 0, 5),
                $s['slots']->first()->guru?->no_hp ?: '(belum diisi)',
            ])->all()
        );
    }
}
