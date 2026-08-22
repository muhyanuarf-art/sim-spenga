<?php

namespace App\Http\Controllers;

use App\Models\GuruBkKelas;
use App\Models\GuruMengajarKelas;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Support\PeriodeAkademik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TahunAjaranController extends Controller
{
    public function index()
    {
        $tahunAjaran = TahunAjaran::orderByDesc('id')->get();
        $periodeAktif = TahunAjaran::aktif();

        // (revisi permintaan admin) — Bagian 4: kalau sebuah Tahun Ajaran
        // baru punya SATU semester (mis. admin hanya sempat menambah
        // Ganjil), tabel di bawah menampilkan tombol "+ Tambah Semester"
        // untuk semester yang masih kurang. Maksimal 2 semester per tahun
        // ajaran (Ganjil & Genap saja).
        $semesterHilangPerNama = $tahunAjaran->groupBy('nama')
            ->filter(fn ($rows) => $rows->count() === 1)
            ->map(fn ($rows) => $rows->first()->semester === 'Ganjil' ? 'Genap' : 'Ganjil');

        // Info tahun ajaran berikutnya (dihitung dari nama periode aktif,
        // BUKAN dipilih bebas oleh admin) — hanya dipakai untuk menyembunyikan
        // kartu "+ Buat Tahun Ajaran Baru" begitu tahun berikutnya sudah ada
        // (row-nya sendiri sudah otomatis tampil di tabel).
        $namaTahunAjaranBerikutnya = $periodeAktif
            ? TahunAjaran::namaTahunAjaranBerikutnya($periodeAktif->nama)
            : null;
        $tahunAjaranBerikutnyaSudahAda = $namaTahunAjaranBerikutnya
            ? TahunAjaran::where('nama', $namaTahunAjaranBerikutnya)->exists()
            : false;

        return view('tahun-ajaran.index', compact(
            'tahunAjaran', 'periodeAktif', 'semesterHilangPerNama',
            'namaTahunAjaranBerikutnya', 'tahunAjaranBerikutnyaSudahAda'
        ));
    }

    /**
     * Aturan validasi (Bagian 3, 4, 6) yang dipakai store() maupun
     * update(). `status` sengaja TIDAK boleh diisi 'aktif' lewat form
     * ini — mengaktifkan periode hanya lewat aktifkan() supaya constraint
     * "hanya satu periode aktif" (Bagian 8) tidak bisa dilanggar lewat
     * jalan pintas edit form.
     *
     * (Revisi permintaan admin) tanggal_mulai/tanggal_selesai DIHAPUS dari
     * form — kolomnya tetap ada di database tapi tidak dipakai validasi
     * apa pun, jadi tidak lagi ditanyakan di UI supaya lebih sederhana.
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
     * (Revisi permintaan admin) Tanggal mulai/selesai DIHAPUS dari form
     * ini — tidak dipakai validasi apa pun, jadi tidak perlu ditanyakan
     * di awal supaya alurnya lebih singkat.
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
            TahunAjaran::create([
                'nama' => $validated['nama'],
                'semester' => 'Ganjil',
                'status' => TahunAjaran::STATUS_AKAN_DATANG,
            ]);
            TahunAjaran::create([
                'nama' => $validated['nama'],
                'semester' => 'Genap',
                'status' => TahunAjaran::STATUS_AKAN_DATANG,
            ]);
        });

        return back()->with('success', "Tahun ajaran {$validated['nama']} berhasil dibuat (Semester Ganjil & Genap, status Akan Datang).");
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

        TahunAjaran::lupakanCacheAktif();

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

    // NOTE (revisi permintaan admin): method gantiSemester() (tombol gabungan
    // "Tutup Semester X & Aktifkan Semester Y") DIHAPUS dari sini karena
    // dianggap membingungkan admin. Alur pergantian semester sekarang murni
    // 2 langkah terpisah yang sudah ada masing-masing sebagai tombol sendiri
    // di tabel: "Tutup Semester" (kunci()) lalu "Aktifkan" (aktifkan())
    // pada baris semester berikutnya — TIDAK ADA mekanisme kedua yang
    // menggabungkan keduanya secara otomatis.

    /**
     * STEP 9 (permintaan admin) — "Salin Data" TERPADU: Kelas + Wali Kelas
     * + Guru Mengajar + Jadwal dalam SATU alur, bukan lagi fitur terpisah
     * (sebelumnya "Salin Struktur Kelas" di menu Data Kelas dan "Salin
     * Mapping Guru/Jadwal" di menu Tahun Ajaran adalah 2 tombol berbeda —
     * sekarang digabung jadi 1 "Salin Data" per baris semester).
     *
     * Hitung APA SAJA yang akan disalin dari $sumber ke $tujuan, TANPA
     * menulis apa pun ke database. Dipakai BERSAMA oleh preview (GET,
     * tampil ke admin) dan eksekusi (POST) — supaya keduanya selalu
     * konsisten.
     *
     * Kalau $sumber & $tujuan TAHUN AJARANNYA SAMA (cuma beda semester,
     * mis. Ganjil→Genap): kelas TIDAK disalin sama sekali karena memang
     * SUDAH kelas yang sama persis (STEP5 — kelas melekat ke tahun
     * ajaran, dipakai lintas semester). Kalau BEDA tahun ajaran: kelas
     * (+ wali_kelas_id sebagai titik awal) ikut disalin, dicari lewat
     * (tingkat, nama_kelas) — TIDAK PERNAH memakai kelas_id sumber apa
     * adanya.
     */
    private function resolveRencanaSalin(TahunAjaran $sumber, TahunAjaran $tujuan): array
    {
        $rencana = [
            'kelas' => ['disalin' => [], 'sudah_ada' => []],
            'mengajar' => ['disalin' => [], 'sudah_ada' => []],
            'guru_bk' => ['disalin' => [], 'sudah_ada' => []],
            'jadwal' => ['disalin' => [], 'sudah_ada' => []],
        ];

        $tahunSama = $sumber->nama === $tujuan->nama;

        $kelasSumberMap = [];
        foreach (Kelas::untukTahunAjaran($sumber)->orderBy('tingkat')->orderBy('nama_kelas')->get() as $k) {
            $kelasSumberMap[$k->tingkat.'|'.$k->nama_kelas] = $k;
        }
        $kelasTujuanMap = [];
        foreach (Kelas::untukTahunAjaran($tujuan)->get() as $k) {
            $kelasTujuanMap[$k->tingkat.'|'.$k->nama_kelas] = $k;
        }

        if (! $tahunSama) {
            foreach ($kelasSumberMap as $key => $kelasSumber) {
                if (isset($kelasTujuanMap[$key])) {
                    $rencana['kelas']['sudah_ada'][] = ['sumber' => $kelasSumber, 'tujuan' => $kelasTujuanMap[$key]];
                } else {
                    $rencana['kelas']['disalin'][] = ['sumber' => $kelasSumber];
                }
            }
        }

        foreach (GuruMengajarKelas::with(['guru', 'kelas', 'mapel'])->where('tahun_ajaran_id', $sumber->id)->get() as $m) {
            if (! $m->kelas) {
                continue;
            }
            $key = $m->kelas->tingkat.'|'.$m->kelas->nama_kelas;
            $kelasTujuanReal = $kelasTujuanMap[$key] ?? null;
            $kelasLabel = $kelasTujuanReal ?? $m->kelas; // untuk tampilan preview kalau belum ada
            $sudahAda = $kelasTujuanReal && GuruMengajarKelas::where('tahun_ajaran_id', $tujuan->id)
                ->where('guru_id', $m->guru_id)->where('kelas_id', $kelasTujuanReal->id)
                ->where('mata_pelajaran_id', $m->mata_pelajaran_id)->exists();

            $rencana['mengajar'][$sudahAda ? 'sudah_ada' : 'disalin'][] = [
                'sumber' => $m, 'kelas_tujuan' => $kelasLabel, 'kelas_baru' => is_null($kelasTujuanReal),
            ];
        }

        foreach (GuruBkKelas::with(['guru', 'kelas'])->where('tahun_ajaran_id', $sumber->id)->get() as $gb) {
            if (! $gb->kelas) {
                continue;
            }
            $key = $gb->kelas->tingkat.'|'.$gb->kelas->nama_kelas;
            $kelasTujuanReal = $kelasTujuanMap[$key] ?? null;
            $kelasLabel = $kelasTujuanReal ?? $gb->kelas;
            $sudahAda = $kelasTujuanReal && GuruBkKelas::where('tahun_ajaran_id', $tujuan->id)
                ->where('guru_id', $gb->guru_id)->where('kelas_id', $kelasTujuanReal->id)->exists();

            $rencana['guru_bk'][$sudahAda ? 'sudah_ada' : 'disalin'][] = [
                'sumber' => $gb, 'kelas_tujuan' => $kelasLabel, 'kelas_baru' => is_null($kelasTujuanReal),
            ];
        }

        foreach (JadwalPelajaran::with(['guru', 'kelas', 'mapel', 'jamPelajaran'])->where('tahun_ajaran_id', $sumber->id)->get() as $j) {
            if (! $j->kelas) {
                continue;
            }
            $key = $j->kelas->tingkat.'|'.$j->kelas->nama_kelas;
            $kelasTujuanReal = $kelasTujuanMap[$key] ?? null;
            $kelasLabel = $kelasTujuanReal ?? $j->kelas;
            $sudahAda = $kelasTujuanReal && JadwalPelajaran::where('tahun_ajaran_id', $tujuan->id)
                ->where('hari', $j->hari)->where('kelas_id', $kelasTujuanReal->id)
                ->where('jam_pelajaran_id', $j->jam_pelajaran_id)->exists();

            $rencana['jadwal'][$sudahAda ? 'sudah_ada' : 'disalin'][] = [
                'sumber' => $j, 'kelas_tujuan' => $kelasLabel, 'kelas_baru' => is_null($kelasTujuanReal),
            ];
        }

        return $rencana;
    }

    /**
     * PREVIEW sebelum menyalin apa pun (GET, tidak mengubah database sama
     * sekali). Menampilkan persis: kelas apa yang akan dibuat, mapping
     * guru mengajar & jadwal apa yang akan disalin (termasuk yang kelasnya
     * BELUM ADA — karena sekarang otomatis akan dibuat lebih dulu dalam
     * aksi yang sama), dan apa yang dilewati karena sudah ada.
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
     * Eksekusi "Salin Data" (POST), dibungkus 1 DB::transaction supaya
     * tidak pernah tersalin sebagian saja kalau terjadi error di tengah
     * proses. Urutan PENTING: Kelas (+Wali Kelas) disalin LEBIH DULU,
     * baru KEMUDIAN rencana Guru Mengajar & Jadwal dihitung ULANG (kelas
     * tujuannya sekarang sudah pasti ada) — supaya tidak ada lagi baris
     * yang "dilewati karena kelas belum tersedia" seperti sebelumnya.
     */
    public function duplikasiMapping(Request $request, TahunAjaran $tahunAjaran)
    {
        $validated = $request->validate([
            'dari_tahun_ajaran_id' => ['required', 'exists:tahun_ajarans,id'],
        ]);

        if ((int) $validated['dari_tahun_ajaran_id'] === $tahunAjaran->id) {
            return back()->with('error', 'Tahun ajaran/semester sumber tidak boleh sama dengan tujuan.');
        }

        $sumber = TahunAjaran::findOrFail($validated['dari_tahun_ajaran_id']);

        // STEP 5/8 Bagian 8/26 — cek lock berdasarkan periode TUJUAN.
        PeriodeAkademik::pastikanTidakTerkunci($tahunAjaran);

        $kelasDisalin = 0;
        $mengajarDisalin = 0;
        $guruBkDisalin = 0;
        $jadwalDisalin = 0;

        DB::transaction(function () use ($sumber, $tahunAjaran, &$kelasDisalin, &$mengajarDisalin, &$guruBkDisalin, &$jadwalDisalin) {
            $rencanaAwal = $this->resolveRencanaSalin($sumber, $tahunAjaran);

            foreach ($rencanaAwal['kelas']['disalin'] as $baris) {
                $k = $baris['sumber'];
                $baru = Kelas::firstOrCreate(
                    ['tahun_ajaran_id' => $tahunAjaran->id, 'tingkat' => $k->tingkat, 'nama_kelas' => $k->nama_kelas],
                    ['wali_kelas_id' => $k->wali_kelas_id]
                );
                if ($baru->wasRecentlyCreated) {
                    $kelasDisalin++;
                }
            }

            // Hitung ulang SETELAH kelas dibuat — kelas_tujuan sekarang selalu baris nyata.
            $rencana = $this->resolveRencanaSalin($sumber, $tahunAjaran);

            foreach ($rencana['mengajar']['disalin'] as $baris) {
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

            foreach ($rencana['guru_bk']['disalin'] as $baris) {
                $baru = GuruBkKelas::firstOrCreate([
                    'tahun_ajaran_id' => $tahunAjaran->id,
                    'guru_id' => $baris['sumber']->guru_id,
                    'kelas_id' => $baris['kelas_tujuan']->id,
                ]);
                if ($baru->wasRecentlyCreated) {
                    $guruBkDisalin++;
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

        $totalDisalin = $kelasDisalin + $mengajarDisalin + $guruBkDisalin + $jadwalDisalin;
        $pesan = "Berhasil disalin dari {$sumber->labelPeriode()} ke {$tahunAjaran->labelPeriode()}: "
            ."{$kelasDisalin} kelas (+wali kelas), {$mengajarDisalin} mapping guru-mengajar, {$guruBkDisalin} mapping guru BK, {$jadwalDisalin} jadwal.";

        return redirect()->route('tahun-ajaran.index')
            ->with($totalDisalin > 0 ? 'success' : 'error',
                $totalDisalin > 0 ? $pesan : "Tidak ada data baru yang disalin — semua data dari {$sumber->labelPeriode()} sudah ada di {$tahunAjaran->labelPeriode()}.");
    }

    // NOTE (revisi permintaan admin): halaman "Persiapan Tahun Ajaran"
    // (method persiapan(), view tahun-ajaran/persiapan.blade.php) DIHAPUS
    // — dianggap menu tambahan yang tidak perlu. Semua informasi yang
    // relevan (kelas, wali kelas, guru mengajar, jadwal) sudah bisa
    // dilihat langsung lewat menu masing-masing.
}
