<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\PemanggilanOrangTua;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Support\BkAccessScope;
use Illuminate\Http\Request;

class BkPemanggilanController extends Controller
{
    use BkAccessScope;

    public function index(Request $request)
    {
        $user = $request->user();
        $query = PemanggilanOrangTua::with(['siswa.kelas', 'petugas'])->orderByDesc('tanggal');

        $kelasIds = $this->bkKelasIdsUntukUser($user);
        if ($kelasIds !== null) {
            $query->whereHas('siswa', fn ($q) => $q->whereIn('kelas_id', $kelasIds));
        }

        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal', $request->bulan);
        }
        if ($request->filled('tahun')) {
            $query->whereYear('tanggal', $request->tahun);
        }
        if ($request->filled('status')) {
            $query->where('ortu_hadir', $request->status === 'Hadir' ? 1 : 0);
        }
        if ($request->filled('kelas_id')) {
            $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $request->kelas_id));
        }

        // Tanpa pagination — 1 tabel dipakai untuk tampilan layar sekaligus
        // cetak/PDF, sesuai konvensi halaman Rekapitulasi.
        $data = $query->get();

        $kelasList = in_array($user->role, ['admin', 'kurikulum', 'kepala_sekolah'])
            ? Kelas::aktif()->orderBy('nama_kelas')->get()
            : ($user->role === 'guru_bk' ? $user->kelasBk() : collect());

        $guruBk = $this->bkGuruBkUntukCetak($user, $request->filled('kelas_id') ? (int) $request->kelas_id : null);

        return view('bk.pemanggilan.index', compact('data', 'kelasList', 'guruBk'));
    }

    public function store(Request $request)
    {
        $tahunAjaran = TahunAjaran::aktif();
        abort_if(!$tahunAjaran, 422, 'Tidak ada tahun ajaran aktif.');

        $validated = $request->validate([
            'siswa_id' => ['required', 'exists:siswas,id'],
            'kasus_siswa_id' => ['nullable', 'exists:kasus_siswas,id'],
            'tanggal' => ['required', 'date'],
            'alasan' => ['required', 'string'],
            'ortu_hadir' => ['required', 'boolean'],
            'hasil_pertemuan' => ['nullable', 'string', 'required_if:ortu_hadir,1'],
            'bukti_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'], // maks 5MB
            'kesepakatan' => ['nullable', 'string'],
        ]);

        $siswa = Siswa::findOrFail($validated['siswa_id']);
        $this->bkPastikanSiswaSesuaiCakupan($request->user(), $siswa);

        if ($request->hasFile('bukti_file')) {
            $validated['bukti_file'] = $request->file('bukti_file')->store('bk/bukti-pemanggilan', 'public');
        }

        $pemanggilan = PemanggilanOrangTua::create([
            ...$validated,
            'tahun_ajaran_id' => $tahunAjaran->id,
            'petugas_id' => $request->user()->id,
        ]);

        return redirect()->route('bk.siswa.show', $pemanggilan->siswa_id)
            ->with('success', 'Pemanggilan orang tua berhasil dicatat.');
    }
}
