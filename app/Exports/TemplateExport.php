<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export generik untuk file "Download Template Excel".
 *
 * Baris pertama = header kolom (harus sesuai kunci yang dibaca proses Import).
 * Baris kedua dst = contoh pengisian, supaya pengguna tahu format datanya,
 * lalu baris contoh ini tinggal dihapus/diganti dengan data asli sebelum diupload.
 */
class TemplateExport implements FromArray, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    /**
     * @param  array<int,string>  $headings  Nama kolom, harus sama persis dengan header yang dibaca Import.
     * @param  array<int,array<int,mixed>>  $contohBaris  Satu atau beberapa baris contoh pengisian.
     * @param  string  $judul  Nama sheet.
     * @param  array<int,string>  $catatan  Baris catatan/petunjuk tambahan, ditulis di bawah data.
     */
    public function __construct(
        private array $headings,
        private array $contohBaris,
        private string $judul = 'Template',
        private array $catatan = [],
    ) {}

    public function array(): array
    {
        $rows = $this->contohBaris;

        if (! empty($this->catatan)) {
            $rows[] = array_fill(0, count($this->headings), '');
            foreach ($this->catatan as $baris) {
                $rows[] = array_pad([$baris], count($this->headings), '');
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return $this->judul;
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = $sheet->getHighestColumn();
        $sheet->getStyle('1:1')->getFont()->setBold(true);
        $sheet->getStyle('A1:' . $lastCol . '1')->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DBEAFE');

        // Baris contoh (baris 2 dst sebelum catatan) diberi warna lembut sbg penanda "contoh".
        $jumlahContoh = count($this->contohBaris);
        if ($jumlahContoh > 0) {
            $range = 'A2:' . $lastCol . (1 + $jumlahContoh);
            $sheet->getStyle($range)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0FDF4');
            $sheet->getStyle($range)->getFont()->setItalic(true);
        }

        return [];
    }
}
