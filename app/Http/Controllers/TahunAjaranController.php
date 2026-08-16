<?php

namespace App\Http\Controllers;

use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TahunAjaranController extends Controller
{
    public function index()
    {
        $tahunAjaran = TahunAjaran::orderByDesc('id')->get();
        return view('tahun-ajaran.index', compact('tahunAjaran'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:Ganjil,Genap'],
        ]);
        TahunAjaran::create($validated);
        return back()->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function update(Request $request, TahunAjaran $tahunAjaran)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:Ganjil,Genap'],
        ]);
        $tahunAjaran->update($validated);
        return back()->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    public function aktifkan(TahunAjaran $tahunAjaran)
    {
        TahunAjaran::query()->update(['is_active' => false]);
        $tahunAjaran->update(['is_active' => true]);
        return back()->with('success', "Tahun ajaran {$tahunAjaran->nama} {$tahunAjaran->semester} sekarang aktif.");
    }

    public function destroy(TahunAjaran $tahunAjaran)
    {
        return $this->hapusAtauGagalDenganPesan(
            $tahunAjaran,
            'Tahun ajaran berhasil dihapus.',
            'Tahun ajaran ini tidak dapat dihapus karena masih memiliki data terkait (jadwal, mapping guru, atau data lain).'
        );
    }

    /**
     * Salin mapping guru-mengajar, guru-BK, dan jadwal pelajaran dari 1
     * Tahun Ajaran sumber ke Tahun Ajaran tujuan ($tahunAjaran). Dipakai
     * saat mulai semester baru supaya Kurikulum tidak perlu input ulang
     * dari nol — cukup salin dari semester sebelumnya, lalu edit baris
     * yang memang berubah saja (mis. guru pensiun diganti, guru lain
     * nambah kelas).
     *
     * AMAN dijalankan berkali-kali / setelah tujuan sudah mulai diisi
     * manual: baris yang sudah ada di tujuan (kombinasi guru+kelas(+mapel)
     * yang sama) TIDAK diduplikasi atau ditimpa — hanya baris yang belum
     * ada yang ditambahkan (pakai insertOrIgnore, mengikuti unique
     * constraint masing-masing tabel).
     */
    public function duplikasiMapping(Request $request, TahunAjaran $tahunAjaran)
    {
        $validated = $request->validate([
            'sumber_tahun_ajaran_id' => ['required', 'integer', 'exists:tahun_ajarans,id'],
        ]);

        $sumberId = (int) $validated['sumber_tahun_ajaran_id'];

        if ($sumberId === $tahunAjaran->id) {
            return back()->withErrors(['sumber_tahun_ajaran_id' => 'Tahun ajaran sumber tidak boleh sama dengan tujuan.']);
        }

        $sumber = TahunAjaran::findOrFail($sumberId);

        [$jumlahMengajar, $jumlahBk, $jumlahJadwal] = DB::transaction(function () use ($sumberId, $tahunAjaran) {
            return [
                $this->salinBaris('guru_mengajar_kelas', ['guru_id', 'kelas_id', 'mata_pelajaran_id'], $sumberId, $tahunAjaran->id),
                $this->salinBaris('guru_bk_kelas', ['guru_id', 'kelas_id'], $sumberId, $tahunAjaran->id),
                $this->salinBaris('jadwal_pelajarans', ['hari', 'kelas_id', 'mata_pelajaran_id', 'guru_id', 'jam_pelajaran_id'], $sumberId, $tahunAjaran->id),
            ];
        });

        return back()->with('success',
            "Berhasil menyalin dari {$sumber->nama} {$sumber->semester} ke {$tahunAjaran->nama} {$tahunAjaran->semester}: "
            . "{$jumlahMengajar} mapping guru mengajar, {$jumlahBk} mapping guru BK, {$jumlahJadwal} jadwal pelajaran. "
            . "Baris yang mungkin sudah Anda input manual sebelumnya di tujuan tidak ikut ditimpa — silakan edit/tambah "
            . "baris yang memang berubah saja (mis. guru yang pensiun/pindah tugas)."
        );
    }

    /**
     * Salin semua baris dari 1 tabel ber-tahun_ajaran_id dari $sumberId ke
     * $tujuanId, hanya kolom-kolom di $kolomKunci (di luar id/tahun_ajaran_id/
     * timestamps). Baris yang sudah ada di tujuan (bentrok unique constraint)
     * otomatis dilewati. Mengembalikan jumlah baris yang benar-benar baru
     * ditambahkan.
     */
    private function salinBaris(string $table, array $kolomKunci, int $sumberId, int $tujuanId): int
    {
        $baris = DB::table($table)->where('tahun_ajaran_id', $sumberId)->get();
        if ($baris->isEmpty()) {
            return 0;
        }

        $sekarang = now();
        $rows = $baris->map(function ($b) use ($kolomKunci, $tujuanId, $sekarang) {
            $data = collect($kolomKunci)->mapWithKeys(fn ($kolom) => [$kolom => $b->$kolom])->toArray();
            $data['tahun_ajaran_id'] = $tujuanId;
            $data['created_at'] = $sekarang;
            $data['updated_at'] = $sekarang;
            return $data;
        })->all();

        return DB::table($table)->insertOrIgnore($rows);
    }
}
