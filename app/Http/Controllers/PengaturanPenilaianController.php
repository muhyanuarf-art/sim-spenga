<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\KktpTingkat;
use App\Models\NilaiSiswa;
use App\Models\PengaturanPenilaian;
use App\Models\TahunAjaran;
use App\Support\PeriodeAkademik;
use App\Support\SkemaPenilaian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * PENGATURAN PENILAIAN — dikelola KURIKULUM (& Admin).
 *
 * Yang bisa diatur di sini persis yang diminta sekolah supaya tidak
 * di-hardcode di dalam program:
 *
 * - Bobot FORMATIF + SUMATIF LINGKUP MATERI (default 60%)
 * - Bobot ASTS  (default 20%)
 * - Bobot ASAS/ASAT (default 20%)
 * - Komposisi di dalam bobot 60% (Formatif : Sumatif Lingkup Materi)
 * - KKTP tiap tingkat (Kelas 7, 8, 9 — default 73 s.d. 82)
 * - Banyaknya kolom TPF & Lingkup Materi di daftar nilai
 * - Kebijakan perhitungan nilai remedi
 *
 * Pengaturannya berlaku PER PERIODE (tahun ajaran + semester), jadi
 * mengubah bobot untuk semester ini tidak mengubah angka rapor semester
 * yang sudah lewat.
 */
class PengaturanPenilaianController extends Controller
{
    public function edit(Request $request)
    {
        $periode = $this->periodeDilihat($request);
        $pengaturan = PengaturanPenilaian::untukPeriode($periode);
        $kktp = KktpTingkat::semuaUntuk($periode);
        $skema = SkemaPenilaian::untuk($periode, KktpTingkat::TINGKAT[0]);

        $daftarPeriode = TahunAjaran::orderByDesc('nama')->orderBy('semester')->get();

        // Berapa banyak nilai yang akan ikut dihitung ulang kalau bobotnya
        // diubah — ditampilkan sebagai peringatan yang jujur di layar.
        $jumlahNilai = NilaiSiswa::where('tahun_ajaran_id', $periode->id)->count();

        return view('penilaian.pengaturan', compact(
            'periode', 'pengaturan', 'kktp', 'skema', 'daftarPeriode', 'jumlahNilai'
        ));
    }

    public function update(Request $request)
    {
        $periode = $this->periodeDilihat($request);
        PeriodeAkademik::pastikanTidakTerkunci($periode);

        $validated = $request->validate([
            'bobot_formatif_sumatif' => ['required', 'integer', 'min:0', 'max:100'],
            'bobot_asts' => ['required', 'integer', 'min:0', 'max:100'],
            'bobot_asas' => ['required', 'integer', 'min:0', 'max:100'],
            'komposisi_formatif' => ['required', 'integer', 'min:0', 'max:100'],
            'komposisi_sumatif_lm' => ['required', 'integer', 'min:0', 'max:100'],
            'jumlah_tpf' => ['required', 'integer', 'min:1', 'max:12'],
            'jumlah_lm' => ['required', 'integer', 'min:1', 'max:8'],
            'kebijakan_remedial' => ['required', 'in:'.implode(',', array_keys(SkemaPenilaian::KEBIJAKAN))],
            'kktp' => ['required', 'array'],
            'kktp.*.kktp_min' => ['required', 'integer', 'min:0', 'max:100'],
            'kktp.*.kktp_max' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $totalBobot = $validated['bobot_formatif_sumatif'] + $validated['bobot_asts'] + $validated['bobot_asas'];
        if ($totalBobot !== 100) {
            throw ValidationException::withMessages([
                'bobot_formatif_sumatif' => "Total ketiga bobot harus tepat 100%, saat ini {$totalBobot}%.",
            ]);
        }

        $totalKomposisi = $validated['komposisi_formatif'] + $validated['komposisi_sumatif_lm'];
        if ($totalKomposisi !== 100) {
            throw ValidationException::withMessages([
                'komposisi_formatif' => "Komposisi Formatif dan Sumatif Lingkup Materi harus berjumlah 100%, saat ini {$totalKomposisi}%.",
            ]);
        }

        foreach ($validated['kktp'] as $tingkat => $baris) {
            if ($baris['kktp_min'] > $baris['kktp_max']) {
                throw ValidationException::withMessages([
                    "kktp.{$tingkat}.kktp_min" => "KKTP minimum Kelas {$tingkat} tidak boleh lebih besar dari KKTP maksimum.",
                ]);
            }
        }

        DB::transaction(function () use ($validated, $periode, $request) {
            PengaturanPenilaian::updateOrCreate(
                ['tahun_ajaran_id' => $periode->id],
                [
                    'bobot_formatif_sumatif' => $validated['bobot_formatif_sumatif'],
                    'bobot_asts' => $validated['bobot_asts'],
                    'bobot_asas' => $validated['bobot_asas'],
                    'komposisi_formatif' => $validated['komposisi_formatif'],
                    'komposisi_sumatif_lm' => $validated['komposisi_sumatif_lm'],
                    'jumlah_tpf' => $validated['jumlah_tpf'],
                    'jumlah_lm' => $validated['jumlah_lm'],
                    'kebijakan_remedial' => $validated['kebijakan_remedial'],
                    'diperbarui_oleh_id' => $request->user()->id,
                ]
            );

            foreach ($validated['kktp'] as $tingkat => $baris) {
                KktpTingkat::updateOrCreate(
                    ['tahun_ajaran_id' => $periode->id, 'tingkat' => (int) $tingkat],
                    ['kktp_min' => $baris['kktp_min'], 'kktp_max' => $baris['kktp_max']]
                );
            }

            // Bobot & KKTP ikut menentukan nilai akhir, rata-rata, predikat,
            // dan status tuntas yang SUDAH TERSIMPAN di nilai_siswas. Kalau
            // tidak dihitung ulang di sini, daftar nilai akan menampilkan
            // angka lama yang tidak lagi sesuai aturan yang baru — dan
            // beda antara layar guru dan laporan wali kelas. Jadi seluruh
            // nilai pada periode ini dihitung ulang sekarang juga.
            SkemaPenilaian::lupakanCache();
            $this->hitungUlangPeriode($periode);
        });

        return redirect()
            ->route('penilaian.pengaturan.edit', ['tahun_ajaran_id' => $periode->id])
            ->with('success', 'Pengaturan penilaian disimpan. Seluruh nilai pada periode ini sudah dihitung ulang mengikuti aturan yang baru.');
    }

    /**
     * Hitung ulang nilai akhir seluruh siswa pada satu periode.
     * Diproses per potongan supaya tetap ringan walau satu sekolah penuh.
     */
    private function hitungUlangPeriode(TahunAjaran $periode): void
    {
        // Tingkat tiap kelas dibaca sekali di depan — KKTP (dan karenanya
        // predikat & status tuntas) berbeda per tingkat.
        $tingkatPerKelas = Kelas::pluck('tingkat', 'id');

        NilaiSiswa::where('tahun_ajaran_id', $periode->id)
            ->chunkById(500, function ($daftar) use ($periode, $tingkatPerKelas) {
                foreach ($daftar as $nilai) {
                    $tingkat = (int) ($tingkatPerKelas[$nilai->kelas_id] ?? KktpTingkat::TINGKAT[0]);
                    $nilai->hitungUlang(SkemaPenilaian::untuk($periode, $tingkat))->save();
                }
            });
    }

    /** Periode yang sedang dilihat/disunting — default periode aktif. */
    private function periodeDilihat(Request $request): TahunAjaran
    {
        if ($request->filled('tahun_ajaran_id')) {
            return TahunAjaran::findOrFail($request->integer('tahun_ajaran_id'));
        }

        $periode = PeriodeAkademik::aktif();

        abort_if($periode === null, 409, 'Belum ada Tahun Ajaran yang aktif. Aktifkan periode terlebih dahulu di menu Tahun Ajaran.');

        return $periode;
    }
}
