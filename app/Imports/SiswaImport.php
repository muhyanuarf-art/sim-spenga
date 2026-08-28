<?php

namespace App\Imports;

use App\Models\Kelas;
use App\Models\RiwayatKelasSiswa;
use App\Models\Siswa;
use App\Models\TahunAjaran;

/**
 * Format kolom excel (judul di baris 1):
 * nis | nisn | nama | nama_ortu | no_wa_ortu | jenis_kelamin | kode_kelas
 *
 * (Revisi permintaan admin) — fitur "Kenaikan Kelas" sudah dihapus. Sekolah
 * ini memindahkan siswa antar kelas/tahun ajaran LEWAT IMPORT EXCEL ini,
 * bukan lewat proses kenaikan kelas manual. Supaya histori kelas siswa
 * (Riwayat Kelas) TETAP tersimpan & bisa dilihat admin maupun orang tua,
 * import ini otomatis mencatat baris riwayat_kelas_siswas setiap kali
 * kelas_id seorang siswa berubah.
 */
class SiswaImport extends ImportDasar
{
    /** Dibaca sekali di awal, bukan per baris (dulu query berulang tiap baris). */
    private ?TahunAjaran $tahunAjaranAktif = null;

    private bool $periodeSudahDibaca = false;

    public function __construct()
    {
        parent::__construct('data siswa');
    }

    protected function kolomWajib(): array
    {
        return ['nis', 'nama', 'kode_kelas'];
    }

    protected function prosesBaris(array $data, int $baris): void
    {
        $nis = $this->teks($data, 'nis');
        $nama = $this->teks($data, 'nama');
        $kodeKelas = $this->teks($data, 'kode_kelas');

        if ($nis === '') {
            $this->hasil->lewati($baris, 'Kolom "nis" kosong — NIS wajib diisi karena dipakai sebagai penciri siswa.', $nama);

            return;
        }

        if ($nama === '') {
            $this->hasil->lewati($baris, 'Kolom "nama" kosong.', 'NIS '.$nis);

            return;
        }

        if ($kodeKelas === '') {
            $this->hasil->lewati($baris, 'Kolom "kode_kelas" kosong.', 'NIS '.$nis);

            return;
        }

        $kelas = Kelas::aktif()->where('nama_kelas', $kodeKelas)->first();

        if (! $kelas) {
            $this->hasil->lewati(
                $baris,
                'Kelas "'.$kodeKelas.'" tidak ada pada tahun ajaran yang sedang aktif. '
                    .'Buat dulu kelasnya di menu Data Kelas, atau perbaiki penulisannya.',
                'NIS '.$nis
            );

            return;
        }

        $siswaLama = Siswa::where('nis', $nis)->first();
        $kelasAsalId = $siswaLama?->kelas_id; // null kalau siswa baru sama sekali

        $siswa = Siswa::updateOrCreate(
            ['nis' => $nis],
            [
                'nisn' => $this->teks($data, 'nisn') ?: null,
                'nama' => $nama,
                'nama_ortu' => $this->teks($data, 'nama_ortu') ?: null,
                'no_wa_ortu' => $this->teks($data, 'no_wa_ortu') ?: null,
                'jenis_kelamin' => strtoupper($this->teks($data, 'jenis_kelamin')) === 'P' ? 'P' : 'L',
                'is_active' => true,
            ]
        );

        // Kelas siswa disimpan per SEMESTER di anggota_kelas, bukan lagi
        // kolom di tabel siswas (migrasi 2026_08_29_000001).
        App\Models\AnggotaKelas::tempatkan($siswa->id, $kelas);

        $this->catat($siswa);
        $this->catatRiwayatKelas($siswa, $kelas->id, $kelasAsalId);
    }

    /**
     * Catat Riwayat Kelas untuk Tahun Ajaran aktif — baik untuk siswa baru
     * (kelas_asal_id = null, "awal masuk") maupun siswa lama yang naik/pindah
     * kelas lewat import. Konvensi tahun_ajaran_id SELALU baris Semester
     * Ganjil.
     *
     * jenis dibedakan dari 'pindah_kelas' (mutasi di tengah tahun ajaran,
     * lihat SiswaController::pindahKelas()) supaya keduanya bisa hidup
     * berdampingan pada tahun ajaran yang sama. firstOrCreate juga di-scope
     * dengan jenis, supaya import berulang tidak membuat baris ganda.
     */
    private function catatRiwayatKelas(Siswa $siswa, int $kelasId, ?int $kelasAsalId): void
    {
        if (! $this->periodeSudahDibaca) {
            $aktif = TahunAjaran::aktif();
            $this->tahunAjaranAktif = $aktif
                ? TahunAjaran::where('nama', $aktif->nama)->where('semester', 'Ganjil')->first()
                : null;
            $this->periodeSudahDibaca = true;
        }

        if (! $this->tahunAjaranAktif) {
            return;
        }

        RiwayatKelasSiswa::firstOrCreate(
            [
                'siswa_id' => $siswa->id,
                'tahun_ajaran_id' => $this->tahunAjaranAktif->id,
                'jenis' => $kelasAsalId === null
                    ? RiwayatKelasSiswa::JENIS_AWAL_MASUK
                    : RiwayatKelasSiswa::JENIS_KENAIKAN_KELAS,
            ],
            [
                'kelas_asal_id' => $kelasAsalId,
                'kelas_id' => $kelasId,
                'tanggal_mutasi' => now()->toDateString(),
                'keterangan' => 'Dicatat otomatis dari Import Excel Data Siswa.',
                'dicatat_oleh_id' => auth()->id(),
            ]
        );
    }
}
