<?php

namespace App\Http\Controllers;

use App\Rules\DalamPeriode;
use App\Models\AbsensiEkskul;
use App\Models\AbsensiEkskulPeserta;
use App\Models\Ekstrakurikuler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Absensi kegiatan ekstrakurikuler.
 *
 * Aturan bisnis (sesuai arahan Kesiswaan):
 * - Yang MENGISI form ini hanya pembina dari SEKOLAH (guru/guru_bk yang
 *   terdaftar sebagai pembina INTERNAL kegiatan itu), atau Kesiswaan/Admin
 *   yang mengisi mewakili (pola sama seperti admin "mewakili guru" di
 *   MengajarController). Pembina dari LUAR sekolah tidak pernah bisa
 *   mengisi — mereka memang tidak punya akun sistem.
 * - Yang DIABSEN (dicatat kehadirannya) ada 2 jenis: SISWA anggota
 *   kegiatan, dan PEMBINA (baik sekolah maupun luar sekolah) — jadi
 *   pembina juga tercatat hadir/tidaknya, meski dia sendiri (kalau dari
 *   luar sekolah) tidak bisa mengisi form-nya.
 */
class EkskulAbsensiController extends Controller
{
    /**
     * Daftar kegiatan yang boleh diisi absensinya oleh user yang login —
     * guru/guru_bk hanya melihat kegiatan yang mereka bina, Kesiswaan/Admin
     * melihat semua kegiatan aktif (untuk mengisi mewakili kalau perlu).
     */
    public function pilihKegiatan(Request $request)
    {
        $user = $request->user();

        $query = Ekstrakurikuler::with('pembinas.user')->where('is_aktif', true)->orderBy('nama_ekstrakurikuler');

        if (in_array($user->role, ['guru', 'guru_bk'])) {
            $query->whereHas('pembinas', fn ($q) => $q->where('user_id', $user->id));
        }
        // kesiswaan & admin: tidak difilter, lihat semua kegiatan aktif.

        $kegiatan = $query->get();

        return view('ekstrakurikuler.absensi-pilih', compact('kegiatan'));
    }

    public function form(Request $request, Ekstrakurikuler $ekstrakurikuler)
    {
        $this->otorisasiPengisi($request, $ekstrakurikuler);

        $tanggal = $request->get('tanggal', now()->toDateString());

        $ekstrakurikuler->load(['anggotas.siswa', 'pembinas.user']);
        $siswaList = $ekstrakurikuler->anggotas->pluck('siswa')->filter(fn ($s) => $s && $s->is_active)->sortBy('nama')->values();
        $pembinaList = $ekstrakurikuler->pembinas;

        $sesi = AbsensiEkskul::where('ekstrakurikuler_id', $ekstrakurikuler->id)
            ->whereDate('tanggal', $tanggal)
            ->with('peserta')
            ->first();

        $statusSiswa = [];
        $statusPembina = [];
        if ($sesi) {
            foreach ($sesi->peserta as $p) {
                if ($p->siswa_id) {
                    $statusSiswa[$p->siswa_id] = $p->status;
                } elseif ($p->ekstrakurikuler_pembina_id) {
                    $statusPembina[$p->ekstrakurikuler_pembina_id] = $p->status;
                }
            }
        }

        return view('ekstrakurikuler.absensi-form', [
            'ekstrakurikuler' => $ekstrakurikuler,
            'tanggal' => $tanggal,
            'siswaList' => $siswaList,
            'pembinaList' => $pembinaList,
            'sesi' => $sesi,
            'statusSiswa' => $statusSiswa,
            'statusPembina' => $statusPembina,
        ]);
    }

    public function store(Request $request, Ekstrakurikuler $ekstrakurikuler)
    {
        $this->otorisasiPengisi($request, $ekstrakurikuler);

        $validated = $request->validate([
            'tanggal' => ['required', 'date', new DalamPeriode(sebutan: 'absensi ekstrakurikuler')],
            'kegiatan' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'siswa' => ['nullable', 'array'],
            'siswa.*' => ['in:Hadir,Sakit,Izin,Alfa'],
            'pembina' => ['nullable', 'array'],
            'pembina.*' => ['in:Hadir,Sakit,Izin,Alfa'],
        ]);

        // Validasi keanggotaan: id yang dikirim harus benar-benar anggota/
        // pembina kegiatan INI, bukan hasil rakitan form.
        $idSiswaAnggota = $ekstrakurikuler->anggotas()->pluck('siswa_id');
        $idAsingSiswa = collect(array_keys($validated['siswa'] ?? []))->map(fn ($id) => (int) $id)->diff($idSiswaAnggota);
        if ($idAsingSiswa->isNotEmpty()) {
            abort(422, 'Ada siswa pada data absensi yang bukan anggota kegiatan ini.');
        }
        $idPembinaSah = $ekstrakurikuler->pembinas()->pluck('id');
        $idAsingPembina = collect(array_keys($validated['pembina'] ?? []))->map(fn ($id) => (int) $id)->diff($idPembinaSah);
        if ($idAsingPembina->isNotEmpty()) {
            abort(422, 'Ada pembina pada data absensi yang bukan pembina kegiatan ini.');
        }

        DB::transaction(function () use ($validated, $ekstrakurikuler, $request) {
            $sesi = AbsensiEkskul::updateOrCreate(
                ['ekstrakurikuler_id' => $ekstrakurikuler->id, 'tanggal' => $validated['tanggal']],
                [
                    'dicatat_oleh_id' => $request->user()->id,
                    'kegiatan' => $validated['kegiatan'] ?? null,
                    'keterangan' => $validated['keterangan'] ?? null,
                ]
            );

            foreach ($validated['siswa'] ?? [] as $siswaId => $status) {
                AbsensiEkskulPeserta::updateOrCreate(
                    ['absensi_ekskul_id' => $sesi->id, 'siswa_id' => $siswaId],
                    ['status' => $status]
                );
            }

            foreach ($validated['pembina'] ?? [] as $pembinaId => $status) {
                AbsensiEkskulPeserta::updateOrCreate(
                    ['absensi_ekskul_id' => $sesi->id, 'ekstrakurikuler_pembina_id' => $pembinaId],
                    ['status' => $status]
                );
            }
        });

        return redirect()->route('ekstrakurikuler.absensi.form', ['ekstrakurikuler' => $ekstrakurikuler, 'tanggal' => $validated['tanggal']])
            ->with('success', "Absensi ekstrakurikuler {$ekstrakurikuler->nama_ekstrakurikuler} berhasil disimpan.");
    }

    /**
     * Kesiswaan & Admin boleh mengisi kegiatan apa pun (mewakili). Guru/
     * Guru BK hanya boleh mengisi kegiatan yang mereka bina sendiri
     * (pembina INTERNAL — lihat Ekstrakurikuler::isPembinaInternal()).
     * Role lain (termasuk pembina luar sekolah, yang memang tidak punya
     * akun) tidak pernah lolos otorisasi ini.
     */
    private function otorisasiPengisi(Request $request, Ekstrakurikuler $ekstrakurikuler): void
    {
        $user = $request->user();
        if (in_array($user->role, ['kesiswaan', 'admin'])) {
            return;
        }
        if (in_array($user->role, ['guru', 'guru_bk']) && $ekstrakurikuler->isPembinaInternal($user->id)) {
            return;
        }
        abort(403, 'Anda bukan pembina kegiatan ini.');
    }
}
