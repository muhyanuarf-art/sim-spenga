<?php

namespace App\Imports;

use App\Models\GuruMengajarKelas;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;

/**
 * Format kolom excel (judul di baris 1):
 * nip_guru | kode_kelas | kode_mapel
 * Contoh   : 198501012010011001 | 7A | MTK
 */
class GuruMengajarImport extends ImportDasar
{
    public function __construct(private int $tahunAjaranId)
    {
        parent::__construct('pemetaan guru mengajar');
    }

    protected function kolomWajib(): array
    {
        return ['nip_guru', 'kode_kelas', 'kode_mapel'];
    }

    protected function prosesBaris(array $data, int $baris): void
    {
        $nip = $this->teks($data, 'nip_guru');
        $kodeKelas = $this->teks($data, 'kode_kelas');
        $kodeMapel = $this->teks($data, 'kode_mapel');

        $penanda = trim($kodeKelas.' '.$kodeMapel);

        if ($nip === '' || $kodeKelas === '' || $kodeMapel === '') {
            $this->hasil->lewati($baris, 'Ada kolom wajib yang kosong (nip_guru, kode_kelas, kode_mapel).', $penanda);

            return;
        }

        $guru = User::where('nip', $nip)->where('role', 'guru')->first();
        if (! $guru) {
            $this->hasil->lewati($baris, 'NIP "'.$nip.'" tidak ditemukan sebagai guru di menu Kelola Pengguna.', $penanda);

            return;
        }

        // STEP 5 — kelas harus berasal dari TAHUN AJARAN YANG SAMA dengan
        // pemetaan ini (Bagian 16), bukan kelas mana pun yang kebetulan
        // nama_kelas-nya cocok.
        $kelas = Kelas::untukTahunAjaranId($this->tahunAjaranId)->where('nama_kelas', $kodeKelas)->first();
        if (! $kelas) {
            $this->hasil->lewati($baris, 'Kelas "'.$kodeKelas.'" tidak ada pada tahun ajaran yang dipilih.', $penanda);

            return;
        }

        $mapel = MataPelajaran::periodeAktif()->where('kode', $kodeMapel)->first();
        if (! $mapel) {
            $this->hasil->lewati($baris, 'Kode mata pelajaran "'.$kodeMapel.'" tidak ada di menu Mata Pelajaran.', $penanda);

            return;
        }

        // PERBAIKAN — dulu memakai `new GuruMengajarKelas([...])`, yaitu
        // INSERT baru tanpa syarat. Tabelnya punya unique index
        // (guru_id, kelas_id, mata_pelajaran_id, tahun_ajaran_id), sehingga
        // mengimpor ulang file yang sama — hal yang wajar dilakukan saat
        // memperbaiki beberapa baris — langsung menabrak galat SQL
        // "Duplicate entry" dan seluruh halaman error. firstOrCreate membuat
        // import ini aman diulang.
        $this->catat(GuruMengajarKelas::firstOrCreate([
            'guru_id' => $guru->id,
            'kelas_id' => $kelas->id,
            'mata_pelajaran_id' => $mapel->id,
            'tahun_ajaran_id' => $this->tahunAjaranId,
        ]));
    }
}
