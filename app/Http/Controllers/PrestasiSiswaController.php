<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\PrestasiSiswa;
use App\Models\Siswa;
use App\Support\KonteksPeriode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * PRESTASI SISWA — satu halaman untuk mencatat, memverifikasi, dan merekap.
 *
 * =====================================================================
 * SIAPA BOLEH APA
 * =====================================================================
 *   kesiswaan, admin  : melihat semua, mencatat, mengubah, menghapus,
 *                       dan memverifikasi.
 *   guru (wali kelas) : melihat & mencatat HANYA untuk siswa kelasnya
 *                       sendiri; boleh mengubah/menghapus catatannya
 *                       selama BELUM diverifikasi.
 *   kepala_sekolah,
 *   kurikulum, guru_bk: melihat semua, tanpa mengubah apa pun.
 *
 * Alasan wali kelas ikut menulis ada di migrasi tabelnya: yang tahu
 * seorang siswa juara adalah wali kelasnya, dan selama hanya kesiswaan
 * yang boleh mengetik, setiap prestasi harus melewati satu perantara —
 * di situlah selama ini datanya hilang.
 *
 * Yang membuat wali kelas tidak bisa merusak laporan: begitu kesiswaan
 * memverifikasi sebuah catatan, catatan itu terkunci baginya. Catatan
 * yang sudah dipakai untuk laporan resmi karena itu tidak bisa berubah
 * diam-diam.
 */
class PrestasiSiswaController extends Controller
{
    /** Peran yang boleh mengubah data siapa pun & memverifikasi. */
    private const PENGELOLA = ['kesiswaan', 'admin'];

    public function index(Request $request)
    {
        $user = $request->user();
        $periode = KonteksPeriode::pilihan();

        $kelasTerpilih = $request->integer('kelas_id') ?: null;
        $kelasBoleh = $this->kelasYangBoleh($request);

        $daftar = PrestasiSiswa::query()
            ->with(['siswa.kelas', 'tahunAjaran', 'pencatat', 'verifikator'])
            ->when(
                // Wali kelas hanya melihat kelasnya sendiri. Dipasang
                // lebih dulu daripada filter pilihan pengguna, jadi
                // memaksa kelas_id lain lewat alamat tidak menembusnya.
                ! $this->bolehKelola($request) && $kelasBoleh !== null,
                fn ($q) => $q->whereHas('siswa', fn ($s) => $s->diKelasIn($kelasBoleh))
            )
            ->when($kelasTerpilih, fn ($q) => $q->whereHas('siswa', fn ($s) => $s->diKelas($kelasTerpilih)))
            ->when($request->filled('bidang'), fn ($q) => $q->where('bidang', $request->bidang))
            ->when($request->filled('tingkat'), fn ($q) => $q->where('tingkat', $request->tingkat))
            ->when($request->status === 'belum', fn ($q) => $q->belumDiverifikasi())
            ->when($request->status === 'sudah', fn ($q) => $q->terverifikasi())
            ->when($request->filled('cari'), function ($q) use ($request) {
                $kata = $request->cari;
                $q->where(function ($w) use ($kata) {
                    $w->where('nama', 'like', "%{$kata}%")
                      ->orWhere('penyelenggara', 'like', "%{$kata}%")
                      ->orWhereHas('siswa', fn ($s) => $s->where('nama', 'like', "%{$kata}%")
                                                          ->orWhere('nis', 'like', "%{$kata}%"));
                });
            })
            ->urutanBaku()
            ->paginate(25)
            ->withQueryString();

        // Angka ringkas di atas tabel — memakai batasan akses yang sama
        // supaya wali kelas melihat hitungan kelasnya, bukan sekolah.
        $dasarHitung = PrestasiSiswa::query()->when(
            ! $this->bolehKelola($request) && $kelasBoleh !== null,
            fn ($q) => $q->whereHas('siswa', fn ($s) => $s->diKelasIn($kelasBoleh))
        );

        $ringkasan = [
            'total' => (clone $dasarHitung)->count(),
            'belum' => (clone $dasarHitung)->belumDiverifikasi()->count(),
            'tahun_ini' => $periode
                ? (clone $dasarHitung)->where('tahun_ajaran_id', $periode->id)->count()
                : 0,
        ];

        return view('prestasi.index', [
            'daftar' => $daftar,
            'ringkasan' => $ringkasan,
            'periode' => $periode,
            'daftarKelas' => $this->daftarKelasUntukFilter($request),
            'siswaPilihan' => $this->siswaYangBolehDicatat($request),
            'bolehKelola' => $this->bolehKelola($request),
            'bolehCatat' => $this->bolehCatat($request),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($this->bolehCatat($request), 403, 'Anda tidak berhak mencatat prestasi.');

        $data = $this->validasi($request);

        $siswa = Siswa::findOrFail($data['siswa_id']);
        $this->pastikanSiswaBoleh($request, $siswa);

        $data['dicatat_oleh'] = $request->user()->id;
        $data['tahun_ajaran_id'] = KonteksPeriode::pilihan()?->id;
        $data['sertifikat_path'] = $this->simpanSertifikat($request);

        // Dicatat kesiswaan berarti sudah dipastikan saat itu juga —
        // meminta mereka menekan tombol verifikasi atas catatannya
        // sendiri hanya pekerjaan dua kali.
        if ($this->bolehKelola($request)) {
            $data['diverifikasi_at'] = now();
            $data['diverifikasi_oleh'] = $request->user()->id;
        }

        PrestasiSiswa::create($data);

        return back()->with('success', 'Prestasi '.$siswa->nama.' berhasil dicatat.');
    }

    public function update(Request $request, PrestasiSiswa $prestasi)
    {
        $this->pastikanBolehUbah($request, $prestasi);

        $data = $this->validasi($request);

        $siswa = Siswa::findOrFail($data['siswa_id']);
        $this->pastikanSiswaBoleh($request, $siswa);

        if ($baru = $this->simpanSertifikat($request)) {
            $this->hapusSertifikat($prestasi);
            $data['sertifikat_path'] = $baru;
        }

        $prestasi->update($data);

        return back()->with('success', 'Prestasi berhasil diperbarui.');
    }

    public function destroy(Request $request, PrestasiSiswa $prestasi)
    {
        $this->pastikanBolehUbah($request, $prestasi);

        $this->hapusSertifikat($prestasi);
        $prestasi->delete();

        return back()->with('success', 'Prestasi berhasil dihapus.');
    }

    /**
     * Verifikasi satu klik. Menekan lagi pada catatan yang sudah
     * terverifikasi akan MENCABUT verifikasinya — satu tombol untuk dua
     * arah, karena keliru memverifikasi harus bisa dibatalkan tanpa
     * mencari menu lain.
     */
    public function verifikasi(Request $request, PrestasiSiswa $prestasi)
    {
        abort_unless($this->bolehKelola($request), 403, 'Hanya Kesiswaan yang dapat memverifikasi prestasi.');

        if ($prestasi->sudahDiverifikasi()) {
            $prestasi->update(['diverifikasi_at' => null, 'diverifikasi_oleh' => null]);

            return back()->with('success', 'Verifikasi dicabut. Catatan ini kembali bisa diubah wali kelas.');
        }

        $prestasi->update([
            'diverifikasi_at' => now(),
            'diverifikasi_oleh' => $request->user()->id,
        ]);

        return back()->with('success', 'Prestasi terverifikasi dan siap dipakai untuk laporan.');
    }

    // =================================================================
    // Penjaga akses
    // =================================================================

    private function bolehKelola(Request $request): bool
    {
        return in_array($request->user()->role, self::PENGELOLA, true);
    }

    /** Kesiswaan/admin, atau guru yang sedang menjadi wali kelas. */
    private function bolehCatat(Request $request): bool
    {
        $user = $request->user();

        return $this->bolehKelola($request)
            || ($user->role === 'guru' && $user->kelasWali !== null);
    }

    /**
     * Id kelas yang boleh dilihat pengguna ini, atau null bila ia boleh
     * melihat seluruh sekolah.
     */
    private function kelasYangBoleh(Request $request): ?array
    {
        $user = $request->user();

        if ($user->role === 'guru') {
            return $user->kelasWali ? [$user->kelasWali->id] : [];
        }

        return null;
    }

    private function pastikanSiswaBoleh(Request $request, Siswa $siswa): void
    {
        $kelasBoleh = $this->kelasYangBoleh($request);

        if ($kelasBoleh === null) {
            return;
        }

        abort_unless(
            in_array($siswa->kelasIdSekarang(), $kelasBoleh, true),
            403,
            'Anda hanya dapat mencatat prestasi siswa di kelas yang Anda ampu.'
        );
    }

    /**
     * Boleh mengubah/menghapus? Kesiswaan selalu boleh. Wali kelas hanya
     * selama catatannya BELUM diverifikasi — begitu kesiswaan memastikan
     * dan memakainya untuk laporan, catatan itu tidak boleh lagi berubah
     * tanpa sepengetahuannya.
     */
    private function pastikanBolehUbah(Request $request, PrestasiSiswa $prestasi): void
    {
        if ($this->bolehKelola($request)) {
            return;
        }

        abort_unless($this->bolehCatat($request), 403, 'Anda tidak berhak mengubah prestasi.');

        $prestasi->loadMissing('siswa');
        $this->pastikanSiswaBoleh($request, $prestasi->siswa);

        abort_if(
            $prestasi->sudahDiverifikasi(),
            403,
            'Prestasi ini sudah diverifikasi Kesiswaan sehingga tidak dapat diubah lagi. '
            .'Hubungi Kesiswaan bila ada yang keliru.'
        );
    }

    // =================================================================
    // Bahan tampilan
    // =================================================================

    private function daftarKelasUntukFilter(Request $request)
    {
        $kelasBoleh = $this->kelasYangBoleh($request);

        return Kelas::aktif()
            ->when($kelasBoleh !== null, fn ($q) => $q->whereIn('id', $kelasBoleh))
            ->orderBy('nama_kelas')
            ->get();
    }

    /**
     * Siswa yang boleh dipilih di formulir. Wali kelas hanya melihat
     * kelasnya sendiri, jadi daftarnya pendek dan tidak perlu dicari —
     * inilah yang membuat pencatatan terasa ringan baginya.
     */
    private function siswaYangBolehDicatat(Request $request)
    {
        if (! $this->bolehCatat($request)) {
            return collect();
        }

        $kelasBoleh = $this->kelasYangBoleh($request);

        return Siswa::query()
            ->where('is_active', true)
            ->when($kelasBoleh !== null, fn ($q) => $q->diKelasIn($kelasBoleh))
            ->with('kelas')
            ->orderBy('nama')
            ->get();
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'siswa_id' => ['required', 'exists:siswas,id'],
            'nama' => ['required', 'string', 'max:255'],
            'bidang' => ['required', Rule::in(array_keys(PrestasiSiswa::BIDANG))],
            'tingkat' => ['required', Rule::in(array_keys(PrestasiSiswa::TINGKAT))],
            'peringkat' => ['required', Rule::in(array_keys(PrestasiSiswa::PERINGKAT))],
            'penyelenggara' => ['nullable', 'string', 'max:255'],
            'tanggal' => ['required', 'date'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'sertifikat' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ], [
            'siswa_id.required' => 'Siswa belum dipilih.',
            'nama.required' => 'Nama prestasi/lomba belum diisi.',
            'tanggal.required' => 'Tanggal belum diisi.',
            'sertifikat.mimes' => 'Sertifikat harus berupa JPG, PNG, atau PDF.',
            'sertifikat.max' => 'Ukuran sertifikat maksimal 4 MB.',
        ]);
    }

    /**
     * Sertifikat disimpan di cakram 'local' (storage/app/private), bukan
     * 'public' — berkas bernama siswa tidak boleh terbuka bagi siapa pun
     * yang kebetulan memegang alamatnya. Disajikan lewat
     * App\Http\Controllers\BerkasTerlindungiController.
     */
    private function simpanSertifikat(Request $request): ?string
    {
        if (! $request->hasFile('sertifikat')) {
            return null;
        }

        return $request->file('sertifikat')->store('prestasi/sertifikat');
    }

    private function hapusSertifikat(PrestasiSiswa $prestasi): void
    {
        if ($prestasi->sertifikat_path) {
            Storage::disk('local')->delete($prestasi->sertifikat_path);
        }
    }
}
