<?php

namespace App\Http\Controllers;

use App\Support\JalankanImport;
use App\Exports\TemplateExport;
use App\Imports\SiswaImport;
use App\Models\Kelas;
use App\Models\RiwayatKelasSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        // 'orangTua' di-eager-load supaya kolom "Akun Ortu" di tabel tidak
        // memicu query per baris (akun portal orang tua dikelola dari sini
        // sejak menunya digabung — lihat OrangTuaController).
        $query = Siswa::with(['kelas', 'orangTua'])->when($request->kelas_id, fn ($q) => $q->where('kelas_id', $request->kelas_id));
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('nama', 'like', "%{$request->search}%")
                  ->orWhere('nis', 'like', "%{$request->search}%");
            });
        }
        $siswas = $query->orderBy('nama')->paginate(25)->withQueryString();
        // STEP 5 Bagian 23 — dropdown filter default kelas TAHUN AJARAN AKTIF.
        $kelasList = Kelas::aktif()->orderBy('nama_kelas')->get();

        // Untuk tombol "Buatkan Akun Ortu" — dihitung untuk SELURUH siswa
        // aktif, bukan cuma yang tampil di halaman ini, karena tombolnya
        // memang memproses semuanya sekaligus.
        $siswaTanpaAkunOrtu = Siswa::where('is_active', true)->whereDoesntHave('orangTua')->count();

        return view('siswa.index', compact('siswas', 'kelasList', 'siswaTanpaAkunOrtu'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nis' => ['required', 'string', 'unique:siswas,nis'],
            'nisn' => ['nullable', 'string'],
            'nama' => ['required', 'string', 'max:255'],
            'nama_ortu' => ['nullable', 'string', 'max:255'],
            'no_wa_ortu' => ['nullable', 'string', 'max:20'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            // STEP 5 Bagian 23 — siswa baru selalu ditempatkan ke kelas
            // TAHUN AJARAN AKTIF (bukan kelas tahun ajaran lain).
            'kelas_id' => [
                'required',
                Rule::exists('kelas', 'id')->where(fn ($q) => $q->whereIn('id', Kelas::aktif()->pluck('id'))),
            ],
        ]);
        $siswa = Siswa::create($validated);

        // (2026-08-23) — catat baris riwayat "awal_masuk" saat siswa baru
        // ditambahkan manual lewat sini. Sebelumnya method ini TIDAK
        // membuat baris riwayat sama sekali, sehingga siswa yang kelak
        // "Pindah Kelas" hanya punya 1 baris riwayat (baris pindahnya
        // sendiri) tanpa riwayat kelas SEBELUM tanggal pindah itu — celah
        // ini yang membuat form Isi Absensi untuk tanggal sebelum pindah
        // salah mengira siswa itu belum pernah tercatat di kelas manapun.
        // Lihat App\Support\KeanggotaanKelas untuk bagaimana riwayat ini
        // dipakai (dan jaring pengaman tambahan di sana untuk data lama
        // yang sudah kadung tidak punya baris awal_masuk).
        $this->catatMutasiKelas(
            $siswa, null, (int) $validated['kelas_id'], now()->toDateString(),
            null, RiwayatKelasSiswa::JENIS_AWAL_MASUK
        );

        return back()->with('success', 'Siswa berhasil ditambahkan.');
    }

    public function update(Request $request, Siswa $siswa)
    {
        $validated = $request->validate([
            'nis' => ['required', 'string', 'unique:siswas,nis,' . $siswa->id],
            'nisn' => ['nullable', 'string'],
            'nama' => ['required', 'string', 'max:255'],
            'nama_ortu' => ['nullable', 'string', 'max:255'],
            'no_wa_ortu' => ['nullable', 'string', 'max:20'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'kelas_id' => [
                'required',
                Rule::exists('kelas', 'id')->where(fn ($q) => $q->whereIn('id', Kelas::aktif()->pluck('id'))),
            ],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active', true);

        // (2026-08-23) — kelas_id boleh diubah lewat form Edit ini juga
        // (mis. koreksi salah input), bukan cuma lewat tombol khusus
        // "Pindah Kelas" di bawah. Supaya Riwayat Kelas TETAP akurat apa
        // pun jalur yang dipakai admin/kurikulum, catat mutasinya di sini
        // juga kalau kelas_id benar-benar berubah.
        $kelasAsalId = $siswa->kelas_id;
        $siswa->update($validated);

        if ((int) $validated['kelas_id'] !== (int) $kelasAsalId) {
            $this->catatMutasiKelas($siswa, $kelasAsalId, (int) $validated['kelas_id'], now()->toDateString(),
                'Diubah lewat form Edit Data Siswa.');
        }

        return back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    /**
     * Aksi khusus "Pindah Kelas" — dipakai untuk siswa yang pindah kelas DI
     * TENGAH tahun ajaran berjalan (mis. Juli-Agustus di 7A, September
     * pindah ke 7B). Berbeda dengan update() di atas, aksi ini memang
     * dikhususkan untuk mutasi (bukan koreksi data), sehingga tanggal
     * efektif & keterangan bisa diisi eksplisit oleh operator.
     *
     * Data absensi & jurnal bulan-bulan sebelumnya TIDAK ikut berubah —
     * absensi_siswas.kelas_id adalah snapshot permanen per baris (lihat
     * catatan di WaliKelasController & LaporanGuruController), jadi riwayat
     * Juli-Agustus siswa tetap tercatat di kelas lama.
     */
    public function pindahKelas(Request $request, Siswa $siswa)
    {
        $validated = $request->validate([
            'kelas_tujuan_id' => [
                'required',
                Rule::exists('kelas', 'id')->where(fn ($q) => $q->whereIn('id', Kelas::aktif()->pluck('id'))),
            ],
            'tanggal_mutasi' => ['nullable', 'date'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ]);

        $kelasTujuanId = (int) $validated['kelas_tujuan_id'];
        if ($kelasTujuanId === (int) $siswa->kelas_id) {
            return back()->with('error', 'Kelas tujuan sama dengan kelas siswa saat ini.');
        }

        $kelasAsalId = $siswa->kelas_id;
        $tanggalMutasi = $validated['tanggal_mutasi'] ?? now()->toDateString();

        $siswa->update(['kelas_id' => $kelasTujuanId]);

        $this->catatMutasiKelas($siswa, $kelasAsalId, $kelasTujuanId, $tanggalMutasi, $validated['keterangan'] ?? null);

        return back()->with('success', 'Siswa berhasil dipindahkan ke kelas baru. Data absensi/jurnal bulan-bulan sebelumnya tetap tercatat di kelas lama.');
    }

    /**
     * Catat 1 baris riwayat_kelas_siswas untuk tahun ajaran aktif (baris
     * Semester Ganjil, sama seperti konvensi di SiswaImport). Dipanggil
     * dari store() (siswa baru -> jenis awal_masuk), update() (kalau
     * kelas_id berubah lewat form Edit -> jenis pindah_kelas) maupun
     * pindahKelas() (aksi khusus -> jenis pindah_kelas).
     */
    private function catatMutasiKelas(
        Siswa $siswa, ?int $kelasAsalId, int $kelasTujuanId, string $tanggalMutasi,
        ?string $keterangan, string $jenis = RiwayatKelasSiswa::JENIS_PINDAH_KELAS
    ): void {
        $tahunAjaranAktif = TahunAjaran::aktif();
        if (! $tahunAjaranAktif) {
            return;
        }
        $tahunAjaranGanjil = TahunAjaran::where('nama', $tahunAjaranAktif->nama)->where('semester', 'Ganjil')->first();
        if (! $tahunAjaranGanjil) {
            return;
        }

        RiwayatKelasSiswa::create([
            'siswa_id' => $siswa->id,
            'tahun_ajaran_id' => $tahunAjaranGanjil->id,
            'kelas_asal_id' => $kelasAsalId,
            'kelas_id' => $kelasTujuanId,
            'jenis' => $jenis,
            'tanggal_mutasi' => $tanggalMutasi,
            'keterangan' => $keterangan,
            'dicatat_oleh_id' => auth()->id(),
        ]);
    }

    /**
     * STEP 7 Bagian 30 — sebelumnya method ini memanggil $siswa->delete()
     * TANPA perlindungan apa pun: kalau siswa punya histori (BK, absensi,
     * riwayat kelas, dll — hampir selalu ada untuk siswa aktif), cascade
     * FK lama akan MENGHAPUS SELURUH HISTORI itu secara diam-diam. Sejak
     * migrasi 2026_08_21_000001, FK yang bersangkutan sudah RESTRICT
     * (menolak, bukan mencascade), jadi sekarang dibungkus helper yang
     * sama seperti Kelas & Tahun Ajaran supaya errornya ditangkap ramah.
     *
     * Untuk siswa lulus/keluar sekolah, gunakan toggle "Aktif/Nonaktif"
     * di form edit (is_active) — BUKAN tombol hapus ini. Hapus hanya
     * untuk data yang benar-benar salah input & belum punya histori sama
     * sekali (STEP 4 Bagian 24).
     */
    public function destroy(Siswa $siswa)
    {
        return $this->hapusAtauGagalDenganPesan(
            $siswa,
            'Siswa berhasil dihapus.',
            'Siswa ini tidak dapat dihapus karena sudah memiliki data terkait (riwayat kelas, absensi, BK, atau data lain). Gunakan toggle nonaktifkan di form edit untuk siswa yang lulus/keluar.'
        );
    }

    public function importForm()
    {
        return view('siswa.import');
    }

    public function import(Request $request)
    {
        [$aturan, $pesan] = JalankanImport::aturanBerkas();
        $request->validate($aturan, $pesan);

        return JalankanImport::jalankan(new SiswaImport(), $request->file('file'), 'siswa.import.form');
    }

    public function template()
    {
        return Excel::download(new TemplateExport(
            ['nis', 'nisn', 'nama', 'nama_ortu', 'no_wa_ortu', 'jenis_kelamin', 'kode_kelas'],
            [
                ['2526001', '0091234567', 'Ahmad Fauzan', 'Bpk. Slamet', '081234567890', 'L', '7A'],
                ['2526002', '0091234568', 'Siti Aminah', 'Ibu Rahayu', '081234567891', 'P', '7A'],
            ],
            'Data Siswa',
            [
                'Petunjuk:',
                '- nis wajib diisi dan bersifat unik (tidak boleh sama dengan siswa lain).',
                '- nisn boleh dikosongkan jika belum ada.',
                '- nama_ortu boleh dikosongkan (dipakai untuk sapaan pada pesan WA).',
                '- no_wa_ortu diisi nomor WhatsApp aktif orang tua/wali, format bebas',
                '  (08xxx atau 62xxx), sistem akan merapikan otomatis. Kosongkan kalau',
                '  belum ada nomornya — siswa tsb tidak akan dikirimi notifikasi WA.',
                '- jenis_kelamin diisi L (Laki-laki) atau P (Perempuan).',
                '- kode_kelas diisi sesuai nama kelas pada menu Data Kelas UNTUK TAHUN AJARAN AKTIF (contoh: 7A). Siswa selalu diimpor ke kelas tahun ajaran yang sedang aktif.',
                '- Untuk NIS yang SUDAH ADA di sistem (siswa lama), import ini akan MEMINDAHKAN siswa tsb ke kode_kelas yang diisi (dipakai untuk naik kelas/pindah kelas di tahun ajaran baru). Riwayat kelasnya otomatis tercatat dan tetap bisa dilihat admin maupun orang tua siswa.',
                '- Hapus baris contoh ini sebelum mengisi data yang sebenarnya.',
            ]
        ), 'template-data-siswa.xlsx');
    }
}
