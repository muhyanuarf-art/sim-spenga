<?php

namespace App\Http\Controllers;

use App\Support\JalankanImport;
use App\Exports\TemplateExport;
use App\Imports\KelasImport;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class KelasController extends Controller
{
    /**
     * STEP 5 Bagian 23 — default menampilkan kelas TAHUN AJARAN AKTIF.
     * Admin/Kurikulum bisa pindah ke tahun ajaran lain lewat dropdown
     * (mis. untuk menyiapkan kelas tahun ajaran berikutnya SEBELUM
     * diaktifkan — lihat Bagian 13) — itu BUKAN halaman histori, jadi
     * tetap dapat CRUD, bukan cuma lihat.
     */
    public function index(Request $request)
    {
        $tahunAjaranList = TahunAjaran::where('semester', 'Ganjil')->orderByDesc('id')->get();
        $periodeAktif = TahunAjaran::aktif();

        $tahunAjaranDipilih = $request->filled('tahun_ajaran_id')
            ? TahunAjaran::find($request->integer('tahun_ajaran_id'))
            : ($periodeAktif ? TahunAjaran::where('nama', $periodeAktif->nama)->where('semester', 'Ganjil')->first() : null);

        $kelas = $tahunAjaranDipilih
            ? Kelas::where('tahun_ajaran_id', $tahunAjaranDipilih->id)
                ->withCount('siswas')->with('waliKelas')->orderBy('nama_kelas')->paginate(20)
            : Kelas::query()->whereRaw('1 = 0')->paginate(20);

        $guruList = User::where('role', 'guru')->orderBy('name')->get();

        // Untuk fitur "Salin Struktur Kelas" (Bagian 14) — daftar tahun
        // ajaran LAIN yang punya kelas, sebagai pilihan sumber salin.
        $tahunAjaranSumberPilihan = $tahunAjaranDipilih
            ? $tahunAjaranList->filter(fn ($t) => $t->id !== $tahunAjaranDipilih->id
                && Kelas::where('tahun_ajaran_id', $t->id)->exists())
            : collect();

        return view('kelas.index', compact(
            'kelas', 'guruList', 'tahunAjaranList', 'tahunAjaranDipilih', 'tahunAjaranSumberPilihan'
        ));
    }

    /**
     * STEP 5 Bagian 15/26 — unique per (tahun_ajaran_id, tingkat, nama_kelas),
     * BUKAN nama_kelas saja lagi — nama kelas yang sama SAH dipakai ulang
     * di tahun ajaran berbeda (itu tujuan utama STEP 5).
     */
    private function aturanValidasi(?int $ignoreId = null): array
    {
        return [
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajarans,id'],
            'nama_kelas' => [
                'required', 'string', 'max:10',
                Rule::unique('kelas', 'nama_kelas')
                    ->where(fn ($q) => $q->where('tahun_ajaran_id', request('tahun_ajaran_id'))
                        ->where('tingkat', request('tingkat')))
                    ->ignore($ignoreId),
            ],
            'tingkat' => ['required', 'integer', 'in:7,8,9'],
            'wali_kelas_id' => ['nullable', 'exists:users,id'],
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->aturanValidasi());
        $kelas = Kelas::create($validated);

        return back()->with('success', "Kelas {$kelas->nama_kelas} berhasil ditambahkan untuk Tahun Ajaran {$kelas->tahunAjaran->nama}.");
    }

    /**
     * STEP 5 Bagian 9/10/22 — Test 9: karena tiap tahun ajaran sekarang
     * punya baris kelas SENDIRI, mengubah wali_kelas_id di sini HANYA
     * menyentuh baris kelas tahun ajaran ini. Baris kelas tahun ajaran
     * lain (nama_kelas boleh sama, id BEDA) tidak pernah tersentuh sama
     * sekali — tidak perlu lagi tabel histori terpisah seperti STEP 4.
     *
     * `tahun_ajaran_id` SENGAJA TIDAK BOLEH diubah lewat form ini (kelas
     * tidak "pindah" tahun ajaran) — kalau perlu kelas yang sama di
     * tahun lain, pakai "Salin Struktur Kelas" untuk membuat baris baru.
     */
    public function update(Request $request, Kelas $kelas)
    {
        $request->merge(['tahun_ajaran_id' => $kelas->tahun_ajaran_id]); // kunci ke tahun ajaran aslinya
        $validated = $request->validate($this->aturanValidasi($kelas->id));

        $kelas->update($validated);

        return back()->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Kelas $kelas)
    {
        return $this->hapusAtauGagalDenganPesan(
            $kelas,
            'Kelas berhasil dihapus.',
            'Kelas ini tidak dapat dihapus karena masih memiliki data terkait (siswa, jadwal, atau data lain).'
        );
    }

    /**
     * STEP 5 Bagian 14 — "Salin Struktur Kelas dari Tahun Sebelumnya".
     * Hasil salinan SELALU jadi baris/ID baru (bukan reuse ID lama), TIDAK
     * menyalin wali_kelas_id (admin atur ulang manual sesuai Bagian 14 —
     * wali kelas tahun baru memang wajar berbeda dari tahun lama), dan
     * aman dijalankan berulang (firstOrCreate, kombinasi yang sudah ada
     * di tujuan otomatis dilewati, bukan error/duplikat).
     */
    public function salinDariTahunAjaran(Request $request)
    {
        $validated = $request->validate([
            'tahun_ajaran_tujuan_id' => ['required', 'exists:tahun_ajarans,id'],
            'tahun_ajaran_sumber_id' => ['required', 'exists:tahun_ajarans,id', 'different:tahun_ajaran_tujuan_id'],
        ]);

        $tujuan = TahunAjaran::findOrFail($validated['tahun_ajaran_tujuan_id']);
        $sumber = TahunAjaran::findOrFail($validated['tahun_ajaran_sumber_id']);

        $disalin = 0;
        $dilewati = 0;

        foreach (Kelas::where('tahun_ajaran_id', $sumber->id)->get() as $kelasSumber) {
            $baru = Kelas::firstOrCreate([
                'tahun_ajaran_id' => $tujuan->id,
                'tingkat' => $kelasSumber->tingkat,
                'nama_kelas' => $kelasSumber->nama_kelas,
            ]);
            $baru->wasRecentlyCreated ? $disalin++ : $dilewati++;
        }

        $pesan = "Berhasil menyalin {$disalin} kelas dari {$sumber->nama} ke {$tujuan->nama}.";
        if ($dilewati > 0) {
            $pesan .= " {$dilewati} kelas dilewati karena nama & tingkatnya sudah ada di tujuan.";
        }

        return redirect()->route('kelas.index', ['tahun_ajaran_id' => $tujuan->id])->with('success', $pesan);
    }

    public function importForm()
    {
        $tahunAjaranList = TahunAjaran::where('semester', 'Ganjil')->orderByDesc('id')->get();
        return view('kelas.import', compact('tahunAjaranList'));
    }

    /** STEP 5 — import sekarang butuh tahun_ajaran_id tujuan (default: tahun ajaran aktif). */
    public function import(Request $request)
    {
        [$aturan, $pesan] = JalankanImport::aturanBerkas();
        $validated = $request->validate(
            $aturan + ['tahun_ajaran_id' => ['required', 'exists:tahun_ajarans,id']],
            $pesan + ['tahun_ajaran_id.required' => 'Pilih dulu Tahun Ajaran tujuan.']
        );

        return JalankanImport::jalankan(
            new KelasImport((int) $validated['tahun_ajaran_id']),
            $request->file('file'),
            'kelas.import.form'
        );
    }

    public function template()
    {
        return Excel::download(new TemplateExport(
            ['nama_kelas', 'tingkat', 'nip_wali_kelas'],
            [
                ['7A', 7, '198501012010011001'],
                ['7B', 7, ''],
            ],
            'Data Kelas',
            [
                'Petunjuk:',
                '- nama_kelas wajib diisi dan bersifat unik PER TAHUN AJARAN & TINGKAT (contoh: 7A, 8B, 9C boleh dipakai ulang di tahun ajaran berbeda).',
                '- tingkat diisi salah satu dari: 7, 8, atau 9.',
                '- nip_wali_kelas bersifat opsional; jika diisi, harus NIP guru yang sudah terdaftar di menu Kelola Pengguna.',
                '- Tahun Ajaran tujuan dipilih di halaman import, bukan di file Excel.',
                '- Hapus baris contoh ini sebelum mengisi data yang sebenarnya.',
            ]
        ), 'template-data-kelas.xlsx');
    }
}
