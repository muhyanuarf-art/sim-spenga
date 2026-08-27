<?php

namespace App\Http\Controllers;

use App\Models\AbsensiKegiatan;
use App\Models\AbsensiSiswa;
use App\Models\KegiatanSekolah;
use App\Models\Kelas;
use App\Support\KeanggotaanKelas;
use App\Support\NotifikasiAlfa;
use App\Support\PeriodeAkademik;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * PENGISIAN ABSENSI KEGIATAN SEKOLAH — HANYA WALI KELAS.
 *
 * Pada hari kegiatan (lomba, tryout/asesmen, classmeeting, pesantren
 * Ramadan) tidak ada guru mapel yang mengisi absensi karena memang tidak
 * ada KBM. Yang bertanggung jawab atas kehadiran siswa hari itu adalah
 * WALI KELAS, maka hanya wali kelas dari kelas sasaran yang boleh membuka
 * dan menyimpan form di sini.
 *
 * Admin tetap diizinkan sebagai perwakilan (mis. wali kelas berhalangan),
 * konsisten dengan MengajarController yang juga membolehkan admin mengisi
 * jurnal atas nama guru. Selain keduanya: ditolak 403 — kesiswaan dan
 * kurikulum boleh menjadwalkan & memantau, tapi tidak mengisi absensi.
 *
 * Notifikasi WhatsApp Alfa TETAP jalan otomatis, lewat jalur yang sama
 * persis dengan absensi KBM (App\Support\NotifikasiAlfa).
 */
class AbsensiKegiatanController extends Controller
{
    /** Daftar kegiatan yang bisa/harus diisi wali kelas, per tanggal. */
    public function pilih(Request $request)
    {
        $user = $request->user();
        $tanggal = $request->get('tanggal', now()->toDateString());
        $tanggalCarbon = Carbon::parse($tanggal);

        $kelasList = $this->kelasYangBolehDiisi($request);

        // Kegiatan yang berlangsung pada tanggal itu, disilangkan dengan
        // kelas yang boleh diisi oleh pengguna ini.
        $kegiatanBerlangsung = KegiatanSekolah::berlangsungPadaTanggal($tanggalCarbon->toDateString());

        $sudahDiisi = AbsensiKegiatan::whereDate('tanggal', $tanggalCarbon->toDateString())
            ->whereIn('kelas_id', $kelasList->pluck('id'))
            ->get()
            ->keyBy(fn ($a) => $a->kegiatan_sekolah_id.'|'.$a->kelas_id);

        $tugasList = collect();
        foreach ($kegiatanBerlangsung as $kegiatan) {
            $idKelasSasaran = $kegiatan->kelasSasaran()->pluck('id');
            foreach ($kelasList as $kelas) {
                if (! $idKelasSasaran->contains($kelas->id)) {
                    continue;
                }
                $absensi = $sudahDiisi->get($kegiatan->id.'|'.$kelas->id);
                $tugasList->push([
                    'kegiatan' => $kegiatan,
                    'kelas' => $kelas,
                    'absensi' => $absensi,
                    'sudah_diisi' => (bool) $absensi,
                ]);
            }
        }

        // Kegiatan terdekat yang akan datang — supaya wali kelas tahu ada
        // agenda meski hari ini tidak ada kegiatan.
        $kegiatanMendatang = KegiatanSekolah::where('is_aktif', true)
            ->whereDate('tanggal_selesai', '>=', $tanggalCarbon->toDateString())
            ->whereDate('tanggal_mulai', '>', $tanggalCarbon->toDateString())
            ->orderBy('tanggal_mulai')
            ->limit(5)
            ->get();

        return view('kegiatan.absensi-pilih', compact(
            'tugasList', 'tanggal', 'tanggalCarbon', 'kelasList', 'kegiatanMendatang'
        ));
    }

    /** Form isi absensi 1 kelas untuk 1 kegiatan pada 1 tanggal. */
    public function form(Request $request, KegiatanSekolah $kegiatan, Kelas $kelas)
    {
        $tanggal = $this->resolveTanggal($request, $kegiatan);
        $this->pastikanBoleh($request, $kegiatan, $kelas, $tanggal);

        $absensiKegiatan = AbsensiKegiatan::where('kegiatan_sekolah_id', $kegiatan->id)
            ->where('kelas_id', $kelas->id)
            ->whereDate('tanggal', $tanggal)
            ->first();

        // Sama seperti absensi KBM: pakai keanggotaan kelas PADA TANGGAL
        // tersebut, bukan kelas siswa saat ini, supaya siswa yang sudah
        // pindah kelas tetap muncul untuk tanggal sebelum ia pindah.
        $siswas = KeanggotaanKelas::anggotaPadaTanggal($kelas, $tanggal);

        $absensiTersimpan = [];
        if ($absensiKegiatan) {
            foreach ($absensiKegiatan->absensi as $a) {
                $absensiTersimpan[$a->siswa_id] = $a->status;
            }
        }

        return view('kegiatan.absensi-form', compact(
            'kegiatan', 'kelas', 'tanggal', 'siswas', 'absensiKegiatan', 'absensiTersimpan'
        ));
    }

    public function store(Request $request, KegiatanSekolah $kegiatan, Kelas $kelas)
    {
        $tanggal = $this->resolveTanggal($request, $kegiatan);
        $this->pastikanBoleh($request, $kegiatan, $kelas, $tanggal);

        PeriodeAkademik::pastikanTidakTerkunci($kegiatan->tahunAjaran);

        $validated = $request->validate([
            'catatan' => ['nullable', 'string', 'max:1000'],
            'absensi' => ['required', 'array'],
            'absensi.*' => ['required', 'in:Hadir,Sakit,Izin,Alfa'],
        ]);

        $siswaIdsKelas = KeanggotaanKelas::anggotaPadaTanggal($kelas, $tanggal)->pluck('id');
        $siswaIdsAsing = collect(array_keys($validated['absensi']))
            ->map(fn ($id) => (int) $id)
            ->diff($siswaIdsKelas);
        if ($siswaIdsAsing->isNotEmpty()) {
            abort(422, 'Ada siswa pada data absensi yang bukan anggota kelas ini.');
        }

        DB::transaction(function () use ($validated, $kegiatan, $kelas, $tanggal, $request) {
            $absensiKegiatan = AbsensiKegiatan::firstOrNew([
                'kegiatan_sekolah_id' => $kegiatan->id,
                'kelas_id' => $kelas->id,
                'tanggal' => $tanggal,
            ]);

            $absensiKegiatan->fill([
                'diisi_oleh_id' => $request->user()->id,
                'catatan' => $validated['catatan'] ?? null,
            ])->save();

            $rekap = ['Hadir' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alfa' => 0];

            foreach ($validated['absensi'] as $siswaId => $status) {
                AbsensiSiswa::updateOrCreate(
                    ['absensi_kegiatan_id' => $absensiKegiatan->id, 'siswa_id' => $siswaId],
                    [
                        'jurnal_mengajar_id' => null,
                        'sumber' => 'kegiatan',
                        'kelas_id' => $kelas->id,
                        'tanggal' => $tanggal,
                        'status' => $status,
                    ]
                );
                $rekap[$status] = ($rekap[$status] ?? 0) + 1;
            }

            $absensiKegiatan->update([
                'jumlah_hadir' => $rekap['Hadir'],
                'jumlah_sakit' => $rekap['Sakit'],
                'jumlah_izin' => $rekap['Izin'],
                'jumlah_alfa' => $rekap['Alfa'],
            ]);
        });

        // Notifikasi WhatsApp Alfa ke orang tua — jalur yang SAMA PERSIS
        // dengan absensi KBM, hanya konteks pesannya menyebut nama kegiatan.
        NotifikasiAlfa::proses($validated['absensi'], $tanggal);

        $jumlahAlfa = collect($validated['absensi'])->filter(fn ($s) => $s === 'Alfa')->count();
        $pesanWa = match (true) {
            $jumlahAlfa === 0 => '',
            ! $kegiatan->kirim_wa_alfa => ' Tidak ada WhatsApp yang dikirim karena kegiatan ini diatur tanpa notifikasi.',
            ! Carbon::parse($tanggal)->isToday() => ' Notifikasi WhatsApp tidak dikirim karena pengisian untuk tanggal yang sudah lewat.',
            default => " Notifikasi WhatsApp untuk {$jumlahAlfa} siswa Alfa sedang diproses.",
        };

        return redirect()->route('kegiatan.absensi.pilih', ['tanggal' => $tanggal])
            ->with('success', "Absensi kegiatan \"{$kegiatan->nama}\" kelas {$kelas->nama_kelas} tersimpan.".$pesanWa);
    }

    /**
     * Kelas yang boleh diisi oleh pengguna ini:
     * - Guru: HANYA kelas yang ia wali-i.
     * - Admin: semua kelas aktif (perwakilan bila wali kelas berhalangan).
     */
    private function kelasYangBolehDiisi(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $kelasId = (int) $request->get('kelas_id', 0);
            $semua = Kelas::aktif()->orderBy('nama_kelas')->get();

            return $kelasId ? $semua->where('id', $kelasId)->values() : $semua;
        }

        if ($user->role === 'guru' && $user->kelasWali) {
            return collect([$user->kelasWali]);
        }

        return collect();
    }

    private function pastikanBoleh(Request $request, KegiatanSekolah $kegiatan, Kelas $kelas, string $tanggal): void
    {
        $user = $request->user();

        $bolehKelasIni = $user->isAdmin() || ($user->role === 'guru' && $user->kelasWali?->id === $kelas->id);
        if (! $bolehKelasIni) {
            abort(403, 'Absensi kegiatan hanya dapat diisi oleh Wali Kelas dari kelas tersebut.');
        }

        if (! $kegiatan->is_aktif) {
            abort(403, 'Kegiatan ini sedang dinonaktifkan.');
        }

        if (! $kegiatan->mencakupKelas($kelas)) {
            abort(403, "Kelas {$kelas->nama_kelas} bukan sasaran kegiatan ini.");
        }

        if (! $kegiatan->berlangsungPada($tanggal)) {
            abort(404, 'Kegiatan ini tidak berlangsung pada tanggal tersebut.');
        }
    }

    /**
     * Tanggal yang diisi. Default: hari ini kalau kegiatannya memang
     * berlangsung hari ini, kalau tidak, tanggal kegiatan yang pertama.
     */
    private function resolveTanggal(Request $request, KegiatanSekolah $kegiatan): string
    {
        $diminta = $request->get('tanggal');
        if ($diminta) {
            return Carbon::parse($diminta)->toDateString();
        }

        if ($kegiatan->berlangsungPada(now())) {
            return now()->toDateString();
        }

        return ($kegiatan->daftarTanggal()->first() ?? $kegiatan->tanggal_mulai)->toDateString();
    }
}
