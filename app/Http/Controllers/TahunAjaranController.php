<?php

namespace App\Http\Controllers;

use App\Models\GuruMengajarKelas;
use App\Models\JadwalPelajaran;
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

    /**
     * Aturan validasi tanggal & status (Bagian 3, 4, 6) yang dipakai
     * store() maupun update(). `status` sengaja TIDAK boleh diisi
     * 'aktif' lewat form ini — mengaktifkan periode hanya lewat
     * aktifkan() supaya constraint "hanya satu periode aktif" (Bagian 8)
     * tidak bisa dilanggar lewat jalan pintas edit form.
     */
    private function aturanValidasi(): array
    {
        return [
            'nama' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'in:Ganjil,Genap'],
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

    public function update(Request $request, TahunAjaran $tahunAjaran)
    {
        $validated = $request->validate($this->aturanValidasi());

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
     */
    public function aktifkan(TahunAjaran $tahunAjaran)
    {
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

            $tahunAjaran->update(['is_active' => true, 'status' => TahunAjaran::STATUS_AKTIF]);
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
