<?php

namespace App\Http\Controllers;

use App\Models\HariLibur;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Kalender Libur — tanggal-tanggal yang tidak ada KBM.
 *
 * Isinya dipakai diam-diam oleh tiga bagian lain: pengingat WhatsApp
 * (dua kali, sebelum antre dan sebelum kirim) dan Rekapitulasi
 * Kepatuhan. Lihat App\Models\HariLibur.
 *
 * TIDAK dibatasi kunci periode. Libur semester justru jatuh di antara
 * dua periode, dan sering baru sempat dicatat setelah semester lama
 * ditutup — kalau dikunci, Admin tidak bisa memperbaiki kalender untuk
 * libur yang sedang berjalan. Lagi pula baris di sini tidak mengubah
 * satu pun data akademik; ia hanya menjawab "tanggal ini libur".
 */
class HariLiburController extends Controller
{
    public function index(Request $request)
    {
        $tahun = (int) ($request->input('tahun') ?: Carbon::today()->year);

        $libur = HariLibur::with('pembuat')
            ->whereYear('tanggal_selesai', '>=', $tahun)
            ->whereYear('tanggal_mulai', '<=', $tahun)
            ->orderBy('tanggal_mulai')
            ->get();

        // Tahun yang punya isi, supaya penyaringnya tidak menawarkan
        // tahun kosong. Tahun berjalan selalu ikut walau belum ada isinya.
        $daftarTahun = HariLibur::query()
            ->selectRaw('YEAR(tanggal_mulai) tahun')
            ->union(HariLibur::query()->selectRaw('YEAR(tanggal_selesai) tahun'))
            ->pluck('tahun')
            ->push(Carbon::today()->year)
            ->unique()
            ->sortDesc()
            ->values();

        return view('hari-libur.index', compact('libur', 'tahun', 'daftarTahun'));
    }

    public function store(Request $request)
    {
        $data = $this->periksa($request);

        HariLibur::create($data + ['dibuat_oleh' => auth()->id()]);

        return back()->with('success', 'Libur "'.$data['keterangan'].'" berhasil ditambahkan.');
    }

    public function update(Request $request, HariLibur $hariLibur)
    {
        $hariLibur->update($this->periksa($request, $hariLibur));

        return back()->with('success', 'Kalender libur berhasil diperbarui.');
    }

    public function destroy(HariLibur $hariLibur)
    {
        $keterangan = $hariLibur->keterangan;
        $hariLibur->delete();

        return back()->with('success', 'Libur "'.$keterangan.'" berhasil dihapus.');
    }

    /**
     * Pemeriksaan isian, dipakai bersama oleh store() dan update().
     *
     * Dua aturan yang tidak bisa diserahkan ke validator bawaan:
     * tanggal selesai tidak boleh mendahului tanggal mulai, dan rentang
     * yang ditulis tidak boleh bertindihan dengan rentang yang sudah
     * ada. Tindihan tidak merusak apa pun secara teknis — pemeriksaan
     * liburnya tetap benar — tetapi membuat kalender sulit dibaca dan
     * sulit diperbaiki: menghapus satu baris tidak lagi berarti hari itu
     * masuk kembali.
     */
    private function periksa(Request $request, ?HariLibur $kecuali = null): array
    {
        $data = $request->validate([
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'keterangan' => ['required', 'string', 'max:150'],
        ], [], [
            'tanggal_mulai' => 'tanggal mulai',
            'tanggal_selesai' => 'tanggal selesai',
        ]);

        $bertindih = HariLibur::query()
            ->when($kecuali, fn ($q) => $q->whereKeyNot($kecuali->getKey()))
            ->whereDate('tanggal_mulai', '<=', $data['tanggal_selesai'])
            ->whereDate('tanggal_selesai', '>=', $data['tanggal_mulai'])
            ->first();

        if ($bertindih) {
            abort(redirect()->back()->withInput()->with(
                'error',
                'Rentang ini bertindihan dengan "'.$bertindih->keterangan.'" ('.$bertindih->label().'). '
                .'Perbaiki baris itu lebih dulu, atau pilih tanggal lain.'
            ));
        }

        return $data;
    }
}
