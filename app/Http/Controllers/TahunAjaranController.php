<?php

namespace App\Http\Controllers;

use App\Models\GuruMengajarKelas;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\RiwayatKelasSiswa;
use App\Models\Siswa;
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
        // STEP 8 — sekarang simpan barisnya (bukan cuma boolean), supaya
        // kartu ini bisa langsung mengarahkan admin ke halaman Persiapan
        // Tahun Ajaran (satu pintu, bukan "cari sendiri di tabel bawah").
        $tahunAjaranBerikutnya = $namaTahunAjaranBerikutnya
            ? TahunAjaran::where('nama', $namaTahunAjaranBerikutnya)->where('semester', 'Ganjil')->first()
            : null;
        $tahunAjaranBerikutnyaSudahAda = (bool) $tahunAjaranBerikutnya;

        return view('tahun-ajaran.index', compact(
            'tahunAjaran', 'periodeAktif', 'jadwalSemesterBerikutnyaTersedia',
            'namaTahunAjaranBerikutnya', 'tahunAjaranBerikutnyaSudahAda', 'tahunAjaranBerikutnya'
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
     * STEP 4 Bagian 3 & 4 — "Buat Tahun Ajaran Baru" dalam SATU aksi: admin
     * isi nama + tanggal mulai/selesai TAHUN AJARAN (bukan per semester),
     * sistem langsung membuat KEDUA baris Semester 1 & Semester 2
     * sekaligus. Status keduanya AKAN DATANG, is_active TETAP false
     * (Bagian 4/5 — tahun ajaran baru TIDAK langsung aktif).
     *
     * Tanggal mulai/selesai PER SEMESTER (mis. kapan tepatnya Semester 1
     * berakhir & Semester 2 mulai) SENGAJA TIDAK ditebak di sini — setiap
     * sekolah beda kalender liburnya. Tanggal yang admin isi dipasang di
     * ujung yang pasti benar (mulai tahun ajaran = mulai Semester 1,
     * selesai tahun ajaran = selesai Semester 2); titik tengahnya diisi
     * belakangan lewat form edit per-semester yang sudah ada (STEP 1).
     */
    public function buatTahunAjaranBaru(Request $request)
    {
        $validated = $request->validate([
            'nama' => [
                'required', 'string', 'max:20',
                Rule::unique('tahun_ajarans', 'nama'),
            ],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        $tahunAjaranGanjil = null;
        DB::transaction(function () use ($validated, &$tahunAjaranGanjil) {
            $tahunAjaranGanjil = TahunAjaran::create([
                'nama' => $validated['nama'],
                'semester' => 'Ganjil',
                'status' => TahunAjaran::STATUS_AKAN_DATANG,
                'tanggal_mulai' => $validated['tanggal_mulai'] ?? null,
            ]);
            TahunAjaran::create([
                'nama' => $validated['nama'],
                'semester' => 'Genap',
                'status' => TahunAjaran::STATUS_AKAN_DATANG,
                'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
            ]);
        });

        // STEP 8 Bagian 2/4 — langsung arahkan ke halaman Persiapan supaya
        // admin punya SATU alur berkelanjutan (bukan kembali ke tabel lalu
        // harus mencari sendiri tahun ajaran yang baru dibuat).
        return redirect()->route('tahun-ajaran.persiapan', $tahunAjaranGanjil)
            ->with('success', "Tahun ajaran {$validated['nama']} berhasil dibuat (Semester 1 & Semester 2, status Akan Datang). Lengkapi langkah-langkah di bawah sebelum mengaktifkannya.");
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
     * STEP 6 Bagian 9-13 & 22 — hitung APA SAJA yang akan disalin dari
     * $sumber ke $tujuan, TANPA menulis apa pun ke database. Dipakai
     * BERSAMA oleh preview (GET, tampil ke admin) dan eksekusi (POST,
     * benar-benar menyimpan) — supaya keduanya selalu konsisten (apa
     * yang di-preview PASTI itulah yang tersimpan, tidak ada logika
     * yang menyimpang antara preview & eksekusi).
     *
     * Bagian 13 (PENTING, ini pusat perbaikan STEP 6): kelas tujuan
     * dicari lewat (tingkat, nama_kelas) PADA TAHUN AJARAN TUJUAN — TIDAK
     * PERNAH memakai kelas_id dari tahun ajaran sumber apa adanya. Kalau
     * kelas dengan nama & tingkat yang sama belum ada di tujuan, baris
     * itu masuk daftar 'kelas_tidak_ada' (dilewati, BUKAN dibuat dengan
     * data tidak lengkap — Bagian 22).
     */
    private function resolveRencanaSalin(TahunAjaran $sumber, TahunAjaran $tujuan): array
    {
        $rencana = [
            'mengajar' => ['disalin' => [], 'sudah_ada' => [], 'kelas_tidak_ada' => []],
            'jadwal' => ['disalin' => [], 'sudah_ada' => [], 'kelas_tidak_ada' => []],
        ];

        // Cache pencarian kelas tujuan per (tingkat, nama_kelas) supaya tidak
        // query berulang untuk kelas yang sama dipakai banyak baris mapping/jadwal.
        $kelasTujuanCache = [];
        $cariKelasTujuan = function (Kelas $kelasSumber) use ($tujuan, &$kelasTujuanCache) {
            $key = $kelasSumber->tingkat.'|'.$kelasSumber->nama_kelas;
            if (! array_key_exists($key, $kelasTujuanCache)) {
                $kelasTujuanCache[$key] = Kelas::untukTahunAjaran($tujuan)
                    ->where('tingkat', $kelasSumber->tingkat)
                    ->where('nama_kelas', $kelasSumber->nama_kelas)
                    ->first();
            }
            return $kelasTujuanCache[$key];
        };

        foreach (GuruMengajarKelas::with(['guru', 'kelas', 'mapel'])->where('tahun_ajaran_id', $sumber->id)->get() as $m) {
            $kelasTujuan = $m->kelas ? $cariKelasTujuan($m->kelas) : null;

            if (! $kelasTujuan) {
                $rencana['mengajar']['kelas_tidak_ada'][] = $m;
                continue;
            }

            $sudahAda = GuruMengajarKelas::where('tahun_ajaran_id', $tujuan->id)
                ->where('guru_id', $m->guru_id)
                ->where('kelas_id', $kelasTujuan->id)
                ->where('mata_pelajaran_id', $m->mata_pelajaran_id)
                ->exists();

            $baris = ['sumber' => $m, 'kelas_tujuan' => $kelasTujuan];
            $rencana['mengajar'][$sudahAda ? 'sudah_ada' : 'disalin'][] = $baris;
        }

        foreach (JadwalPelajaran::with(['guru', 'kelas', 'mapel', 'jamPelajaran'])->where('tahun_ajaran_id', $sumber->id)->get() as $j) {
            $kelasTujuan = $j->kelas ? $cariKelasTujuan($j->kelas) : null;

            if (! $kelasTujuan) {
                $rencana['jadwal']['kelas_tidak_ada'][] = $j;
                continue;
            }

            $sudahAda = JadwalPelajaran::where('tahun_ajaran_id', $tujuan->id)
                ->where('hari', $j->hari)
                ->where('kelas_id', $kelasTujuan->id)
                ->where('jam_pelajaran_id', $j->jam_pelajaran_id)
                ->exists();

            $baris = ['sumber' => $j, 'kelas_tujuan' => $kelasTujuan];
            $rencana['jadwal'][$sudahAda ? 'sudah_ada' : 'disalin'][] = $baris;
        }

        return $rencana;
    }

    /**
     * STEP 6 Bagian 9 & 11 — PREVIEW sebelum menyalin apa pun (GET, tidak
     * mengubah database sama sekali). Menampilkan persis apa yang akan
     * disalin, apa yang dilewati karena sudah ada, dan apa yang dilewati
     * karena kelasnya belum tersedia di tahun ajaran tujuan (Bagian 22).
     */
    public function previewDuplikasiMapping(Request $request)
    {
        $validated = $request->validate([
            'dari_tahun_ajaran_id' => ['required', 'exists:tahun_ajarans,id'],
            'tahun_ajaran_tujuan' => ['required', 'exists:tahun_ajarans,id', 'different:dari_tahun_ajaran_id'],
        ]);

        $sumber = TahunAjaran::findOrFail($validated['dari_tahun_ajaran_id']);
        $tujuan = TahunAjaran::findOrFail($validated['tahun_ajaran_tujuan']);

        $rencana = $this->resolveRencanaSalin($sumber, $tujuan);

        return view('tahun-ajaran.preview-duplikasi', compact('sumber', 'tujuan', 'rencana'));
    }

    /**
     * STEP 6 — eksekusi salin (POST), memakai persis rencana yang sama
     * dengan preview() lewat resolveRencanaSalin(), dibungkus 1
     * DB::transaction (Bagian 23) supaya tidak pernah tersalin
     * sebagian saja kalau terjadi error di tengah proses.
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
        $rencana = $this->resolveRencanaSalin($sumber, $tahunAjaran);

        $mengajarDisalin = 0;
        $jadwalDisalin = 0;

        DB::transaction(function () use ($tahunAjaran, $rencana, &$mengajarDisalin, &$jadwalDisalin) {
            foreach ($rencana['mengajar']['disalin'] as $baris) {
                // firstOrCreate (bukan create polos) — pertahanan tambahan
                // terhadap race condition kalau ada proses salin lain yang
                // berjalan bersamaan di antara preview dan eksekusi ini.
                $baru = GuruMengajarKelas::firstOrCreate([
                    'tahun_ajaran_id' => $tahunAjaran->id,
                    'guru_id' => $baris['sumber']->guru_id,
                    'kelas_id' => $baris['kelas_tujuan']->id,
                    'mata_pelajaran_id' => $baris['sumber']->mata_pelajaran_id,
                ]);
                if ($baru->wasRecentlyCreated) {
                    $mengajarDisalin++;
                }
            }

            foreach ($rencana['jadwal']['disalin'] as $baris) {
                $baru = JadwalPelajaran::firstOrCreate([
                    'tahun_ajaran_id' => $tahunAjaran->id,
                    'hari' => $baris['sumber']->hari,
                    'kelas_id' => $baris['kelas_tujuan']->id,
                    'jam_pelajaran_id' => $baris['sumber']->jam_pelajaran_id,
                ], [
                    'mata_pelajaran_id' => $baris['sumber']->mata_pelajaran_id,
                    'guru_id' => $baris['sumber']->guru_id,
                ]);
                if ($baru->wasRecentlyCreated) {
                    $jadwalDisalin++;
                }
            }
        });

        $mengajarDilewati = count($rencana['mengajar']['sudah_ada']);
        $jadwalDilewati = count($rencana['jadwal']['sudah_ada']);
        $kelasTidakAda = count($rencana['mengajar']['kelas_tidak_ada']) + count($rencana['jadwal']['kelas_tidak_ada']);

        $pesan = "Berhasil menyalin {$mengajarDisalin} mapping guru-mengajar dan {$jadwalDisalin} jadwal dari {$sumber->nama} {$sumber->semester} ke {$tahunAjaran->nama} {$tahunAjaran->semester}.";
        if ($mengajarDilewati > 0 || $jadwalDilewati > 0) {
            $pesan .= " ({$mengajarDilewati} mapping & {$jadwalDilewati} jadwal dilewati karena sudah ada di tujuan.)";
        }
        if ($kelasTidakAda > 0) {
            $pesan .= " {$kelasTidakAda} baris DILEWATI karena kelasnya belum tersedia di {$tahunAjaran->nama} — buat dulu kelasnya di menu Data Kelas lalu ulangi salin.";
        }

        return redirect()->route('tahun-ajaran.index')->with($mengajarDisalin + $jadwalDisalin > 0 ? 'success' : 'error', $pesan);
    }

    /**
     * STEP 8 Bagian 2/5/6/11-15 — HALAMAN UTAMA "Persiapan Tahun Ajaran
     * Baru". Mengumpulkan status semua langkah persiapan dalam SATU
     * halaman (Bagian 21: hindari redundansi menu — admin tidak perlu
     * membuka banyak halaman terpisah hanya untuk tahu apa yang belum
     * selesai) memakai data yang SUDAH ADA (Bagian 26: jangan buat
     * service/logika duplikat) — tidak ada tabel/kolom baru.
     *
     * PENTING (Bagian 6): checklist ini HANYA PANDUAN, bukan syarat wajib
     * — tombol Aktifkan tetap bisa ditekan berapa pun status checklist-nya
     * (aktifkan() di STEP4 sudah menegakkan syarat yang BENAR-BENAR wajib:
     * tahun ajaran lama harus terkunci penuh). $tahunAjaran = baris
     * Semester Ganjil tahun yang mau disiapkan.
     */
    public function persiapan(TahunAjaran $tahunAjaran)
    {
        $semesterGenap = TahunAjaran::where('nama', $tahunAjaran->nama)->where('semester', 'Genap')->first();

        $kelasList = Kelas::untukTahunAjaran($tahunAjaran)->withCount('siswas')->with('waliKelas')->orderBy('tingkat')->orderBy('nama_kelas')->get();
        $jumlahKelas = $kelasList->count();
        $jumlahSiswaDitempatkan = $kelasList->sum('siswas_count');

        // Bagian 11 — Status Kenaikan Kelas per kelas ASAL (tahun sebelumnya),
        // supaya admin tahu persis kelas mana yang belum selesai diproses.
        $tahunSebelumnya = $tahunAjaran->tahunAjaranSebelumnya();
        $statusKenaikan = collect();
        if ($tahunSebelumnya) {
            $kelasAsalList = Kelas::untukTahunAjaran($tahunSebelumnya)->orderBy('tingkat')->orderBy('nama_kelas')->get();
            foreach ($kelasAsalList as $kelasAsal) {
                $totalSiswa = $kelasAsal->siswas()->where('is_active', true)->count();
                $sudahDiproses = RiwayatKelasSiswa::where('tahun_ajaran_id', $tahunAjaran->id)
                    ->whereIn('siswa_id', $kelasAsal->siswas()->where('is_active', true)->pluck('id'))
                    ->count();
                $statusKenaikan->push([
                    'kelas' => $kelasAsal,
                    'total' => $totalSiswa,
                    'sudah' => $sudahDiproses,
                    'belum' => max(0, $totalSiswa - $sudahDiproses),
                ]);
            }
        }
        $totalSiswaAsal = $statusKenaikan->sum('total');
        $totalBelumDiproses = $statusKenaikan->sum('belum');

        // Bagian 12 — status Wali Kelas per kelas TUJUAN.
        $kelasBelumWali = $kelasList->filter(fn ($k) => ! $k->wali_kelas_id)->count();

        // Bagian 13 — status Guru Mengajar: berapa dari kelas tujuan yang
        // SUDAH punya minimal 1 mapping guru mengajar.
        $kelasDenganMengajar = $jumlahKelas > 0
            ? GuruMengajarKelas::where('tahun_ajaran_id', $tahunAjaran->id)->distinct('kelas_id')->count('kelas_id')
            : 0;
        $totalMappingMengajar = GuruMengajarKelas::where('tahun_ajaran_id', $tahunAjaran->id)->count();

        // Bagian 14 — status Jadwal: sekadar ada/tidak (bukan lengkap 100%,
        // supaya tidak memaksakan definisi "lengkap" yang belum tentu benar).
        $jadwalTersedia = JadwalPelajaran::where('tahun_ajaran_id', $tahunAjaran->id)->exists();

        // Bagian 6 — pisahkan WAJIB vs DIBUTUHKAN vs PERSIAPAN. Status
        // keseluruhan HANYA dipengaruhi tahap WAJIB (yang sudah pasti
        // terpenuhi karena baris ini sendiri ada) — tahap lain sekadar info.
        $siapDiaktifkan = ! $tahunAjaran->is_active; // satu-satunya syarat teknis nyata: belum aktif

        return view('tahun-ajaran.persiapan', compact(
            'tahunAjaran', 'semesterGenap', 'kelasList', 'jumlahKelas', 'jumlahSiswaDitempatkan',
            'tahunSebelumnya', 'statusKenaikan', 'totalSiswaAsal', 'totalBelumDiproses',
            'kelasBelumWali', 'kelasDenganMengajar', 'totalMappingMengajar', 'jadwalTersedia', 'siapDiaktifkan'
        ));
    }
}
