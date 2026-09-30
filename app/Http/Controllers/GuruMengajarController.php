<?php

namespace App\Http\Controllers;

use App\Support\KonteksPeriode;
use App\Support\JalankanImport;
use App\Exports\TemplateExport;
use App\Imports\GuruMengajarImport;
use App\Models\GuruMengajarKelas;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\PeriodeAkademik;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class GuruMengajarController extends Controller
{
    /**
     * STEP 6 Bagian 19/20 — default menampilkan Mapping periode AKTIF,
     * tapi admin bisa memilih periode lain (histori) lewat dropdown tanpa
     * data tercampur. "+ Tambah" & Import tetap SELALU ke periode aktif
     * (tidak terpengaruh periode yang sedang DILIHAT) — konsisten dengan
     * store()/import() di bawah.
     */
    public function index(Request $request)
    {
        $periodeAktif = KonteksPeriode::pilihan();
        $tahunAjaranList = TahunAjaran::orderByDesc('id')->get();
        $periodeDilihat = $request->filled('tahun_ajaran_id')
            ? TahunAjaran::find($request->integer('tahun_ajaran_id'))
            : $periodeAktif;

        $query = GuruMengajarKelas::with(['guru', 'kelas', 'mapel'])
            ->when($periodeDilihat, fn ($q) => $q->where('guru_mengajar_kelas.tahun_ajaran_id', $periodeDilihat->id))
            ->when($request->kelas_id, fn ($q) => $q->where('guru_mengajar_kelas.kelas_id', $request->kelas_id))
            ->when($request->guru_id, fn ($q) => $q->where('guru_mengajar_kelas.guru_id', $request->guru_id));

        $data = $query->join('kelas', 'guru_mengajar_kelas.kelas_id', '=', 'kelas.id')
            ->orderBy('kelas.nama_kelas')
            ->select('guru_mengajar_kelas.*')
            ->paginate(25)
            ->withQueryString();

        // STEP 5 Bagian 16/23 — hanya kelas TAHUN AJARAN AKTIF (mapping
        // selalu ditulis ke tahun_ajaran_id = aktif(), lihat store()).
        $kelasList = Kelas::aktif()->orderBy('nama_kelas')->get();
        $guruList = User::where('role', 'guru')->orderBy('name')->get();
        $mapelList = MataPelajaran::periodeAktif()->orderBy('nama_mapel')->get();

        return view('kurikulum.guru-mengajar.index', compact('data', 'kelasList', 'guruList', 'mapelList', 'periodeAktif', 'periodeDilihat', 'tahunAjaranList'));
    }

    public function store(Request $request)
    {
        $tahunAjaran = TahunAjaran::aktif();
        abort_if(! $tahunAjaran, 422, 'Tidak ada tahun ajaran aktif. Aktifkan dahulu di menu Tahun Ajaran.');

        // BANYAK KELAS SEKALIGUS UNTUK SATU GURU + SATU MATA PELAJARAN.
        //
        // Seorang guru IPA yang mengampu 7A sampai 7D dulu harus mengisi
        // formulir ini empat kali, dengan guru dan mapel yang sama diulang
        // terus. Sekarang kelasnya dicentang sekaligus.
        //
        // Yang TERSIMPAN tidak berubah sama sekali: tetap satu baris per
        // guru + kelas + mapel, persis seperti sebelumnya. Yang berubah
        // hanya cara mengisinya. Jadi tabel, filter, edit per baris, jadwal
        // pelajaran, dan impor Excel semuanya tetap berjalan apa adanya.
        $validated = $request->validate([
            'guru_id' => ['required', 'exists:users,id'],
            'kelas_id' => ['required', 'array', 'min:1'],
            // STEP 5 Bagian 16 — kelas WAJIB dari tahun ajaran yang sama
            // dengan mapping ini. Ditolak kalau tidak.
            'kelas_id.*' => [
                Rule::exists('kelas', 'id')->where(
                    fn ($q) => $q->whereIn('id', Kelas::untukTahunAjaran($tahunAjaran)->pluck('id'))
                ),
            ],
            'mata_pelajaran_id' => ['required', 'exists:mata_pelajarans,id'],
        ], [], ['kelas_id' => 'kelas']);

        $dibuat = 0;
        $sudahAda = 0;

        foreach ($validated['kelas_id'] as $kelasId) {
            // firstOrCreate, bukan create: mencentang kelas yang mappingnya
            // sudah ada tidak boleh menggandakan barisnya — dan operator
            // tetap diberi tahu berapa yang dilewati, supaya tidak mengira
            // centangannya gagal tersimpan.
            $baris = GuruMengajarKelas::firstOrCreate([
                'guru_id' => $validated['guru_id'],
                'kelas_id' => $kelasId,
                'mata_pelajaran_id' => $validated['mata_pelajaran_id'],
                'tahun_ajaran_id' => $tahunAjaran->id,
            ]);

            $baris->wasRecentlyCreated ? $dibuat++ : $sudahAda++;
        }

        $pesan = $dibuat > 0
            ? "Mapping berhasil ditambahkan untuk {$dibuat} kelas."
            : 'Tidak ada mapping baru yang ditambahkan.';

        if ($sudahAda > 0) {
            $pesan .= " {$sudahAda} kelas dilewati karena mappingnya sudah ada.";
        }

        return back()->with($dibuat > 0 ? 'success' : 'error', $pesan);
    }

    public function update(Request $request, GuruMengajarKelas $guruMengajar)
    {
        // STEP 2 Bagian 8: cek periode MILIK BARIS INI (bukan periode aktif
        // global) — mapping bisa saja bukan milik tahun ajaran yang sedang
        // aktif sekarang.
        PeriodeAkademik::pastikanTidakTerkunci($guruMengajar->tahunAjaran);

        $validated = $request->validate([
            'guru_id' => ['required', 'exists:users,id'],
            'kelas_id' => [
                'required',
                Rule::exists('kelas', 'id')->where(
                    fn ($q) => $q->whereIn('id', Kelas::untukTahunAjaran($guruMengajar->tahunAjaran)->pluck('id'))
                ),
            ],
            'mata_pelajaran_id' => ['required', 'exists:mata_pelajarans,id'],
        ]);

        $guruMengajar->update($validated);

        return back()->with('success', 'Mapping guru mengajar berhasil diperbarui.');
    }

    public function destroy(GuruMengajarKelas $guruMengajar)
    {
        PeriodeAkademik::pastikanTidakTerkunci($guruMengajar->tahunAjaran);

        return $this->hapusAtauGagalDenganPesan(
            $guruMengajar,
            'Mapping berhasil dihapus.',
            'Mapping ini tidak dapat dihapus karena masih dipakai di jadwal pelajaran.'
        );
    }

    public function importForm()
    {
        return view('kurikulum.guru-mengajar.import');
    }

    public function template()
    {
        return Excel::download(new TemplateExport(
            ['nip_guru', 'kode_kelas', 'kode_mapel'],
            [
                ['198501012010011001', '7A', 'MTK'],
                ['198502022011012002', '7B', 'IPA'],
            ],
            'Mapping Guru Mengajar',
            [
                'Petunjuk:',
                '- nip_guru diisi dengan NIP guru yang sudah terdaftar di menu Kelola Pengguna.',
                '- kode_kelas diisi sesuai nama kelas pada menu Data Kelas UNTUK TAHUN AJARAN AKTIF (contoh: 7A). Pastikan kelas tsb sudah dibuat untuk tahun ajaran yang sedang aktif sebelum import.',
                '- kode_mapel diisi sesuai kode pada menu Mata Pelajaran (contoh: MTK).',
                '- Hapus baris contoh ini sebelum mengisi data yang sebenarnya.',
            ]
        ), 'template-mapping-guru-mengajar.xlsx');
    }

    public function import(Request $request)
    {
        [$aturan, $pesan] = JalankanImport::aturanBerkas();
        $request->validate($aturan, $pesan);

        $tahunAjaran = TahunAjaran::aktif();
        abort_if(! $tahunAjaran, 422, 'Tidak ada tahun ajaran aktif.');

        return JalankanImport::jalankan(
            new GuruMengajarImport($tahunAjaran->id),
            $request->file('file'),
            'kurikulum.guru-mengajar.import.form'
        );
    }
}
