<?php

namespace App\Http\Controllers;

use App\Models\KasusSiswa;
use App\Models\Kelas;
use App\Models\PemanggilanOrangTua;
use App\Models\PembinaanSiswa;
use App\Models\PenguranganPoinSiswa;
use App\Models\Siswa;
use App\Support\BkAccessScope;
use App\Support\RentangBulan;
use Illuminate\Http\Request;

/**
 * LAPORAN BULANAN BK — satu lembar rekap kegiatan BK selama satu bulan.
 *
 * Dulu ada tautan "Laporan Bulanan" di Menu Cepat Ringkasan BK, tapi
 * tautannya hanya mengarah ke daftar kasus biasa — laporannya sendiri tidak
 * pernah ada. Halaman ini yang sebenarnya dimaksud: rekap yang bisa
 * dicetak dan diserahkan ke Kepala Sekolah tiap akhir bulan.
 *
 * Isinya empat bagian:
 *   1. Rekap jumlah kegiatan BK (kasus, pembinaan, pengurangan, pemanggilan)
 *   2. Sebaran pelanggaran menurut kategori & jenis terbanyak
 *   3. Rekap per kelas — kelas mana yang paling perlu perhatian
 *   4. Daftar siswa yang ditangani bulan itu beserta tindak lanjutnya
 *
 * Seluruh angkanya dihitung dari tabel yang sama dengan daftar-daftar di
 * Buku Catatan BK, dengan penyaring rentang bulan yang sama
 * (App\Support\RentangBulan) — jadi laporan ini tidak akan pernah berbeda
 * dengan tab-tab di sebelahnya untuk bulan yang sama.
 *
 * Cakupan datanya mengikuti hak akses BK yang sudah berlaku: Guru BK hanya
 * kelas binaannya, pimpinan seluruh sekolah (lihat BkAccessScope).
 */
class BkLaporanBulananController extends Controller
{
    use BkAccessScope;

    public function index(Request $request)
    {
        $user = $request->user();
        $kelasIds = $this->bkKelasIdsUntukUser($user);

        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);
        [$awal, $akhir] = RentangBulan::dari($tahun, $bulan);

        // Batasan kelas dipakai berulang di lima query di bawah.
        $batasiKelasSiswa = fn ($q) => $kelasIds !== null
            ? $q->whereHas('siswa', fn ($s) => $s->whereIn('kelas_id', $kelasIds))
            : $q;

        // ===== 1. Kasus bulan ini =====
        $kasus = KasusSiswa::with(['siswa.kelas', 'jenisPelanggaran', 'guruPelapor'])
            ->aktif()
            ->when($kelasIds !== null, fn ($q) => $q->whereIn('kelas_id', $kelasIds))
            ->whereBetween('tanggal_kejadian', [$awal, $akhir])
            ->orderBy('tanggal_kejadian')
            ->get();

        // ===== 2. Kegiatan penanganan bulan ini =====
        $pembinaan = PembinaanSiswa::with(['siswa.kelas', 'petugas'])
            ->tap($batasiKelasSiswa)
            ->whereBetween('tanggal', [$awal, $akhir])
            ->orderBy('tanggal')->get();

        $pengurangan = PenguranganPoinSiswa::with(['siswa.kelas'])
            ->aktif()
            ->tap($batasiKelasSiswa)
            ->whereBetween('tanggal', [$awal, $akhir])
            ->orderBy('tanggal')->get();

        $pemanggilan = PemanggilanOrangTua::with(['siswa.kelas'])
            ->tap($batasiKelasSiswa)
            ->whereBetween('tanggal', [$awal, $akhir])
            ->orderBy('tanggal')->get();

        // ===== 3. Bulan sebelumnya, untuk pembanding =====
        [$awalLalu, $akhirLalu] = RentangBulan::dari(
            $bulan === 1 ? $tahun - 1 : $tahun,
            $bulan === 1 ? 12 : $bulan - 1
        );
        $kasusBulanLalu = KasusSiswa::aktif()
            ->when($kelasIds !== null, fn ($q) => $q->whereIn('kelas_id', $kelasIds))
            ->whereBetween('tanggal_kejadian', [$awalLalu, $akhirLalu])
            ->count();

        $ringkasan = [
            'kasus' => $kasus->count(),
            'kasus_bulan_lalu' => $kasusBulanLalu,
            'kasus_selesai' => $kasus->filter(fn ($k) => $k->isSelesai())->count(),
            'kasus_belum_selesai' => $kasus->filter(fn ($k) => ! $k->isSelesai())->count(),
            'total_poin' => (int) $kasus->sum('poin'),
            'siswa_terlibat' => $kasus->pluck('siswa_id')->unique()->count(),
            'pembinaan' => $pembinaan->count(),
            'pembinaan_selesai' => $pembinaan->filter(fn ($p) => $p->isSelesai())->count(),
            'pengurangan' => $pengurangan->count(),
            'poin_dikurangi' => (int) $pengurangan->sum('jumlah'),
            'pemanggilan' => $pemanggilan->count(),
            'ortu_hadir' => $pemanggilan->where('ortu_hadir', true)->count(),
        ];

        // ===== 4. Sebaran kategori & jenis pelanggaran terbanyak =====
        $perKategori = collect(['Ringan', 'Sedang', 'Berat', 'Sangat Berat'])
            ->mapWithKeys(fn ($k) => [$k => $kasus->where('kategori', $k)->count()]);

        $jenisTerbanyak = $kasus->groupBy('nama_pelanggaran')
            ->map(fn ($grup, $nama) => [
                'nama' => $nama,
                'jumlah' => $grup->count(),
                'poin' => (int) $grup->sum('poin'),
                'kategori' => $grup->first()->kategori,
            ])
            ->sortByDesc('jumlah')->values();

        // ===== 5. Rekap per kelas =====
        $perKelas = $kasus->groupBy(fn ($k) => $k->siswa->kelas->nama_kelas ?? '-')
            ->map(fn ($grup, $nama) => [
                'kelas' => $nama,
                'kasus' => $grup->count(),
                'poin' => (int) $grup->sum('poin'),
                'siswa' => $grup->pluck('siswa_id')->unique()->count(),
            ])
            ->sortByDesc('kasus')->values();

        // ===== 6. Daftar siswa yang ditangani bulan ini =====
        // Digabung dari keempat jenis catatan, supaya siswa yang bulan ini
        // hanya dibina (tanpa kasus baru) tetap muncul di laporan.
        $siswaIds = $kasus->pluck('siswa_id')
            ->merge($pembinaan->pluck('siswa_id'))
            ->merge($pengurangan->pluck('siswa_id'))
            ->merge($pemanggilan->pluck('siswa_id'))
            ->unique()->values();

        $siswaMap = Siswa::with('kelas')->whereIn('id', $siswaIds)->get()->keyBy('id');

        $daftarSiswa = $siswaIds->map(fn ($id) => [
            'siswa' => $siswaMap[$id] ?? null,
            'kasus' => $kasus->where('siswa_id', $id)->count(),
            'poin' => (int) $kasus->where('siswa_id', $id)->sum('poin'),
            'pembinaan' => $pembinaan->where('siswa_id', $id)->count(),
            'pengurangan' => (int) $pengurangan->where('siswa_id', $id)->sum('jumlah'),
            'pemanggilan' => $pemanggilan->where('siswa_id', $id)->count(),
        ])
            ->filter(fn ($r) => $r['siswa'] !== null)
            ->sortByDesc('poin')
            ->values();

        $kelasList = in_array($user->role, ['admin', 'kurikulum', 'kepala_sekolah'], true)
            ? Kelas::aktif()->orderBy('nama_kelas')->get()
            : ($user->role === 'guru_bk' ? $user->kelasBk() : collect());

        $guruBk = $this->bkGuruBkUntukCetak($user, null);

        return view('bk.laporan-bulanan', compact(
            'bulan', 'tahun', 'ringkasan', 'perKategori', 'jenisTerbanyak',
            'perKelas', 'daftarSiswa', 'kelasList', 'guruBk'
        ));
    }
}
