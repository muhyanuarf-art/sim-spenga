<?php

namespace App\Http\Controllers;

use App\Models\GuruMengajarKelas;
use App\Models\JadwalPelajaran;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TahunAjaranController extends Controller
{
    public function index()
    {
        $tahunAjaran = TahunAjaran::orderByDesc('id')->get();
        $periodeAktif = TahunAjaran::aktif();

        // Bagian 11/12 — kalau tombol ganti semester bisa dijalankan, cek
        // apakah Jadwal Semester 2 sudah tersedia, supaya bisa kasih info
        // ke admin ("belum ada, silakan salin dari Semester 1") TANPA
        // membuat/menyalin apa pun secara otomatis.
        $jadwalSemesterBerikutnyaTersedia = null;
        if ($periodeAktif && $periodeAktif->bisaGantiSemester()) {
            $berikutnya = $periodeAktif->semesterBerikutnya();
            $jadwalSemesterBerikutnyaTersedia = $berikutnya
                ? JadwalPelajaran::where('tahun_ajaran_id', $berikutnya->id)->exists()
                : null;
        }

        // STEP 4 Bagian 2/19 — info tahun ajaran berikutnya (dihitung dari
        // nama periode aktif, BUKAN dipilih bebas oleh admin) untuk kartu
        // "Buat Tahun Ajaran Baru" di halaman ini.
        $namaTahunAjaranBerikutnya = $periodeAktif
            ? TahunAjaran::namaTahunAjaranBerikutnya($periodeAktif->nama)
            : null;
        $tahunAjaranBerikutnyaSudahAda = $namaTahunAjaranBerikutnya
            ? TahunAjaran::where('nama', $namaTahunAjaranBerikutnya)->exists()
            : false;

        return view('tahun-ajaran.index', compact(
            'tahunAjaran', 'periodeAktif', 'jadwalSemesterBerikutnyaTersedia',
            'namaTahunAjaranBerikutnya', 'tahunAjaranBerikutnyaSudahAda'
        ));
    }

    /**
     * Aturan validasi tanggal & status (Bagian 3, 4, 6) yang dipakai
     * store() maupun update(). `status` sengaja TIDAK boleh diisi
     * 'aktif' lewat form ini — mengaktifkan periode hanya lewat
     * aktifkan() supaya constraint "hanya satu periode aktif" (Bagian 8)
     * tidak bisa dilanggar lewat jalan pintas edit form.
     *
     * STEP 4 — ditambah validasi unique(nama, semester): tanpa ini,
     * admin bisa tidak sengaja membuat dua baris "2027/2028 Ganjil" yang
     * membingungkan (semesterBerikutnya()/tahunAjaranBerikutnya() di
     * model mengasumsikan kombinasi nama+semester itu unik).
     */
    private function aturanValidasi(?int $ignoreId = null): array
    {
        return [
            'nama' => ['required', 'string', 'max:20'],
            'semester' => [
                'required',
                'in:Ganjil,Genap',
                Rule::unique('tahun_ajarans', 'semester')
                    ->where(fn ($q) => $q->where('nama', request('nama')))
                    ->ignore($ignoreId),
            ],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'status' => ['nullable', 'in:akan_datang,selesai'],
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->aturanValidasi());
        $validated['status'] = $validated['status'] ?? TahunAjaran::STATUS_AKAN_DATANG;
        TahunAjaran::create($validated);
        return back()->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }

    /**
     * STEP 4 Bagian 4 — "Buat Tahun Ajaran Baru" dalam SATU aksi: admin
     * cukup isi nama (+ tanggal opsional), sistem langsung membuat KEDUA
     * baris Semester 1 & Semester 2 sekaligus (bukan 2x submit form
     * tambah biasa). Status keduanya AKAN DATANG, is_active TETAP false
     * (Bagian 5 — tahun ajaran baru TIDAK langsung aktif).
     */
    public function buatTahunAjaranBaru(Request $request)
    {
        $validated = $request->validate([
            'nama' => [
                'required', 'string', 'max:20',
                Rule::unique('tahun_ajarans', 'nama'),
            ],
        ]);

        DB::transaction(function () use ($validated) {
            foreach (['Ganjil', 'Genap'] as $semester) {
                TahunAjaran::create([
                    'nama' => $validated['nama'],
                    'semester' => $semester,
                    'status' => TahunAjaran::STATUS_AKAN_DATANG,
                ]);
            }
        });

        return back()->with('success', "Tahun ajaran {$validated['nama']} berhasil dibuat (Semester 1 & Semester 2). Lengkapi tanggal, kenaikan kelas, wali kelas, dan jadwal sebelum mengaktifkannya.");
    }

    public function update(Request $request, TahunAjaran $tahunAjaran)
    {
        $validated = $request->validate($this->aturanValidasi($tahunAjaran->id));

        // Periode yang sedang AKTIF tidak boleh "dijatuhkan" statusnya lewat
        // form edit biasa — status aktif hanya boleh berubah lewat aktifkan().
        if ($tahunAjaran->is_active) {
            unset($validated['status']);
        } elseif (empty($validated['status'])) {
            $validated['status'] = $tahunAjaran->status;
        }

        $tahunAjaran->update($validated);
        return back()->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    /**
     * Bagian 8: pastikan HANYA SATU periode aktif dalam satu waktu.
     * Dibungkus transaksi supaya "matikan semua yang aktif" + "aktifkan
     * satu baris tujuan" tidak pernah berhenti di tengah jalan (mis. dua
     * baris sama-sama aktif kalau request terputus).
     *
     * STEP 4 Bagian 20/21 & Test 7 — ditambah 1 validasi PENTING: kalau
     * baris yang mau diaktifkan berasal dari TAHUN AJARAN (nama) yang
     * BERBEDA dari periode yang sedang aktif sekarang, berarti ini
     * PERGANTIAN TAHUN AJARAN (bukan cuma pindah semester dalam tahun
     * yang sama seperti STEP 3) — dan itu HANYA boleh kalau tahun ajaran
     * LAMA sudah selesai total (Ganjil & Genap-nya sama-sama terkunci).
     * Kalau baris tujuan masih dalam TAHUN AJARAN YANG SAMA (mis. dari
     * Semester 2 balik ke Semester 1 tahun yang sama, kasus langka tapi
     * tidak dilarang), validasi ini tidak berlaku — perilaku lama tetap
     * jalan seperti sebelum STEP 4.
     */
    public function aktifkan(TahunAjaran $tahunAjaran)
    {
        $periodeAktifSaatIni = TahunAjaran::aktif();

        if ($periodeAktifSaatIni && $periodeAktifSaatIni->nama !== $tahunAjaran->nama) {
            if (! TahunAjaran::semuaSemesterTerkunci($periodeAktifSaatIni->nama)) {
                return back()->with('error', "Tidak dapat memulai Tahun Ajaran {$tahunAjaran->nama}. Semester {$periodeAktifSaatIni->semester} Tahun Ajaran {$periodeAktifSaatIni->nama} masih berjalan — tutup & kunci dulu SELURUH semester tahun ajaran {$periodeAktifSaatIni->nama} sebelum mengaktifkan tahun ajaran berikutnya.");
            }
        }

        DB::transaction(function () use ($tahunAjaran) {
            // Periode lain yang sebelumnya berstatus 'aktif' otomatis jadi
            // 'selesai' karena is_active-nya dimatikan di baris yang sama —
            // ini konsekuensi langsung dari aksi manual admin menekan tombol
            // "Aktifkan", BUKAN penutupan/pergantian otomatis terjadwal
            // (yang memang belum dikerjakan, lingkup STEP berikutnya).
            TahunAjaran::where('is_active', true)
                ->where('status', TahunAjaran::STATUS_AKTIF)
                ->update(['is_active' => false, 'status' => TahunAjaran::STATUS_SELESAI]);
            TahunAjaran::where('is_active', true)->update(['is_active' => false]);

            $tahunAjaran->update([
                'is_active' => true,
                'status' => TahunAjaran::STATUS_AKTIF,
                'diaktifkan_at' => now(),
                'diaktifkan_oleh_id' => auth()->id(),
            ]);
        });

        return back()->with('success', "Tahun ajaran {$tahunAjaran->nama} {$tahunAjaran->semester} sekarang aktif.");
    }

    /** STEP 2 Bagian 15: periode yang sudah terkunci tidak boleh dihapus sama sekali. */
    public function destroy(TahunAjaran $tahunAjaran)
    {
        if ($tahunAjaran->isTerkunci()) {
            return back()->with('error', 'Periode ini sudah terkunci dan tidak dapat dihapus. Buka kunci terlebih dahulu (khusus Admin) jika benar-benar perlu dihapus.');
        }

        return $this->hapusAtauGagalDenganPesan(
            $tahunAjaran,
            'Tahun ajaran berhasil dihapus.',
            'Tahun ajaran ini tidak dapat dihapus karena masih memiliki data terkait (jadwal, mapping guru, atau data lain).'
        );
    }

    /**
     * STEP 2 — Tutup Semester (Bagian 5 & 6). Boleh Kurikulum & Admin (sama
     * seperti akses modul Tahun Ajaran lainnya). Menutup periode menandai
     * status = SELESAI + terkunci = true dalam 1 aksi atomik lewat
     * TahunAjaran::tutup() (dibungkus transaksi di sini karena berpotensi
     * dikembangkan lagi jadi beberapa perubahan sekaligus — Bagian 16).
     * Ini HANYA memblokir aksi tulis pada data yang periodenya = periode
     * ini (lewat App\Support\PeriodeAkademik::pastikanTidakTerkunci(),
     * dipanggil di controller modul terkait) — tidak menghapus/menyembunyikan
     * data apa pun, dan TIDAK mengaktifkan semester lain (Bagian 7 & 19).
     */
    public function kunci(TahunAjaran $tahunAjaran)
    {
        if ($tahunAjaran->isTerkunci()) {
            return back()->with('error', 'Periode ini sudah terkunci.');
        }

        DB::transaction(fn () => $tahunAjaran->tutup(auth()->user()));

        return back()->with('success', "Semester {$tahunAjaran->nama} {$tahunAjaran->semester} berhasil ditutup. Data transaksi pada periode ini sekarang hanya bisa dilihat, tidak bisa diubah.");
    }

    /**
     * STEP 2 — Buka Kembali (Bagian 10 & 11): KHUSUS Admin, bukan Kurikulum,
     * supaya membuka data historis tidak semudah tombol biasa. Mencatat
     * dibuka_at & dibuka_oleh_id (siapa & kapan) tanpa membuat tabel audit
     * log terpisah.
     */
    public function bukaKunci(TahunAjaran $tahunAjaran)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Hanya Admin yang dapat membuka kunci periode.');
        }

        if (! $tahunAjaran->isTerkunci()) {
            return back()->with('error', 'Periode ini tidak sedang terkunci.');
        }

        DB::transaction(fn () => $tahunAjaran->bukaKembali(auth()->user()));

        return back()->with('success', "Periode {$tahunAjaran->nama} {$tahunAjaran->semester} dibuka kembali. Data pada periode ini sekarang dapat diubah lagi oleh pengguna yang berhak.");
    }

    /**
     * STEP 3 — Tutup Semester & Aktifkan Semester Berikutnya (Bagian 1-5).
     *
     * $tahunAjaran = Semester Ganjil yang SEDANG AKTIF dan mau ditutup.
     * Semester tujuannya (Genap, tahun ajaran SAMA) dicari otomatis lewat
     * semesterBerikutnya() — bukan pergantian tahun ajaran (itu STEP 4).
     *
     * Validasi (Bagian 2 & 13) dan proses (Bagian 4) SEMUA dilakukan DI
     * DALAM 1 DB::transaction dengan row lock (lockForUpdate), supaya:
     * - tombol diklik dua kali / 2 request bersamaan (Bagian 4 & Test 6)
     *   tidak pernah menghasilkan dua periode aktif sekaligus atau
     *   setengah-proses (Semester 1 terkunci tapi Semester 2 belum aktif).
     * - validasi "apakah sudah diproses" memakai data TERBARU dari
     *   database saat transaksi berjalan, bukan data yang mungkin sudah
     *   basi dari saat halaman di-render.
     */
    public function gantiSemester(TahunAjaran $tahunAjaran)
    {
        // Validasi awal (Bagian 13) — dicek dulu di luar transaksi supaya
        // pesan error yang tepat bisa ditampilkan tanpa membuka transaksi
        // kalau memang sudah jelas tidak valid.
        if (! $tahunAjaran->is_active) {
            return back()->with('error', 'Periode ini bukan periode yang sedang aktif.');
        }

        if ($tahunAjaran->semester !== 'Ganjil') {
            return back()->with('error', 'Pergantian semester otomatis hanya tersedia dari Semester Ganjil ke Semester Genap dalam tahun ajaran yang sama. Pergantian ke tahun ajaran berikutnya akan dikerjakan pada tahap selanjutnya.');
        }

        $berikutnya = $tahunAjaran->semesterBerikutnya();

        if (! $berikutnya) {
            return back()->with('error', "Pergantian tidak dapat dilakukan. Semester Genap untuk tahun ajaran {$tahunAjaran->nama} belum tersedia — buat dulu di menu Tahun Ajaran.");
        }

        if ($berikutnya->is_active) {
            return back()->with('error', "{$berikutnya->labelPeriode()} sudah menjadi periode aktif. Tidak ada yang perlu dilakukan.");
        }

        try {
            DB::transaction(function () use ($tahunAjaran, $berikutnya) {
                // Kunci baris & baca ulang dari DB (bukan dari objek yang
                // mungkin sudah basi) — pertahanan terhadap klik dobel /
                // request bersamaan (Bagian 4, Test 6).
                $semester1 = TahunAjaran::whereKey($tahunAjaran->id)->lockForUpdate()->first();
                $semester2 = TahunAjaran::whereKey($berikutnya->id)->lockForUpdate()->first();

                if (! $semester1->is_active || $semester2->is_active) {
                    // Sudah diproses oleh request lain sebelum baris ini
                    // terkunci untuk kita — batalkan tanpa mengubah apa pun.
                    throw new \RuntimeException('GANTI_SEMESTER_SUDAH_DIPROSES');
                }

                // 1) Tutup & kunci Semester 1 (memakai mekanisme STEP 2,
                //    bukan mekanisme baru — Bagian 4 & instruksi umum).
                $semester1->tutup(auth()->user());

                // 2) Pastikan tidak ada baris lain yang masih is_active
                //    (jaga-jaga data tidak konsisten dari luar alur normal).
                TahunAjaran::where('is_active', true)->update(['is_active' => false]);

                // 3) Aktifkan Semester 2 + catat siapa/kapan (Bagian 16).
                $semester2->update([
                    'is_active' => true,
                    'status' => TahunAjaran::STATUS_AKTIF,
                    'diaktifkan_at' => now(),
                    'diaktifkan_oleh_id' => auth()->id(),
                ]);
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'GANTI_SEMESTER_SUDAH_DIPROSES') {
                return back()->with('error', 'Pergantian semester ini sudah diproses sebelumnya (kemungkinan tombol tertekan dua kali). Tidak ada perubahan ganda yang dilakukan.');
            }
            throw $e;
        }

        return back()->with('success', "Pergantian semester berhasil. Periode sebelumnya {$tahunAjaran->labelPeriode()} sekarang SELESAI/TERKUNCI. Periode aktif sekarang: {$berikutnya->labelPeriode()}.");
    }

    /**
     * Salin mapping Guru Mengajar & Jadwal Pelajaran dari tahun ajaran lama
     * ke tahun ajaran baru ($tahunAjaran = tujuan), supaya Kurikulum tidak
     * perlu input ulang dari nol setiap ganti tahun ajaran (sesuai prinsip
     * "data master dipakai ulang" — hanya kenaikan kelas & pengaturan
     * wali/jadwal per periode yang perlu disesuaikan manual kalau memang
     * berubah).
     *
     * Bersifat ADITIF & aman dijalankan berulang: baris yang kombinasi
     * uniknya sudah ada di tujuan otomatis dilewati (bukan error, bukan
     * duplikat), memakai firstOrCreate.
     */
    public function duplikasiMapping(Request $request, TahunAjaran $tahunAjaran)
    {
        $validated = $request->validate([
            'dari_tahun_ajaran_id' => ['required', 'exists:tahun_ajarans,id'],
        ]);

        if ((int) $validated['dari_tahun_ajaran_id'] === $tahunAjaran->id) {
            return back()->with('error', 'Tahun ajaran sumber tidak boleh sama dengan tahun ajaran tujuan.');
        }

        $sumber = TahunAjaran::findOrFail($validated['dari_tahun_ajaran_id']);

        $mengajarDisalin = 0;
        $mengajarDilewati = 0;
        $jadwalDisalin = 0;
        $jadwalDilewati = 0;

        DB::transaction(function () use ($sumber, $tahunAjaran, &$mengajarDisalin, &$mengajarDilewati, &$jadwalDisalin, &$jadwalDilewati) {
            foreach (GuruMengajarKelas::where('tahun_ajaran_id', $sumber->id)->get() as $mapping) {
                $baru = GuruMengajarKelas::firstOrCreate([
                    'tahun_ajaran_id' => $tahunAjaran->id,
                    'guru_id' => $mapping->guru_id,
                    'kelas_id' => $mapping->kelas_id,
                    'mata_pelajaran_id' => $mapping->mata_pelajaran_id,
                ]);
                $baru->wasRecentlyCreated ? $mengajarDisalin++ : $mengajarDilewati++;
            }

            foreach (JadwalPelajaran::where('tahun_ajaran_id', $sumber->id)->get() as $jadwal) {
                $baru = JadwalPelajaran::firstOrCreate([
                    'tahun_ajaran_id' => $tahunAjaran->id,
                    'hari' => $jadwal->hari,
                    'kelas_id' => $jadwal->kelas_id,
                    'jam_pelajaran_id' => $jadwal->jam_pelajaran_id,
                ], [
                    'mata_pelajaran_id' => $jadwal->mata_pelajaran_id,
                    'guru_id' => $jadwal->guru_id,
                ]);
                $baru->wasRecentlyCreated ? $jadwalDisalin++ : $jadwalDilewati++;
            }
        });

        $pesan = "Berhasil menyalin {$mengajarDisalin} mapping guru-mengajar dan {$jadwalDisalin} jadwal dari {$sumber->nama} {$sumber->semester} ke {$tahunAjaran->nama} {$tahunAjaran->semester}.";
        if ($mengajarDilewati > 0 || $jadwalDilewati > 0) {
            $pesan .= " ({$mengajarDilewati} mapping & {$jadwalDilewati} jadwal dilewati karena sudah ada di tujuan.)";
        }

        return back()->with('success', $pesan);
    }
}
