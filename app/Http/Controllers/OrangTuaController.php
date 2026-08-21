<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\OrangTua;
use App\Models\Siswa;
use Illuminate\Http\Request;

class OrangTuaController extends Controller
{
    public function index(Request $request)
    {
        $query = OrangTua::with('siswa.kelas')
            ->when($request->kelas_id, fn ($q) => $q->whereHas('siswa', fn ($s) => $s->where('kelas_id', $request->kelas_id)))
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($qq) use ($request) {
                    $qq->where('nis', 'like', "%{$request->search}%")
                       ->orWhereHas('siswa', fn ($s) => $s->where('nama', 'like', "%{$request->search}%"));
                });
            });

        $akunOrtu = $query->latest()->paginate(25)->withQueryString();
        $kelasList = Kelas::aktif()->orderBy('nama_kelas')->get();
        $jumlahSiswaBelumPunyaAkun = Siswa::where('is_active', true)
            ->whereDoesntHave('orangTua')
            ->count();

        return view('orangtua.index', compact('akunOrtu', 'kelasList', 'jumlahSiswaBelumPunyaAkun'));
    }

    /**
     * Buat akun orang tua otomatis untuk SEMUA siswa aktif yang belum
     * punya akun — 1 siswa = 1 akun, NIS siswa dipakai sebagai NIS login,
     * password default OrangTua::PASSWORD_DEFAULT. Menggantikan alur
     * import Excel lama: sumber datanya langsung dari tabel Siswa yang
     * sudah diinput di menu Data Siswa, tidak perlu file terpisah.
     */
    public function generate(Request $request)
    {
        $siswaBelumPunyaAkun = Siswa::where('is_active', true)
            ->whereDoesntHave('orangTua')
            ->get();

        $dibuat = 0;
        foreach ($siswaBelumPunyaAkun as $siswa) {
            OrangTua::create([
                'siswa_id' => $siswa->id,
                'nis' => $siswa->nis,
                'password' => OrangTua::PASSWORD_DEFAULT,
            ]);
            $dibuat++;
        }

        if ($dibuat === 0) {
            return back()->with('success', 'Semua siswa aktif sudah punya akun orang tua — tidak ada akun baru yang dibuat.');
        }

        return back()->with('success', "Berhasil membuat {$dibuat} akun orang tua baru dari data siswa (password default: \"" . OrangTua::PASSWORD_DEFAULT . '").');
    }

    public function resetPassword(OrangTua $orangTua)
    {
        $orangTua->update([
            'password' => OrangTua::PASSWORD_DEFAULT,
            'password_diubah_at' => null,
        ]);

        return back()->with('success', "Password akun orang tua NIS {$orangTua->nis} berhasil direset ke default.");
    }

    public function destroy(OrangTua $orangTua)
    {
        $orangTua->delete();
        return back()->with('success', 'Akun orang tua berhasil dihapus.');
    }
}
