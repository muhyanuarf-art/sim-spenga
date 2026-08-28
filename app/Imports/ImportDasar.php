<?php

namespace App\Imports;

use App\Support\HasilImport;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use RuntimeException;

/**
 * Dasar bersama untuk SELURUH import Excel di aplikasi ini.
 *
 * Dibuat supaya kelima import (Siswa, Kelas, Mata Pelajaran, Jadwal,
 * Pemetaan Guru Mengajar) berperilaku sama persis dalam tiga hal yang
 * dulu tidak ada satu pun di antaranya:
 *
 * 1. MELAPORKAN BARIS YANG DILEWATI. Dulu baris bermasalah cukup
 *    `return null` dan hilang diam-diam sementara pengguna tetap
 *    diberi pesan "Import berhasil" (lihat App\Support\HasilImport).
 * 2. MEMERIKSA JUDUL KOLOM lebih dulu. Kalau operator mengunggah file
 *    yang kolomnya tidak sesuai template, dulu setiap baris gagal diam-
 *    diam (bahkan bisa memicu galat "Undefined array key"). Sekarang
 *    ditolak sejak awal dengan pesan yang menyebut kolom mana yang kurang.
 * 3. NOMOR BARIS YANG SEBENARNYA. Memakai OnEachRow (bukan ToModel)
 *    supaya Row::getIndex() memberi nomor baris asli di file Excel —
 *    jadi pesan kesalahannya bisa menunjuk "baris 47", bukan sekadar
 *    "ada baris yang gagal".
 */
abstract class ImportDasar implements OnEachRow, WithHeadingRow, SkipsEmptyRows
{
    public HasilImport $hasil;

    private bool $judulSudahDiperiksa = false;

    public function __construct(string $namaData)
    {
        $this->hasil = new HasilImport($namaData);
    }

    /** Kolom yang WAJIB ada di baris judul file Excel. */
    abstract protected function kolomWajib(): array;

    /**
     * Proses satu baris data.
     *
     * @param  array  $data   isi baris, kuncinya nama kolom dari baris judul
     * @param  int    $baris  nomor baris asli di file Excel
     */
    abstract protected function prosesBaris(array $data, int $baris): void;

    public function onRow(Row $row): void
    {
        $data = $row->toArray();

        $this->periksaJudulKolom($data);
        $this->prosesBaris($data, $row->getIndex());
    }

    /**
     * Pastikan file yang diunggah memakai template yang benar. Diperiksa
     * sekali saja pada baris pertama; kalau tidak cocok, seluruh proses
     * dihentikan supaya tidak ada data setengah masuk.
     */
    private function periksaJudulKolom(array $data): void
    {
        if ($this->judulSudahDiperiksa) {
            return;
        }
        $this->judulSudahDiperiksa = true;

        $kurang = array_diff($this->kolomWajib(), array_keys($data));

        if ($kurang !== []) {
            throw new RuntimeException(
                'Judul kolom pada file tidak sesuai template. Kolom yang belum ada: '
                .implode(', ', $kurang)
                .'. Unduh ulang templatenya, lalu isi tanpa mengubah baris judul di baris pertama.'
            );
        }
    }

    /** Ambil isi sel sebagai teks yang sudah dirapikan; aman walau kolomnya kosong. */
    protected function teks(array $data, string $kolom): string
    {
        return trim((string) ($data[$kolom] ?? ''));
    }

    /** Ambil isi sel sebagai bilangan bulat; 0 kalau kosong/bukan angka. */
    protected function angka(array $data, string $kolom): int
    {
        return (int) $this->teks($data, $kolom);
    }

    /**
     * Catat hasil simpan sebuah model: dihitung "baru" atau "diperbarui".
     * wasRecentlyCreated hanya bernilai true untuk baris yang benar-benar
     * baru dibuat, jadi bisa dipakai membedakan keduanya.
     */
    protected function catat(\Illuminate\Database\Eloquent\Model $model): void
    {
        $model->wasRecentlyCreated ? $this->hasil->catatDibuat() : $this->hasil->catatDiperbarui();
    }
}
