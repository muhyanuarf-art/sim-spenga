<?php

namespace App\Support;

use App\Jobs\KirimNotifikasiAlfaWhatsapp;
use App\Models\AbsensiSiswa;
use App\Models\NotifikasiAlfaTerkirim;
use Illuminate\Support\Carbon;

/**
 * SATU pintu untuk memutuskan & mengantrikan notifikasi WhatsApp "anak
 * Anda Alfa" ke orang tua.
 *
 * Dulu logika ini hanya ada di MengajarController (jalur KBM). Sekarang
 * absensi juga bisa berasal dari KEGIATAN SEKOLAH yang diisi wali kelas
 * (lomba, asesmen, classmeeting, pesantren Ramadan). Supaya aturannya
 * tidak bercabang dua dan bisa berbeda diam-diam, keduanya memakai kelas
 * ini.
 *
 * Aturan yang dijaga di sini:
 *
 * 1. Yang dikirimi WA hanya siswa yang status FINAL-nya hari itu = Alfa
 *    (lihat AbsensiSiswa::finalPerHari). Jadi kalau siswa Alfa di jam
 *    ke-1 tetapi hadir di sesi/kegiatan yang lebih menentukan, tidak ada
 *    WA yang dikirim.
 * 2. Anti-duplikat: 1 siswa maksimal 1 notifikasi per tanggal, dijaga
 *    oleh unique(siswa_id, tanggal) di notifikasi_alfa_terkirims.
 * 3. Pengisian TERLAMBAT (absensi untuk tanggal yang sudah lewat) tetap
 *    dicatat, tapi WA-nya sengaja TIDAK dikirim — mengabari orang tua
 *    soal Alfa beberapa hari lalu sudah tidak relevan dan berisiko
 *    membuat panik. Barisnya tetap dibuat dengan status 'dilewati'.
 * 4. Kegiatan sekolah yang dikonfigurasi "tanpa notifikasi WA" juga
 *    dicatat sebagai 'dilewati', lengkap dengan alasannya.
 * 5. Pengiriman sesungguhnya dilakukan lewat queue (job), bukan di
 *    request yang sedang berjalan — guru/wali kelas tidak perlu menunggu.
 */
class NotifikasiAlfa
{
    /**
     * @param  array<int|string, string>  $absensi  Peta siswa_id => status yang BARU disimpan.
     */
    public static function proses(array $absensi, string $tanggal): void
    {
        $siswaAlfa = collect($absensi)->filter(fn ($status) => $status === 'Alfa')->keys();
        if ($siswaAlfa->isEmpty()) {
            return;
        }

        $tanggalBukanHariIni = ! Carbon::parse($tanggal)->isToday();

        foreach ($siswaAlfa as $siswaId) {
            $records = AbsensiSiswa::where('siswa_id', $siswaId)
                ->whereDate('tanggal', $tanggal)
                ->with(AbsensiSiswa::RELASI_KONTEKS)
                ->get();

            $final = AbsensiSiswa::finalPerHari($records)->first();
            if (! $final || $final->status !== 'Alfa') {
                continue;
            }

            $kegiatan = $final->dariKegiatan() ? $final->kegiatan() : null;
            $mapel = $kegiatan ? null : $final->jurnal?->mapel?->nama_mapel;
            $jamKe = $kegiatan
                ? null
                : ($final->jurnal?->jamPelajaranAkhir?->jam_ke ?? $final->jurnal?->jamPelajaran?->jam_ke);

            $dilewatiKarena = match (true) {
                $tanggalBukanHariIni => 'Tidak dikirim: absensi diisi terlambat (untuk tanggal '
                    .Carbon::parse($tanggal)->translatedFormat('d M Y')
                    .', bukan tanggal saat diisi). Kejadian Alfa tetap tercatat, hanya notifikasi WA yang sengaja dilewati.',
                $kegiatan && ! $kegiatan->kirim_wa_alfa => "Tidak dikirim: kegiatan \"{$kegiatan->nama}\" diatur tanpa notifikasi WhatsApp. Kejadian Alfa tetap tercatat.",
                default => null,
            };

            // Anti-duplikat: kalau baris untuk siswa+tanggal ini sudah ada,
            // berarti sudah pernah diantrikan (atau sengaja dilewati)
            // sebelumnya — tidak diapa-apakan lagi.
            $baris = NotifikasiAlfaTerkirim::firstOrCreate(
                ['siswa_id' => $siswaId, 'tanggal' => $tanggal],
                [
                    'status_kirim' => $dilewatiKarena ? 'dilewati' : 'pending',
                    'keterangan_gagal' => $dilewatiKarena,
                    'mata_pelajaran_id' => $kegiatan ? null : $final->jurnal?->mata_pelajaran_id,
                    'kegiatan_sekolah_id' => $kegiatan?->id,
                    'jam_ke' => $jamKe,
                ]
            );

            if (! $baris->wasRecentlyCreated || $dilewatiKarena) {
                continue;
            }

            KirimNotifikasiAlfaWhatsapp::dispatch(
                (int) $siswaId,
                $tanggal,
                $mapel,
                $jamKe,
                $kegiatan?->nama,
            );
        }
    }
}
