<?php

namespace App\Support;

/**
 * LAPORAN HASIL IMPORT EXCEL.
 *
 * =====================================================================
 * MASALAH YANG DIPECAHKAN
 * =====================================================================
 * Sebelum ini, SEMUA import Excel di aplikasi (Siswa, Kelas, Mata
 * Pelajaran, Jadwal, Pemetaan Guru Mengajar) memakai pola yang sama:
 * kalau sebuah baris tidak valid atau data rujukannya tidak ketemu,
 * method model() cukup `return null` — dan Maatwebsite/Excel akan
 * MELEWATI baris itu diam-diam. Setelah selesai, pengguna selalu
 * disuguhi pesan hijau "Import berhasil".
 *
 * Akibatnya fatal untuk sekolah: admin mengunggah 300 siswa, 40 baris di
 * antaranya salah ketik kode_kelas atau namanya kosong, lalu sistem
 * bilang BERHASIL — padahal 40 siswa itu tidak pernah masuk. Tidak ada
 * satu pun petunjuk baris mana yang bermasalah. Data hilang tanpa jejak,
 * dan biasanya baru ketahuan berminggu-minggu kemudian.
 *
 * (Satu-satunya pengecualian dulu adalah Import Siswa, yang mencatat NIS
 * dengan kode_kelas tidak ditemukan — tapi hanya untuk SATU alasan itu,
 * tanpa nomor baris, dan tidak ada di empat import lainnya.)
 *
 * =====================================================================
 * SOLUSINYA
 * =====================================================================
 * Setiap Import sekarang mengisi objek ini: berapa baris dibuat, berapa
 * diperbarui, dan SETIAP baris yang dilewati beserta NOMOR BARIS di file
 * Excel-nya serta ALASAN yang bisa dibaca orang. Hasilnya ditampilkan
 * kembali ke pengguna lewat komponen <x-hasil-import />.
 *
 * Nomor barisnya nomor asli di Excel (baris 1 = header), bukan hasil
 * hitungan sendiri — itu sebabnya semua Import memakai OnEachRow, bukan
 * ToModel, karena OnEachRow memberi Row::getIndex() yang sesungguhnya.
 */
class HasilImport
{
    public int $dibuat = 0;
    public int $diperbarui = 0;

    /** @var array<int, array{baris: int, penanda: string, alasan: string}> */
    public array $dilewati = [];

    public function __construct(public readonly string $namaData = 'data') {}

    public function catatDibuat(): void
    {
        $this->dibuat++;
    }

    public function catatDiperbarui(): void
    {
        $this->diperbarui++;
    }

    /**
     * Catat satu baris yang tidak jadi diproses.
     *
     * @param  int     $baris    nomor baris di file Excel (baris 1 = header)
     * @param  string  $alasan   penjelasan singkat, ditulis untuk dibaca operator sekolah
     * @param  string  $penanda  penciri baris supaya mudah dicari, mis. "NIS 1234" atau "Kelas 7A"
     */
    public function lewati(int $baris, string $alasan, string $penanda = ''): void
    {
        $this->dilewati[] = [
            'baris' => $baris,
            'penanda' => $penanda,
            'alasan' => $alasan,
        ];
    }

    public function totalBerhasil(): int
    {
        return $this->dibuat + $this->diperbarui;
    }

    public function totalDilewati(): int
    {
        return count($this->dilewati);
    }

    public function adaYangDilewati(): bool
    {
        return $this->dilewati !== [];
    }

    /** Tidak ada satu baris pun yang berhasil diproses. */
    public function gagalTotal(): bool
    {
        return $this->totalBerhasil() === 0;
    }

    /** Kalimat ringkas untuk notifikasi di atas halaman. */
    public function ringkasan(): string
    {
        if ($this->gagalTotal() && ! $this->adaYangDilewati()) {
            return "Tidak ada {$this->namaData} yang diproses — file yang diunggah tidak berisi baris data.";
        }

        $bagian = [];
        if ($this->dibuat > 0) {
            $bagian[] = "{$this->dibuat} baru ditambahkan";
        }
        if ($this->diperbarui > 0) {
            $bagian[] = "{$this->diperbarui} diperbarui";
        }

        $pesan = $bagian === []
            ? "Tidak ada {$this->namaData} yang tersimpan"
            : 'Import '.$this->namaData.' selesai: '.implode(' dan ', $bagian);

        if ($this->adaYangDilewati()) {
            $pesan .= '. '.$this->totalDilewati().' baris DILEWATI — rinciannya di bawah';
        }

        return $pesan.'.';
    }

    /** Bentuk array untuk disimpan di session (objek tidak selalu aman di-serialize). */
    public function toArray(): array
    {
        return [
            'nama_data' => $this->namaData,
            'dibuat' => $this->dibuat,
            'diperbarui' => $this->diperbarui,
            'dilewati' => $this->dilewati,
            'ringkasan' => $this->ringkasan(),
        ];
    }
}
