<?php

namespace App\Support;

use App\Jobs\KirimNotifikasiAlfaJob;
use App\Models\AbsensiSiswa;
use App\Models\Kelas;
use App\Models\NotifikasiWa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotifikasiAlfaDispatcher
{
    /**
     * Dipanggil setelah guru mapel menyimpan absensi 1 sesi. Menentukan
     * siswa mana di kelas ini, pada tanggal ini, yang status AKHIRNYA
     * (bukan sekadar salah satu mapel) adalah Alfa — memakai aturan yang
     * sama dengan AbsensiSiswa::finalPerHari() (guru mapel jam paling
     * akhir yang menentukan) — lalu dispatch 1 job WA per siswa yang
     * BELUM punya notifikasi hari ini.
     *
     * Kalau siswa yang tadinya Alfa ternyata direvisi jadi Hadir/Sakit/Izin
     * (guru mapel jam akhir mengoreksi), notifikasi yang SUDAH terlanjur
     * terkirim tidak ditarik balik — hanya dicegah pengiriman baru.
     */
    public function prosesKelasTanggal(int $kelasId, string $tanggal): void
    {
        $siswaAlfa = AbsensiSiswa::siswaAlfaHariIniByTanggal($kelasId, $tanggal);

        foreach ($siswaAlfa as $item) {
            $this->buatNotifikasiJikaBelumAda($item['siswa'], $kelasId, $tanggal);
        }
    }

    private function buatNotifikasiJikaBelumAda($siswa, int $kelasId, string $tanggal): void
    {
        try {
            // insertOrIgnore + unique(siswa_id, tanggal) di migration adalah
            // penjamin utama anti-duplikat (aman dari race condition 2
            // request submit absensi yang hampir bersamaan). updateOrCreate
            // biasa TIDAK cukup untuk itu, makanya pakai transaksi + unique
            // constraint di sini.
            $notif = DB::transaction(function () use ($siswa, $kelasId, $tanggal) {
                $ada = NotifikasiWa::where('siswa_id', $siswa->id)
                    ->where('tanggal', $tanggal)
                    ->lockForUpdate()
                    ->exists();

                if ($ada) {
                    return null;
                }

                return NotifikasiWa::create([
                    'siswa_id' => $siswa->id,
                    'kelas_id' => $kelasId,
                    'tanggal' => $tanggal,
                    'status' => 'menunggu',
                    'percobaan_ke' => 1,
                    'no_hp_tujuan' => $siswa->no_hp_ortu,
                ]);
            });

            if ($notif) {
                KirimNotifikasiAlfaJob::dispatch($notif->id);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Kode 23000 = pelanggaran unique constraint: berarti ada
            // request lain yang barusan membuat baris yang sama duluan.
            // Ini SKENARIO NORMAL (dedup bekerja), bukan error nyata.
            if (! str_contains($e->getMessage(), '23000') && $e->getCode() != 23000) {
                throw $e;
            }
            Log::info("Notifikasi WA siswa {$siswa->id} tanggal {$tanggal} sudah ada (dedup), dilewati.");
        }
    }

    /**
     * Dipanggil dari webhook saat status pesan jadi "gagal". Kirim ulang
     * HANYA jika belum mencapai batas MAKS_PERCOBAAN (2x total).
     */
    public function cobaLagiJikaBelumMentok(NotifikasiWa $notif): void
    {
        if (! $notif->bisaDicobaLagi()) {
            return;
        }

        $notif->update([
            'status' => 'menunggu',
            'percobaan_ke' => $notif->percobaan_ke + 1,
            'wa_message_id' => null,
        ]);

        KirimNotifikasiAlfaJob::dispatch($notif->id);
    }
}
