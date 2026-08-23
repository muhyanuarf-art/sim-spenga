<?php

namespace App\Http\Controllers;

use App\Models\Ekstrakurikuler;
use App\Models\EkstrakurikulerPembina;
use App\Models\User;
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
        $ekstrakurikuler = Ekstrakurikuler::with('pembinas.user')->orderBy('nama_ekstrakurikuler')->paginate(25);
        // Calon pembina INTERNAL: biasanya guru/guru BK, tapi kesiswaan
        // sendiri kadang jadi pembina juga. Untuk pembina LUAR SEKOLAH,
        // tidak perlu dropdown — cukup diketik bebas di form (lihat view).
        $calonPembina = User::whereIn('role', ['guru', 'guru_bk', 'kesiswaan'])->orderBy('name')->get();

        return view('ekstrakurikuler.index', compact('ekstrakurikuler', 'calonPembina'));
    }

    public function store(Request $request)
    {
        $validated = $this->validasi($request);

        DB::transaction(function () use ($validated) {
            $ekskul = Ekstrakurikuler::create([
                'nama_ekstrakurikuler' => $validated['nama_ekstrakurikuler'],
                'keterangan' => $validated['keterangan'] ?? null,
                'is_aktif' => true,
            ]);

            $this->simpanPembina($ekskul, $validated['pembina_internal'] ?? [], $validated['pembina_eksternal_nama'] ?? [], $validated['pembina_eksternal_kontak'] ?? []);
        });

        return back()->with('success', 'Kegiatan ekstrakurikuler berhasil ditambahkan.');
    }

    public function update(Request $request, Ekstrakurikuler $ekstrakurikuler)
    {
        $validated = $this->validasi($request);
        $validated['is_aktif'] = $request->boolean('is_aktif', true);

        DB::transaction(function () use ($validated, $ekstrakurikuler) {
            $ekstrakurikuler->update([
                'nama_ekstrakurikuler' => $validated['nama_ekstrakurikuler'],
                'keterangan' => $validated['keterangan'] ?? null,
                'is_aktif' => $validated['is_aktif'],
            ]);

            $this->simpanPembina($ekstrakurikuler, $validated['pembina_internal'] ?? [], $validated['pembina_eksternal_nama'] ?? [], $validated['pembina_eksternal_kontak'] ?? []);
        });

        return back()->with('success', 'Kegiatan ekstrakurikuler berhasil diperbarui.');
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
     * Ganti seluruh baris pembina kegiatan ini dengan yang baru dikirim
     * dari form (hapus lalu buat ulang — lebih sederhana & aman daripada
     * diff baris per baris untuk jumlah pembina yang biasanya kecil).
     */
    private function simpanPembina(Ekstrakurikuler $ekskul, array $userIdsInternal, array $namaEksternal, array $kontakEksternal): void
    {
        $ekskul->pembinas()->delete();

        foreach (array_unique($userIdsInternal) as $userId) {
            EkstrakurikulerPembina::create([
                'ekstrakurikuler_id' => $ekskul->id,
                'user_id' => $userId,
            ]);
        }

        foreach ($namaEksternal as $i => $nama) {
            $nama = trim($nama);
            if ($nama === '') {
                continue;
            }
            $kontak = isset($kontakEksternal[$i]) ? trim($kontakEksternal[$i]) : null;
            EkstrakurikulerPembina::create([
                'ekstrakurikuler_id' => $ekskul->id,
                'nama_eksternal' => $nama,
                'kontak_eksternal' => $kontak ?: null,
            ]);
        }
    }
}
