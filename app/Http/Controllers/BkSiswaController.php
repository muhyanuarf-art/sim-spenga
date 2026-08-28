<?php

namespace App\Http\Controllers;

use App\Models\JenisPelanggaran;
use App\Models\KasusSiswa;
use App\Models\Kelas;
use App\Models\PemanggilanOrangTua;
use App\Models\PembinaanSiswa;
use App\Models\PenguranganPoinSiswa;
use App\Models\Siswa;
use App\Services\PoinSiswaService;
use App\Support\BkAccessScope;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class BkSiswaController extends Controller
{
    use BkAccessScope;

    /** Menu "Monitoring Siswa" — daftar siswa dengan poin aktif & status, sesuai cakupan akses role. */
    public function index(Request $request, PoinSiswaService $poinService)
    {
        $user = $request->user();
        $kelasIds = $this->bkKelasIdsUntukUser($user);

        abort_if($kelasIds === [], 403, 'Anda belum di-mapping ke kelas manapun. Hubungi Kurikulum/Admin.');

        $query = Siswa::periodeAktif()->with('kelas')->where('is_active', true);
        if ($kelasIds !== null) {
            $query->whereIn('kelas_id', $kelasIds);
        }
        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }
        if ($request->filled('cari')) {
            $query->where('nama', 'like', '%' . $request->cari . '%');
        }

        // Daftar bawaan hanya memuat siswa yang PERNAH punya kasus, supaya
        // tidak penuh ratusan siswa yang tidak relevan untuk BK.
        //
        // TAPI begitu pengguna MENCARI NAMA, pencariannya menjangkau SEMUA
        // siswa aktif. Dulu tidak begitu, dan itu menyulitkan pekerjaan yang
        // paling sering dilakukan: mencatat pelanggaran PERTAMA seorang
        // siswa — namanya tidak ketemu di sini karena belum punya kasus,
        // jadi pengguna harus menempuh jalur lain lewat menu terpisah.
        $sedangMencari = $request->filled('cari');
        if (! $sedangMencari) {
            $query->whereHas('kasusBk');
        }

        // PERBAIKAN PERFORMA (N+1) — sebelumnya ->ringkasan($siswa) dipanggil
        // di dalam map() per baris siswa (~9 query PER SISWA). Sekarang
        // dihitung sekaligus untuk seluruh daftar lewat ringkasanBanyak()
        // (jumlah query TETAP, tidak tergantung banyaknya siswa).
        $siswaList = $query->orderBy('nama')->get();
        $ringkasanPerSiswa = $poinService->ringkasanBanyak($siswaList->pluck('id'));

        $siswas = $siswaList
            ->map(fn ($siswa) => ['siswa' => $siswa, ...($ringkasanPerSiswa[$siswa->id] ?? [])])
            ->sortByDesc('poin_aktif')->values();

        $kelasList = in_array($user->role, ['admin', 'kurikulum', 'kepala_sekolah'])
            ? Kelas::aktif()->orderBy('nama_kelas')->get()
            : ($user->role === 'guru_bk' ? $user->kelasBk() : collect());

        return view('bk.siswa.index', compact('siswas', 'kelasList', 'sedangMencari'));
    }

    /**
     * Halaman SENTRAL profil siswa (Bagian 14 & 26 spec): ringkasan poin +
     * timeline lengkap semua kejadian, + tombol aksi cepat.
     */
    public function show(Request $request, Siswa $siswa, PoinSiswaService $poinService)
    {
        $user = $request->user();
        abort_unless($this->bkBisaAksesSiswa($user, $siswa), 403, 'Anda tidak memiliki akses ke data siswa ini.');

        $ringkasan = $poinService->ringkasan($siswa);

        $kasus = KasusSiswa::with(['jenisPelanggaran', 'guruPelapor', 'dibatalkanOleh'])
            ->where('siswa_id', $siswa->id)->orderByDesc('tanggal_kejadian')->get();
        $pembinaan = PembinaanSiswa::with(['petugas', 'evaluasiHarian'])
            ->where('siswa_id', $siswa->id)->orderByDesc('tanggal')->get();
        $pengurangan = PenguranganPoinSiswa::with(['petugas', 'dibatalkanOleh'])
            ->where('siswa_id', $siswa->id)->orderByDesc('tanggal')->get();
        $pemanggilan = PemanggilanOrangTua::with(['petugas', 'surat'])
            ->where('siswa_id', $siswa->id)->orderByDesc('tanggal')->get();

        // Riwayat diurutkan KRONOLOGIS dari yang PALING AWAL ke yang PALING
        // BARU, lalu diberi nomor urut di tampilan.
        $timelineSemua = collect()
            ->concat($kasus->map(fn ($k) => [
                'tanggal' => $k->tanggal_kejadian, 'jenis' => 'kasus', 'data' => $k,
            ]))
            ->concat($pembinaan->map(fn ($p) => [
                'tanggal' => $p->tanggal, 'jenis' => 'pembinaan', 'data' => $p,
            ]))
            ->concat($pengurangan->map(fn ($p) => [
                'tanggal' => $p->tanggal, 'jenis' => 'pengurangan', 'data' => $p,
            ]))
            ->concat($pemanggilan->map(fn ($p) => [
                'tanggal' => $p->tanggal, 'jenis' => 'pemanggilan', 'data' => $p,
            ]))
            ->sortBy(fn ($item) => $item['tanggal']->format('Y-m-d').'-'.$item['data']->id)
            ->values();

        // Jumlah per jenis dihitung dari SELURUH riwayat (bukan halaman yang
        // sedang dibuka), supaya angka pada tombol filter tidak berubah-ubah
        // saat pengguna berpindah halaman.
        $jumlahPerJenis = [
            'semua' => $timelineSemua->count(),
            'kasus' => $kasus->count(),
            'pembinaan' => $pembinaan->count(),
            'pengurangan' => $pengurangan->count(),
            'pemanggilan' => $pemanggilan->count(),
        ];

        // Filter jenis kini diproses di SERVER (dulu disembunyikan lewat
        // Alpine di browser). Bedanya: dulu seluruh riwayat tetap dirender ke
        // HTML meski disembunyikan — makin panjang riwayat siswa, makin berat
        // halamannya. Sekarang yang dikirim ke browser hanya baris yang
        // benar-benar tampil.
        $jenisFilter = $request->get('jenis', 'semua');
        if (! array_key_exists($jenisFilter, $jumlahPerJenis)) {
            $jenisFilter = 'semua';
        }

        $timelineTersaring = $jenisFilter === 'semua'
            ? $timelineSemua
            : $timelineSemua->where('jenis', $jenisFilter)->values();

        // Paginator DIBUAT MANUAL karena riwayat ini gabungan 4 tabel
        // (kasus, pembinaan, pengurangan poin, pemanggilan) yang tidak bisa
        // di-paginate lewat satu query. Nomor urut baris tetap benar lintas
        // halaman karena dihitung dari firstItem() di view.
        $perPage = (int) $request->get('per_page', 15);
        $perPage = in_array($perPage, [15, 30, 50, 100], true) ? $perPage : 15;
        $halaman = LengthAwarePaginator::resolveCurrentPage();

        $timeline = new LengthAwarePaginator(
            $timelineTersaring->forPage($halaman, $perPage)->values(),
            $timelineTersaring->count(),
            $perPage,
            $halaman,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Halaman ini sekarang MURNI untuk membaca rekam jejak siswa —
        // seluruh pencatatan BK berpangkal dari menu Buku Catatan BK. Daftar
        // jenis pelanggaran & kasus terbuka yang dulu dikirim ke sini hanya
        // dipakai oleh modal pencatatan yang sudah dihapus, jadi query-nya
        // ikut dibuang (dua query yang tidak lagi ada gunanya).

        return view('bk.siswa.show', compact(
            'siswa', 'ringkasan', 'timeline',
            'jumlahPerJenis', 'jenisFilter', 'perPage'
        ));
    }
}
