<?php

namespace App\Http\Controllers;

use App\Models\EvaluasiPembinaan;
use App\Models\KasusSiswa;
use App\Models\PembinaanSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Support\BkAccessScope;
use Illuminate\Http\Request;

class BkPembinaanController extends Controller
{
    use BkAccessScope;

    public function index(Request $request)
    {
        $user = $request->user();
        $query = PembinaanSiswa::with(['siswa.kelas', 'petugas', 'kasus'])->orderByDesc('tanggal');

        $kelasIds = $this->bkKelasIdsUntukUser($user);
        if ($kelasIds !== null) {
            $query->whereHas('siswa', fn ($q) => $q->whereIn('kelas_id', $kelasIds));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $data = $query->paginate(20)->withQueryString();
        return view('bk.pembinaan.index', compact('data'));
    }

    public function store(Request $request)
    {
        $tahunAjaran = TahunAjaran::aktif();
        abort_if(!$tahunAjaran, 422, 'Tidak ada tahun ajaran aktif.');

        $validated = $request->validate([
            'siswa_id' => ['required', 'exists:siswas,id'],
            'kasus_siswa_id' => ['nullable', 'exists:kasus_siswas,id'],
            'tanggal' => ['required', 'date'],
            'tahap' => ['required', 'integer', 'min:1', 'max:7'],
            'jenis_pembinaan' => ['required', 'string'],
            'catatan_bk' => ['required', 'string'],
            'status' => ['required', 'in:Direncanakan,Berlangsung,Selesai,Tidak Berhasil'],
            'hasil_pembinaan' => ['nullable', 'string', 'required_if:status,Selesai,Tidak Berhasil'],
            'tanggal_evaluasi_berikutnya' => ['nullable', 'date'],
            'ruang_refleksi_selesai' => ['nullable', 'date'],
        ]);

        $pembinaan = PembinaanSiswa::create([
            ...$validated,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'petugas_id' => $request->user()->id,
        ]);

        // Kalau kasus terkait, otomatis update status kasus jadi "Dalam Pembinaan"
        if ($pembinaan->kasus_siswa_id) {
            KasusSiswa::where('id', $pembinaan->kasus_siswa_id)
                ->where('status', '!=', 'Selesai')
                ->update(['status' => 'Dalam Pembinaan']);
        }

        return redirect()->route('bk.siswa.show', $pembinaan->siswa_id)
            ->with('success', 'Pembinaan berhasil dicatat.');
    }

    public function update(Request $request, PembinaanSiswa $pembinaan)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Direncanakan,Berlangsung,Selesai,Tidak Berhasil'],
            'hasil_pembinaan' => ['nullable', 'string', 'required_if:status,Selesai,Tidak Berhasil'],
            'tanggal_evaluasi_berikutnya' => ['nullable', 'date'],
        ]);

        $pembinaan->update($validated);
        return back()->with('success', 'Pembinaan berhasil diperbarui.');
    }

    /** Catat evaluasi harian untuk pembinaan jenis "Ruang refleksi" (maks 7 hari). */
    public function storeEvaluasiHarian(Request $request, PembinaanSiswa $pembinaan)
    {
        $validated = $request->validate([
            'hari_ke' => ['required', 'integer', 'min:1', 'max:7'],
            'tanggal' => ['required', 'date'],
            'kondisi' => ['required', 'in:Baik,Perlu Perhatian,Kurang Baik'],
            'catatan' => ['required', 'string'],
        ]);

        EvaluasiPembinaan::updateOrCreate(
            ['pembinaan_siswa_id' => $pembinaan->id, 'hari_ke' => $validated['hari_ke']],
            [...$validated, 'petugas_id' => $request->user()->id]
        );

        return back()->with('success', "Evaluasi hari ke-{$validated['hari_ke']} berhasil disimpan.");
    }
}
