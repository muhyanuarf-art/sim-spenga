<?php

namespace App\Http\Controllers;

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

        $data = $query->paginate(20)->withQueryString();
        return view('bk.pemanggilan.index', compact('data'));
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
