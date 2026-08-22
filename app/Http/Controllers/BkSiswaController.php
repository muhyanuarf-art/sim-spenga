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

class BkSiswaController extends Controller
{
    use BkAccessScope;

    /** Menu "Monitoring Siswa" — daftar siswa dengan poin aktif & status, sesuai cakupan akses role. */
    public function index(Request $request, PoinSiswaService $poinService)
    {
        $user = $request->user();
        $kelasIds = $this->bkKelasIdsUntukUser($user);

        abort_if($kelasIds === [], 403, 'Anda belum di-mapping ke kelas manapun. Hubungi Kurikulum/Admin.');

        $query = Siswa::with('kelas')->where('is_active', true);
        if ($kelasIds !== null) {
            $query->whereIn('kelas_id', $kelasIds);
        }
        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }
        if ($request->filled('cari')) {
            $query->where('nama', 'like', '%' . $request->cari . '%');
        }

        // Hanya tampilkan siswa yang PERNAH punya kasus (supaya listing tidak
        // penuh 471 siswa yang tidak relevan sama sekali untuk BK).
        $query->whereHas('kasusBk');

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

        return view('bk.siswa.index', compact('siswas', 'kelasList'));
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
        $pemanggilan = PemanggilanOrangTua::with('petugas')
            ->where('siswa_id', $siswa->id)->orderByDesc('tanggal')->get();

        // Riwayat diurutkan KRONOLOGIS dari yang PALING AWAL ke yang PALING
        // BARU (permintaan: "urutkan berdasarkan tanggal serta inputan awal
        // sampai akhir"), lalu diberi nomor urut di tampilan (lihat
        // resources/views/bk/siswa/show.blade.php).
        $timeline = collect()
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
            ->sortBy(fn ($item) => $item['tanggal']->format('Y-m-d') . '-' . $item['data']->id)
            ->values();

        $jenisList = JenisPelanggaran::where('is_active', true)->orderBy('kategori')->orderBy('nama')->get();
        $kasusAktifTerbuka = $kasus->whereNull('dibatalkan_at')->whereNotIn('status', ['Selesai'])->values();

        return view('bk.siswa.show', compact(
            'siswa', 'ringkasan', 'timeline', 'jenisList', 'kasusAktifTerbuka'
        ));
    }
}
