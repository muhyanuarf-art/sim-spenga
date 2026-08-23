<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\NotifikasiAlfaTerkirim;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class NotifikasiWhatsappController extends Controller
{
    /**
     * Histori/status pengiriman notifikasi WA Alfa ke orang tua, dengan
     * filter bulan (default bulan & tahun berjalan, mengikuti server).
     *
     * Cakupan data menurut role:
     * - Admin, Kurikulum, Kepala Sekolah: semua siswa/kelas.
     * - Guru yang menjabat Wali Kelas: hanya siswa di kelas walinya sendiri.
     * - Guru BK: siswa di kelas-kelas yang di-mapping-kan kepadanya (bisa lebih dari 1).
     * - Guru mapel biasa (bukan wali kelas): tidak ada data yang relevan
     *   untuk dilihat (notifikasi ini levelnya per KELAS/hari, bukan per
     *   mapel), jadi ditampilkan pesan penjelasan alih-alih tabel kosong.
     */
    public function index(Request $request)
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);
        $user = $request->user();

        // PERBAIKAN PERFORMA — whereMonth()/whereYear() membungkus kolom
        // `tanggal` dengan fungsi (MONTH(tanggal), YEAR(tanggal)), yang
        // membuat MySQL TIDAK BISA memakai index pada kolom tsb sama sekali
        // (index hanya bisa dipakai untuk perbandingan langsung, bukan hasil
        // fungsi) — jadi MySQL scan SELURUH tabel setiap halaman ini dibuka.
        // whereBetween(tanggal, [awal, akhir]) di bawah ini setara secara
        // hasil, tapi BISA memakai index (lihat migrasi index baru pada
        // kolom `tanggal`), jauh lebih cepat begitu tabelnya sudah berisi
        // data beberapa bulan/tahun.
        $awalBulan = Carbon::create($tahun, $bulan, 1)->startOfDay();
        $akhirBulan = $awalBulan->copy()->endOfMonth()->endOfDay();

        $query = NotifikasiAlfaTerkirim::with(['siswa.kelas', 'mapel'])
            ->whereBetween('tanggal', [$awalBulan, $akhirBulan]);

        $kelasWali = null;
        $bisaFilterKelas = in_array($user->role, ['admin', 'kurikulum', 'kepala_sekolah', 'kesiswaan']);
        $tanpaAksesData = false;
        $kelasBkList = collect();

        if ($user->role === 'guru') {
            $kelasWali = $user->kelasWali;
            if ($kelasWali) {
                $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $kelasWali->id));
            } else {
                $tanpaAksesData = true;
            }
        } elseif ($user->role === 'guru_bk') {
            $kelasBkList = $user->kelasBk();
            if ($kelasBkList->isEmpty()) {
                $tanpaAksesData = true;
            } else {
                $query->whereHas('siswa', fn ($q) => $q->whereIn('kelas_id', $kelasBkList->pluck('id')));
            }
        } elseif ($bisaFilterKelas && $request->filled('kelas_id')) {
            $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $request->kelas_id));
        }

        // PERBAIKAN PERFORMA — ringkasan dihitung lewat 3 query COUNT
        // ringan (memakai index yang sama), BUKAN lagi dari koleksi penuh
        // di memori — supaya tetap akurat (mencakup SELURUH bulan) walau
        // $data di bawah sekarang dipaginasi (tidak lagi memuat semua baris
        // sekaligus, yang sebelumnya bisa jadi ribuan baris untuk sekolah
        // dengan banyak siswa & tanpa batas apa pun).
        $ringkasan = $tanpaAksesData ? ['terkirim' => 0, 'pending' => 0, 'gagal' => 0, 'dilewati' => 0] : [
            'terkirim' => (clone $query)->where('status_kirim', 'terkirim')->count(),
            'pending' => (clone $query)->where('status_kirim', 'pending')->count(),
            'gagal' => (clone $query)->where('status_kirim', 'gagal')->count(),
            'dilewati' => (clone $query)->where('status_kirim', 'dilewati')->count(),
        ];

        $data = $tanpaAksesData
            ? new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50)
            : $query->orderByDesc('tanggal')->orderBy('siswa_id')->paginate(50)->withQueryString();

        $kelasList = $bisaFilterKelas ? Kelas::aktif()->orderBy('nama_kelas')->get() : collect();

        return view('notifikasi-wa.index', compact(
            'data', 'bulan', 'tahun', 'ringkasan', 'kelasList', 'kelasWali', 'kelasBkList', 'bisaFilterKelas', 'tanpaAksesData'
        ));
    }
}
