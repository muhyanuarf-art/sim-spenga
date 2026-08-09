<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\NotifikasiAlfaTerkirim;
use Illuminate\Http\Request;

class NotifikasiWhatsappController extends Controller
{
    /**
     * Histori/status pengiriman notifikasi WA Alfa ke orang tua, dengan
     * filter bulan (default bulan & tahun berjalan, mengikuti server).
     *
     * Cakupan data menurut role:
     * - Admin, Kurikulum, Kepala Sekolah: semua siswa/kelas.
     * - Guru yang menjabat Wali Kelas: hanya siswa di kelas walinya sendiri.
     * - Guru mapel biasa (bukan wali kelas): tidak ada data yang relevan
     *   untuk dilihat (notifikasi ini levelnya per KELAS/hari, bukan per
     *   mapel), jadi ditampilkan pesan penjelasan alih-alih tabel kosong.
     */
    public function index(Request $request)
    {
        $bulan = (int) $request->get('bulan', now()->month);
        $tahun = (int) $request->get('tahun', now()->year);
        $user = $request->user();

        $query = NotifikasiAlfaTerkirim::with(['siswa.kelas', 'mapel'])
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun);

        $kelasWali = null;
        $bisaFilterKelas = in_array($user->role, ['admin', 'kurikulum', 'kepala_sekolah']);
        $tanpaAksesData = false;

        if ($user->role === 'guru') {
            $kelasWali = $user->kelasWali;
            if ($kelasWali) {
                $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $kelasWali->id));
            } else {
                $tanpaAksesData = true;
            }
        } elseif ($bisaFilterKelas && $request->filled('kelas_id')) {
            $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $request->kelas_id));
        }

        $data = $tanpaAksesData ? collect() : $query->orderByDesc('tanggal')->orderBy('siswa_id')->get();

        $ringkasan = [
            'terkirim' => $data->where('status_kirim', 'terkirim')->count(),
            'pending' => $data->where('status_kirim', 'pending')->count(),
            'gagal' => $data->where('status_kirim', 'gagal')->count(),
        ];

        $kelasList = $bisaFilterKelas ? Kelas::orderBy('nama_kelas')->get() : collect();

        return view('notifikasi-wa.index', compact(
            'data', 'bulan', 'tahun', 'ringkasan', 'kelasList', 'kelasWali', 'bisaFilterKelas', 'tanpaAksesData'
        ));
    }
}
