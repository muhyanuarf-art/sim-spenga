<?php

namespace App\Imports;

use App\Models\JadwalPelajaran;
use App\Models\JamPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;

/**
 * Format kolom excel (judul di baris 1):
 * hari | kode_kelas | jam_ke | kode_mapel | nip_guru
 * Contoh: Senin | 7A | 1 | MTK | 198501012010011001
 */
class JadwalImport extends ImportDasar
{
    public function __construct(private int $tahunAjaranId)
    {
        parent::__construct('jadwal pelajaran');
    }

    protected function kolomWajib(): array
    {
        return ['hari', 'kode_kelas', 'jam_ke', 'kode_mapel', 'nip_guru'];
    }

    protected function prosesBaris(array $data, int $baris): void
    {
        $hari = ucfirst(strtolower($this->teks($data, 'hari')));
        $kodeKelas = $this->teks($data, 'kode_kelas');
        $jamKe = $this->angka($data, 'jam_ke');
        $kodeMapel = $this->teks($data, 'kode_mapel');
        $nip = $this->teks($data, 'nip_guru');

        $penanda = trim($hari.' '.$kodeKelas.' jam '.($jamKe ?: '?'));

        if ($hari === '' || $kodeKelas === '' || $jamKe < 1 || $kodeMapel === '' || $nip === '') {
            $this->hasil->lewati($baris, 'Ada kolom wajib yang kosong atau jam_ke bukan angka.', $penanda);

            return;
        }

        // STEP 5 — kelas harus berasal dari TAHUN AJARAN YANG SAMA dengan
        // jadwal ini (Bagian 17), bukan kelas mana pun yang kebetulan
        // nama_kelas-nya cocok.
        $kelas = Kelas::untukTahunAjaranId($this->tahunAjaranId)->where('nama_kelas', $kodeKelas)->first();
        if (! $kelas) {
            $this->hasil->lewati($baris, 'Kelas "'.$kodeKelas.'" tidak ada pada tahun ajaran yang dipilih.', $penanda);

            return;
        }

        $mapel = MataPelajaran::where('kode', $kodeMapel)->first();
        if (! $mapel) {
            $this->hasil->lewati($baris, 'Kode mata pelajaran "'.$kodeMapel.'" tidak ada di menu Mata Pelajaran.', $penanda);

            return;
        }

        $guru = User::where('nip', $nip)->where('role', 'guru')->first();
        if (! $guru) {
            $this->hasil->lewati($baris, 'NIP "'.$nip.'" tidak ditemukan sebagai guru di menu Kelola Pengguna.', $penanda);

            return;
        }

        $jam = JamPelajaran::where('hari', $hari)->where('jam_ke', $jamKe)->first();
        if (! $jam) {
            $this->hasil->lewati(
                $baris,
                'Jam ke-'.$jamKe.' untuk hari '.$hari.' belum diatur di menu Jam Pelajaran.',
                $penanda
            );

            return;
        }

        $this->catat(JadwalPelajaran::updateOrCreate(
            [
                'hari' => $hari,
                'kelas_id' => $kelas->id,
                'jam_pelajaran_id' => $jam->id,
                'tahun_ajaran_id' => $this->tahunAjaranId,
            ],
            [
                'mata_pelajaran_id' => $mapel->id,
                'guru_id' => $guru->id,
            ]
        ));
    }
}
