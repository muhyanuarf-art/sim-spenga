<?php

namespace App\Imports;

use App\Models\MataPelajaran;

/**
 * Format kolom excel (judul di baris 1):
 * kode | nama_mapel
 * Contoh: MTK | Matematika
 */
class MataPelajaranImport extends ImportDasar
{
    public function __construct()
    {
        parent::__construct('mata pelajaran');
    }

    protected function kolomWajib(): array
    {
        return ['kode', 'nama_mapel'];
    }

    protected function prosesBaris(array $data, int $baris): void
    {
        $kode = $this->teks($data, 'kode');
        $nama = $this->teks($data, 'nama_mapel');

        if ($kode === '') {
            $this->hasil->lewati($baris, 'Kolom "kode" kosong.', $nama !== '' ? $nama : '');

            return;
        }

        if ($nama === '') {
            $this->hasil->lewati($baris, 'Kolom "nama_mapel" kosong.', 'Kode '.$kode);

            return;
        }

        $this->catat(MataPelajaran::updateOrCreate(
            ['kode' => $kode],
            ['nama_mapel' => $nama]
        ));
    }
}
