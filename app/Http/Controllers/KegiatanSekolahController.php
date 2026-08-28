<?php

namespace App\Http\Controllers;

use App\Rules\DalamPeriode;
use App\Models\AbsensiKegiatan;
use App\Models\KegiatanSekolah;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Penjadwalan KEGIATAN SEKOLAH di luar jam KBM: lomba Agustus, tryout &
 * asesmen sumatif, classmeeting, pesantren Ramadan, dan sebagainya.
 *
 * Yang MENJADWALKAN: Kesiswaan, Kurikulum, Admin.
 * Yang MENGISI ABSENSI: hanya Wali Kelas (lihat AbsensiKegiatanController).
 * Yang HANYA MEMANTAU: Kepala Sekolah & Guru BK (route GET saja).
 */
class KegiatanSekolahController extends Controller
{
    public function index(Request $request)
    {
        $tahunAjaran = TahunAjaran::aktif();

        $filter = $request->get('status', 'berjalan');

        $semua = KegiatanSekolah::with(['kelasTerpilih', 'dibuatOleh'])
            ->when($tahunAjaran, fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaran->id))
            ->orderByDesc('tanggal_mulai')
            ->get();

        $kegiatanList = match ($filter) {
            'selesai' => $semua->filter(fn ($k) => $k->status() === 'selesai'),
            'semua' => $semua,
            default => $semua->filter(fn ($k) => in_array($k->status(), ['berlangsung', 'akan_datang', 'nonaktif'], true)),
        };

        // Progres pengisian absensi tiap kegiatan: 1 query GROUP BY untuk
        // semua kegiatan sekaligus, bukan 1 query per kegiatan.
        $terisiPerKegiatan = AbsensiKegiatan::selectRaw('kegiatan_sekolah_id, COUNT(*) as jumlah')
            ->whereIn('kegiatan_sekolah_id', $semua->pluck('id'))
            ->groupBy('kegiatan_sekolah_id')
            ->pluck('jumlah', 'kegiatan_sekolah_id');

        $kegiatanList = $kegiatanList->map(function (KegiatanSekolah $k) use ($terisiPerKegiatan) {
            $target = $k->kelasSasaran()->count() * $k->daftarTanggal()->count();
            $terisi = (int) ($terisiPerKegiatan[$k->id] ?? 0);
            $k->setAttribute('progres_target', $target);
            $k->setAttribute('progres_terisi', $terisi);
            $k->setAttribute('progres_persen', $target > 0 ? (int) round(min($terisi, $target) / $target * 100) : 0);

            return $k;
        })->values();

        $kelasList = Kelas::aktif()->orderBy('nama_kelas')->get();
        $tingkatList = $kelasList->pluck('tingkat')->unique()->sort()->values();
        $bolehKelola = in_array($request->user()->role, ['kesiswaan', 'kurikulum', 'admin'], true);

        return view('kegiatan.index', compact(
            'kegiatanList', 'kelasList', 'tingkatList', 'tahunAjaran', 'filter', 'bolehKelola'
        ));
    }

    /** Detail + pantauan pengisian absensi per kelas per tanggal. */
    public function show(Request $request, KegiatanSekolah $kegiatan)
    {
        $kegiatan->load('kelasTerpilih', 'dibuatOleh');

        $tanggalList = $kegiatan->daftarTanggal();
        $kelasSasaran = $kegiatan->kelasSasaran()->load('waliKelas');

        // Satu query untuk seluruh matriks kelas × tanggal.
        $absensiTerisi = AbsensiKegiatan::where('kegiatan_sekolah_id', $kegiatan->id)
            ->with('diisiOleh')
            ->get()
            ->keyBy(fn ($a) => $a->kelas_id.'|'.$a->tanggal->toDateString());

        $bolehKelola = in_array($request->user()->role, ['kesiswaan', 'kurikulum', 'admin'], true);

        return view('kegiatan.show', compact(
            'kegiatan', 'tanggalList', 'kelasSasaran', 'absensiTerisi', 'bolehKelola'
        ));
    }

    public function store(Request $request)
    {
        $tahunAjaran = TahunAjaran::aktif();
        if (! $tahunAjaran) {
            return back()->with('error', 'Belum ada Tahun Ajaran aktif. Aktifkan periode lebih dulu sebelum menjadwalkan kegiatan.');
        }

        $data = $this->validasi($request);

        DB::transaction(function () use ($data, $request, $tahunAjaran) {
            $kegiatan = KegiatanSekolah::create([
                'tahun_ajaran_id' => $tahunAjaran->id,
                'nama' => $data['nama'],
                'jenis' => $data['jenis'],
                'tanggal_mulai' => $data['tanggal_mulai'],
                'tanggal_selesai' => $data['tanggal_selesai'],
                'hari_aktif' => $data['hari_aktif'] ?? null,
                'cakupan' => $data['cakupan'],
                'tingkat' => $data['cakupan'] === 'tingkat' ? $data['tingkat'] : null,
                'keterangan' => $data['keterangan'] ?? null,
                'kirim_wa_alfa' => $request->boolean('kirim_wa_alfa'),
                'is_aktif' => true,
                'dibuat_oleh_id' => $request->user()->id,
            ]);

            if ($data['cakupan'] === 'kelas') {
                $kegiatan->kelasTerpilih()->sync($data['kelas_ids'] ?? []);
            }
        });

        return redirect()->route('kegiatan.index')
            ->with('success', "Kegiatan \"{$data['nama']}\" berhasil dijadwalkan. Wali kelas sasaran sudah bisa mengisi absensinya.");
    }

    public function update(Request $request, KegiatanSekolah $kegiatan)
    {
        $data = $this->validasi($request);

        DB::transaction(function () use ($data, $request, $kegiatan) {
            $kegiatan->update([
                'nama' => $data['nama'],
                'jenis' => $data['jenis'],
                'tanggal_mulai' => $data['tanggal_mulai'],
                'tanggal_selesai' => $data['tanggal_selesai'],
                'hari_aktif' => $data['hari_aktif'] ?? null,
                'cakupan' => $data['cakupan'],
                'tingkat' => $data['cakupan'] === 'tingkat' ? $data['tingkat'] : null,
                'keterangan' => $data['keterangan'] ?? null,
                'kirim_wa_alfa' => $request->boolean('kirim_wa_alfa'),
                'is_aktif' => $request->boolean('is_aktif'),
            ]);

            $kegiatan->kelasTerpilih()->sync($data['cakupan'] === 'kelas' ? ($data['kelas_ids'] ?? []) : []);
        });

        return back()->with('success', 'Kegiatan berhasil diperbarui.');
    }

    public function destroy(KegiatanSekolah $kegiatan)
    {
        // Kegiatan yang absensinya sudah pernah diisi TIDAK boleh dihapus —
        // menghapusnya akan ikut menghapus catatan kehadiran siswa pada hari
        // itu (cascade), termasuk yang sudah menjadi dasar notifikasi WA ke
        // orang tua. Untuk menyembunyikannya dari wali kelas, nonaktifkan
        // saja lewat tombol "Nonaktifkan".
        if ($kegiatan->absensi()->exists()) {
            return back()->with('error', 'Kegiatan ini tidak dapat dihapus karena absensinya sudah pernah diisi. Nonaktifkan saja bila tidak dipakai lagi.');
        }

        $nama = $kegiatan->nama;
        $kegiatan->delete();

        return redirect()->route('kegiatan.index')->with('success', "Kegiatan \"{$nama}\" dihapus.");
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'jenis' => ['required', Rule::in(array_keys(KegiatanSekolah::JENIS))],
            'tanggal_mulai' => ['required', 'date', new DalamPeriode(sebutan: 'kegiatan')],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai', new DalamPeriode(sebutan: 'kegiatan')],
            'hari_aktif' => ['nullable', 'array'],
            'hari_aktif.*' => [Rule::in(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'])],
            'cakupan' => ['required', Rule::in(array_keys(KegiatanSekolah::CAKUPAN))],
            'tingkat' => ['nullable', 'string', 'max:10', 'required_if:cakupan,tingkat'],
            'kelas_ids' => ['nullable', 'array', 'required_if:cakupan,kelas'],
            'kelas_ids.*' => ['integer', 'exists:kelas,id'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ], [
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
            'kelas_ids.required_if' => 'Pilih minimal satu kelas untuk cakupan "Kelas tertentu".',
            'tingkat.required_if' => 'Pilih tingkat untuk cakupan "Satu tingkat".',
        ]);
    }
}
