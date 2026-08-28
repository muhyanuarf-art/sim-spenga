<?php

namespace App\Http\Controllers;

use App\Models\TahunAjaran;
use App\Support\PeriodeAkademik;
use App\Support\RentangPeriode;
use App\Support\SalinDataPeriode;
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
     * (2026-08-28) tanggal_mulai/tanggal_selesai DIKEMBALIKAN ke form,
     * tetapi OPSIONAL. Dulu keduanya dihilangkan supaya form lebih ringkas
     * — waktu itu memang tidak dipakai apa pun. Sekarang berbeda: kedua
     * tanggal ini menentukan rentang yang dipakai App\Rules\DalamPeriode
     * (menolak tanggal di luar periode saat menyimpan) DAN Laporan Akhir
     * Semester. Kalau dibiarkan kosong, rentangnya diturunkan otomatis
     * dari nama + semester (Ganjil = Juli–Desember, Genap = Januari–Juni,
     * dengan kelonggaran 21 hari) — lihat App\Support\RentangPeriode.
     * Jadi isian ini hanya perlu diisi kalau kalender sekolah menyimpang
     * dari pola umum tersebut.
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
            // Opsional — kosongkan saja bila kalender sekolah mengikuti
            // pola umum (lihat catatan di atas).
            'tanggal_mulai' => ['nullable', 'date', 'required_with:tanggal_selesai'],
            'tanggal_selesai' => ['nullable', 'date', 'required_with:tanggal_mulai', 'after_or_equal:tanggal_mulai'],
        ];
    }

    /** Pesan khusus rentang tanggal periode. */
    private function pesanValidasi(): array
    {
        return [
            'tanggal_mulai.required_with' => 'Tanggal mulai harus diisi bila tanggal selesai diisi (atau kosongkan keduanya).',
            'tanggal_selesai.required_with' => 'Tanggal selesai harus diisi bila tanggal mulai diisi (atau kosongkan keduanya).',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->aturanValidasi(), $this->pesanValidasi());
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
        $validated = $request->validate($this->aturanValidasi($tahunAjaran->id), $this->pesanValidasi());

        // Periode yang sedang AKTIF tidak boleh "dijatuhkan" statusnya lewat
        // form edit biasa — status aktif hanya boleh berubah lewat aktifkan().
        if ($tahunAjaran->is_active) {
            unset($validated['status']);
        } elseif (empty($validated['status'])) {
            $validated['status'] = $tahunAjaran->status;
        }

        $tahunAjaran->update($validated);

        // Rentang periode dipakai validasi & laporan — cache-nya dilupakan
        // supaya perubahan langsung berlaku, termasuk pada request ini.
        RentangPeriode::lupakanCache();

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
     * "SALIN DATA" — memindahkan seluruh pengaturan satu periode ke periode
     * lain dalam satu aksi: Mata Pelajaran, Jam Pelajaran, Jenis
     * Pelanggaran, Jenis Surat, Ekstrakurikuler (+pembina), Kelas & Wali
     * Kelas, Mapping Guru Mengajar, Mapping Guru BK, dan Jadwal Pelajaran.
     *
     * Perhitungannya seluruhnya ada di App\Support\SalinDataPeriode —
     * dipakai bersama oleh pratinjau (GET, tidak menulis apa pun) dan
     * eksekusi (POST), supaya apa yang dijanjikan di layar persis sama
     * dengan yang tersimpan.
     *
     * PRATINJAU sebelum menyalin apa pun.
     */
    public function previewDuplikasiMapping(Request $request)
    {
        $validated = $request->validate([
            'dari_tahun_ajaran_id' => ['required', 'exists:tahun_ajarans,id'],
            'tahun_ajaran_tujuan' => ['required', 'exists:tahun_ajarans,id', 'different:dari_tahun_ajaran_id'],
        ]);

        $sumber = TahunAjaran::findOrFail($validated['dari_tahun_ajaran_id']);
        $tujuan = TahunAjaran::findOrFail($validated['tahun_ajaran_tujuan']);

        $rencana = SalinDataPeriode::hitung($sumber, $tujuan);

        return view('tahun-ajaran.preview-duplikasi', compact('sumber', 'tujuan', 'rencana'));
    }

    /** Eksekusi "Salin Data" (POST). */
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

        $jumlah = SalinDataPeriode::jalankan($sumber, $tahunAjaran);
        $total = array_sum($jumlah);

        if ($total === 0) {
            return redirect()->route('tahun-ajaran.index')->with(
                'error',
                "Tidak ada data baru yang disalin — semua data dari {$sumber->labelPeriode()} sudah ada di {$tahunAjaran->labelPeriode()}."
            );
        }

        $rincian = collect(SalinDataPeriode::KATEGORI)
            ->filter(fn ($label, $kunci) => ($jumlah[$kunci] ?? 0) > 0)
            ->map(fn ($label, $kunci) => $jumlah[$kunci].' '.mb_strtolower($label))
            ->implode(', ');

        return redirect()->route('tahun-ajaran.index')->with(
            'success',
            "Berhasil disalin dari {$sumber->labelPeriode()} ke {$tahunAjaran->labelPeriode()}: {$rincian}."
        );
    }

    // NOTE (revisi permintaan admin): halaman "Persiapan Tahun Ajaran"
    // (method persiapan(), view tahun-ajaran/persiapan.blade.php) DIHAPUS
    // — dianggap menu tambahan yang tidak perlu. Semua informasi yang
    // relevan (kelas, wali kelas, guru mengajar, jadwal) sudah bisa
    // dilihat langsung lewat menu masing-masing.
}
