<?php

namespace App\Http\Controllers;

use App\Models\Ekstrakurikuler;
use App\Models\EkstrakurikulerPembina;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Master data "Kegiatan Ekstrakurikuler" — dikelola Kesiswaan.
 * Ini langkah pertama dari fitur absensi ekskul: Kesiswaan input nama
 * kegiatan + pembina (boleh lebih dari satu, campur staf sekolah & pembina
 * dari luar sekolah) di sini dulu. Anggota/jadwal/absensi per kegiatan
 * menyusul di menu terpisah, dibangun di atas data ini.
 */
class EkstrakurikulerController extends Controller
{
    public function index()
    {
        $ekstrakurikuler = Ekstrakurikuler::periodeAktif()->with('pembinas.user')->orderBy('nama_ekstrakurikuler')->paginate(25);
        // Calon pembina INTERNAL: biasanya guru/guru BK, tapi kesiswaan
        // sendiri kadang jadi pembina juga. Untuk pembina LUAR SEKOLAH,
        // tidak perlu dropdown — cukup diketik bebas di form (lihat view).
        $calonPembina = User::whereIn('role', ['guru', 'guru_bk', 'kesiswaan'])->orderBy('name')->get();

        return view('ekstrakurikuler.index', compact('ekstrakurikuler', 'calonPembina'));
    }

    public function store(Request $request)
    {
        $validated = $this->validasi($request);

        $gagal = DB::transaction(function () use ($validated) {
            $ekskul = Ekstrakurikuler::create([
                'nama_ekstrakurikuler' => $validated['nama_ekstrakurikuler'],
                'keterangan' => $validated['keterangan'] ?? null,
                'is_aktif' => true,
            ]);

            return $this->simpanPembina($ekskul, $validated['pembina_internal'] ?? [], $validated['pembina_eksternal_nama'] ?? [], $validated['pembina_eksternal_kontak'] ?? []);
        });

        return back()->with($this->pesanSimpan($gagal, 'ditambahkan'));
    }

    public function update(Request $request, Ekstrakurikuler $ekstrakurikuler)
    {
        $validated = $this->validasi($request);
        $validated['is_aktif'] = $request->boolean('is_aktif', true);

        $gagal = DB::transaction(function () use ($validated, $ekstrakurikuler) {
            $ekstrakurikuler->update([
                'nama_ekstrakurikuler' => $validated['nama_ekstrakurikuler'],
                'keterangan' => $validated['keterangan'] ?? null,
                'is_aktif' => $validated['is_aktif'],
            ]);

            return $this->simpanPembina($ekstrakurikuler, $validated['pembina_internal'] ?? [], $validated['pembina_eksternal_nama'] ?? [], $validated['pembina_eksternal_kontak'] ?? []);
        });

        return back()->with($this->pesanSimpan($gagal, 'diperbarui'));
    }

    /**
     * @param  string[]  $gagalDikeluarkan  Nama pembina yang GAGAL dikeluarkan
     *         karena masih punya riwayat absensi (lihat simpanPembina()).
     */
    private function pesanSimpan(array $gagalDikeluarkan, string $kata): array
    {
        if (empty($gagalDikeluarkan)) {
            return ['success' => "Kegiatan ekstrakurikuler berhasil {$kata}."];
        }

        return ['success' => "Kegiatan ekstrakurikuler berhasil {$kata}. Catatan: " . implode(', ', $gagalDikeluarkan)
            . ' tidak bisa dikeluarkan dari daftar pembina karena sudah punya riwayat absensi — masih tercatat sebagai pembina.'];
    }

    public function destroy(Ekstrakurikuler $ekstrakurikuler)
    {
        return $this->hapusAtauGagalDenganPesan(
            $ekstrakurikuler,
            'Kegiatan ekstrakurikuler berhasil dihapus.',
            'Kegiatan ini tidak dapat dihapus karena masih dipakai di data lain (mis. anggota/absensi).'
        );
    }

    private function validasi(Request $request): array
    {
        return $request->validate([
            'nama_ekstrakurikuler' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'pembina_internal' => ['nullable', 'array'],
            'pembina_internal.*' => ['exists:users,id'],
            // (2026-08-23, revisi tampilan) — pembina luar sekolah sekarang
            // dikirim sebagai 2 array SEJAJAR (indeks ke-i nama pasangan
            // indeks ke-i kontak), hasil dari daftar "Input/Batal" di form
            // (lihat ekstrakurikuler/index.blade.php), bukan lagi 1 textarea
            // bebas per baris.
            'pembina_eksternal_nama' => ['nullable', 'array'],
            'pembina_eksternal_nama.*' => ['required', 'string', 'max:255'],
            'pembina_eksternal_kontak' => ['nullable', 'array'],
            'pembina_eksternal_kontak.*' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * Sinkronkan pembina, BUKAN hapus-lalu-buat-ulang seperti sebelumnya —
     * baris pembina INTERNAL yang MASIH dicentang dipertahankan apa
     * adanya (id-nya tidak berubah), supaya riwayat absensi ekskul yang
     * sudah tercatat atas nama pembina itu (lewat
     * absensi_ekskul_pesertas.ekstrakurikuler_pembina_id) tidak pernah
     * ikut terhapus hanya karena kegiatan ini di-Edit (mis. cuma ganti
     * keterangan, pembina yang dicentang sama sekali tidak berubah).
     *
     * Kalau ada pembina yang mau di-uncheck TAPI sudah punya riwayat
     * absensi, penghapusannya akan ditolak database (FK restrict, lihat
     * migrasi 2026_08_23_000006) — baris itu SENGAJA dibiarkan (tidak
     * dipaksa hilang) dan Kesiswaan diberi tahu lewat pesan supaya tidak
     * bingung kenapa pembina itu masih muncul walau sudah di-uncheck.
     */
    private function simpanPembina(Ekstrakurikuler $ekskul, array $userIdsInternal, array $namaEksternal, array $kontakEksternal): array
    {
        $userIdsInternal = array_values(array_unique($userIdsInternal));

        $pembinaInternalLama = $ekskul->pembinas()->whereNotNull('user_id')->get();
        $idUserLama = $pembinaInternalLama->pluck('user_id')->all();

        // Keluarkan pembina internal yang TIDAK LAGI dicentang.
        $gagalDikeluarkan = [];
        foreach ($pembinaInternalLama->whereNotIn('user_id', $userIdsInternal) as $lama) {
            try {
                $lama->delete();
            } catch (QueryException $e) {
                if ((int) $e->getCode() === 23000) {
                    // Masih punya riwayat absensi — jangan dipaksa hapus.
                    $gagalDikeluarkan[] = $lama->user?->name ?? 'pembina #' . $lama->id;
                    continue;
                }
                throw $e;
            }
        }

        // Tambahkan pembina internal yang BARU dicentang (belum ada baris sebelumnya).
        foreach ($userIdsInternal as $userId) {
            if (!in_array($userId, $idUserLama)) {
                EkstrakurikulerPembina::create([
                    'ekstrakurikuler_id' => $ekskul->id,
                    'user_id' => $userId,
                ]);
            }
        }

        // Pembina LUAR SEKOLAH tidak punya id stabil dari form (nama bisa
        // diketik ulang bebas), jadi tetap dicocokkan sederhana per baris:
        // yang jumlah/nama-nya persis sama TIDAK disentuh, yang berubah
        // dihapus+dibuat ulang (kalau yang dihapus ternyata sudah punya
        // riwayat absensi, sama seperti internal: ditolak & dibiarkan).
        $eksternalLama = $ekskul->pembinas()->whereNotNull('nama_eksternal')->get();
        $eksternalBaru = collect($namaEksternal)
            ->map(fn ($nama, $i) => [
                'nama' => trim($nama),
                'kontak' => isset($kontakEksternal[$i]) ? trim($kontakEksternal[$i]) ?: null : null,
            ])
            ->filter(fn ($x) => $x['nama'] !== '')
            ->values();

        $cocokLama = $eksternalLama->filter(
            fn ($l) => $eksternalBaru->contains(fn ($b) => $b['nama'] === $l->nama_eksternal && $b['kontak'] === $l->kontak_eksternal)
        );
        foreach ($eksternalLama->diff($cocokLama) as $lama) {
            try {
                $lama->delete();
            } catch (QueryException $e) {
                if ((int) $e->getCode() === 23000) {
                    $gagalDikeluarkan[] = $lama->nama_eksternal . ' (luar sekolah)';
                    continue;
                }
                throw $e;
            }
        }
        $namaKontakSudahAda = $cocokLama->map(fn ($l) => $l->nama_eksternal . '|' . $l->kontak_eksternal);
        foreach ($eksternalBaru as $b) {
            if (!$namaKontakSudahAda->contains($b['nama'] . '|' . $b['kontak'])) {
                EkstrakurikulerPembina::create([
                    'ekstrakurikuler_id' => $ekskul->id,
                    'nama_eksternal' => $b['nama'],
                    'kontak_eksternal' => $b['kontak'],
                ]);
            }
        }

        return $gagalDikeluarkan;
    }
}
