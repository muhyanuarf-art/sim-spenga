<?php

namespace App\Http\Controllers;

use App\Models\Ekstrakurikuler;
use App\Models\EkstrakurikulerSiswa;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Anggota (siswa) per kegiatan ekstrakurikuler — dikelola Kesiswaan.
 * Siswa lintas kelas/angkatan boleh jadi anggota kegiatan yang sama, dan
 * 1 siswa boleh ikut lebih dari 1 kegiatan (lihat migrasi ekstrakurikuler_siswas).
 *
 * (2026-08-23, revisi) — cara utama menambah anggota sekarang per KELAS:
 * pilih 1 kelas, tampil checklist semua siswa di kelas itu (yang sudah
 * jadi anggota otomatis tercentang), Kesiswaan tinggal centang/hapus
 * centang lalu "Simpan" — 1x submit men-SINKRON-kan keanggotaan siswa
 * kelas itu (checklist juga otomatis jadi alat EDIT: centang yang
 * ketinggalan, hapus centang yang salah masuk, lalu simpan ulang).
 * Tombol "Centang Semua" mempercepat kalau 1 kelas ikut semua. Pencarian
 * individual per nama/NIS (lintas kelas) tetap ada sebagai jalur kedua.
 */
class EkstrakurikulerAnggotaController extends Controller
{
    /**
     * Keanggotaan hanya boleh diubah pada kegiatan milik TAHUN AJARAN AKTIF.
     *
     * Sejak ekstrakurikuler ikut periode (migrasi 2026_08_28_000003), setiap
     * tahun ajaran punya baris kegiatannya sendiri. Tanpa penjagaan ini,
     * tautan/bookmark lama masih menunjuk id kegiatan tahun sebelumnya —
     * dan anggota tahun berjalan bisa nyangkut di sana tanpa disadari.
     */
    private function pastikanPeriodeAktif(Ekstrakurikuler $ekstrakurikuler): void
    {
        abort_unless(
            Ekstrakurikuler::periodeAktif()->whereKey($ekstrakurikuler->id)->exists(),
            403,
            'Kegiatan ini milik tahun ajaran lain sehingga anggotanya tidak dapat diubah. Salin dulu ke periode aktif lewat menu Tahun Ajaran → Salin Data, atau buat kegiatan baru.'
        );
    }

    public function index(Request $request, Ekstrakurikuler $ekstrakurikuler)
    {
        // Sengaja TIDAK dijaga pastikanPeriodeAktif(): melihat daftar anggota
        // kegiatan periode lampau memang diperbolehkan (pemilih periode di
        // kepala halaman). Yang dijaga hanya aksi tulis di bawah.
        $ekstrakurikuler->load(['anggotas.siswa.kelas']);
        $kelasList = Kelas::aktif()->orderBy('nama_kelas')->get();

        // Kelas yang sedang dipilih untuk checklist (default: kelas
        // pertama, kalau ada) — supaya halaman langsung berguna tanpa
        // klik tambahan.
        $kelasId = (int) $request->get('kelas_id', $kelasList->first()->id ?? 0);
        $kelasDipilih = $kelasList->firstWhere('id', $kelasId);

        $siswaKelas = collect();
        $idAnggotaSaatIni = collect();
        if ($kelasDipilih) {
            $siswaKelas = Siswa::diKelas($kelasDipilih->id)->where('is_active', true)->orderBy('nama')->get();
            $idAnggotaSaatIni = $ekstrakurikuler->anggotas->pluck('siswa_id');
        }

        // Pencarian siswa LINTAS KELAS untuk ditambahkan satu-satu —
        // kecualikan yang sudah jadi anggota, supaya tidak dobel.
        $idSudahAnggota = $ekstrakurikuler->anggotas->pluck('siswa_id');
        $hasilCari = collect();
        if ($request->filled('cari')) {
            $hasilCari = Siswa::periodeAktif()->with('kelas')
                ->where('is_active', true)
                ->whereNotIn('id', $idSudahAnggota)
                ->where(function ($q) use ($request) {
                    $q->where('nama', 'like', "%{$request->cari}%")
                      ->orWhere('nis', 'like', "%{$request->cari}%");
                })
                ->orderBy('nama')
                ->limit(20)
                ->get();
        }

        return view('ekstrakurikuler.anggota', compact(
            'ekstrakurikuler', 'kelasList', 'kelasDipilih', 'siswaKelas', 'idAnggotaSaatIni', 'hasilCari'
        ));
    }

    /**
     * Sinkronkan anggota kegiatan ini KHUSUS untuk siswa dalam 1 kelas
     * (dari checklist) — siswa kelas ini yang dicentang jadi anggota,
     * yang tidak dicentang (padahal sebelumnya anggota) dikeluarkan.
     * Siswa dari kelas LAIN sama sekali tidak tersentuh oleh operasi ini.
     */
    public function syncKelas(Request $request, Ekstrakurikuler $ekstrakurikuler)
    {
        $this->pastikanPeriodeAktif($ekstrakurikuler);
        $validated = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'siswa_id' => ['nullable', 'array'],
            'siswa_id.*' => ['exists:siswas,id'],
        ]);

        $kelas = Kelas::findOrFail($validated['kelas_id']);
        $idTercentang = collect($validated['siswa_id'] ?? [])->map(fn ($id) => (int) $id);
        $idSiswaKelasIni = Siswa::diKelas($kelas->id)->where('is_active', true)->pluck('id');

        DB::transaction(function () use ($ekstrakurikuler, $idSiswaKelasIni, $idTercentang) {
            // Keluarkan: anggota lama dari kelas ini yang sekarang TIDAK dicentang.
            $ekstrakurikuler->anggotas()
                ->whereIn('siswa_id', $idSiswaKelasIni)
                ->whereNotIn('siswa_id', $idTercentang)
                ->delete();

            // Tambahkan: yang dicentang, anggota kelas ini, dan belum jadi anggota.
            $idSudahAnggota = $ekstrakurikuler->anggotas()->pluck('siswa_id');
            $idBaru = $idTercentang->intersect($idSiswaKelasIni)->diff($idSudahAnggota);
            foreach ($idBaru as $siswaId) {
                EkstrakurikulerSiswa::create([
                    'ekstrakurikuler_id' => $ekstrakurikuler->id,
                    'siswa_id' => $siswaId,
                    'tanggal_gabung' => now()->toDateString(),
                ]);
            }
        });

        return redirect()->route('ekstrakurikuler.anggota.index', ['ekstrakurikuler' => $ekstrakurikuler, 'kelas_id' => $kelas->id])
            ->with('success', "Anggota dari kelas {$kelas->nama_kelas} berhasil diperbarui.");
    }

    public function store(Request $request, Ekstrakurikuler $ekstrakurikuler)
    {
        $this->pastikanPeriodeAktif($ekstrakurikuler);
        $validated = $request->validate([
            'siswa_id' => ['required', 'exists:siswas,id'],
        ]);

        $sudahAnggota = $ekstrakurikuler->anggotas()->where('siswa_id', $validated['siswa_id'])->exists();
        if ($sudahAnggota) {
            return back()->with('error', 'Siswa ini sudah jadi anggota kegiatan ini.');
        }

        EkstrakurikulerSiswa::create([
            'ekstrakurikuler_id' => $ekstrakurikuler->id,
            'siswa_id' => $validated['siswa_id'],
            'tanggal_gabung' => now()->toDateString(),
        ]);

        return back()->with('success', 'Siswa berhasil ditambahkan sebagai anggota.');
    }

    public function destroy(Ekstrakurikuler $ekstrakurikuler, EkstrakurikulerSiswa $anggota)
    {
        $this->pastikanPeriodeAktif($ekstrakurikuler);
        if ($anggota->ekstrakurikuler_id !== $ekstrakurikuler->id) {
            abort(404);
        }

        // Absensi ekskul yang SUDAH tersimpan untuk siswa ini TIDAK ikut
        // terhapus (baris di absensi_ekskul_pesertas berdiri sendiri lewat
        // siswa_id, bukan lewat baris anggota ini) — konsisten dengan
        // prinsip "riwayat tidak berubah" yang dipakai di seluruh sistem.
        $anggota->delete();

        return back()->with('success', 'Siswa berhasil dikeluarkan dari kegiatan ini.');
    }
}
