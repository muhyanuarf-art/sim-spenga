<?php

namespace App\Support;

use App\Imports\ImportDasar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;
use Throwable;

/**
 * SATU PINTU untuk menjalankan seluruh import Excel di aplikasi ini.
 *
 * Sebelumnya kelima controller memanggil Excel::import() begitu saja tanpa
 * penanganan galat sama sekali:
 *
 *   Excel::import(new SiswaImport(), $request->file('file'));
 *   return redirect()->route('siswa.index')->with('success', 'Import berhasil.');
 *
 * Dua akibatnya:
 *
 * 1. File rusak, format bukan Excel yang sesungguhnya (mis. .xlsx palsu
 *    hasil rename), atau pelanggaran unique index di database langsung
 *    memunculkan HALAMAN ERROR 500 mentah. Operator sekolah tidak tahu
 *    apa yang salah dan data bisa masuk separuh.
 * 2. Pesan "berhasil" SELALU muncul, bahkan ketika tidak ada satu baris
 *    pun yang tersimpan.
 *
 * Sekarang seluruh galat ditangkap dan diterjemahkan ke bahasa yang bisa
 * dipahami operator, lalu pengguna dikembalikan ke halaman import beserta
 * laporan hasilnya (lihat App\Support\HasilImport & komponen
 * <x-hasil-import />), bukan dilempar ke layar error.
 */
class JalankanImport
{
    /** Batas ukuran berkas dalam kilobyte (10 MB) — jauh di atas kebutuhan wajar satu sekolah. */
    private const MAKS_KB = 10240;

    /**
     * Aturan validasi berkas unggahan, sama untuk kelima import.
     *
     * @return array{0: array, 1: array} [aturan, pesan]
     */
    public static function aturanBerkas(): array
    {
        return [
            ['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:'.self::MAKS_KB]],
            [
                // Pesan bawaan Laravel berbahasa Inggris, sementara seluruh
                // aplikasi ini berbahasa Indonesia. Ditulis ulang di sini
                // supaya operator sekolah paham apa yang harus diperbaiki.
                'file.required' => 'Pilih dulu berkas Excel yang mau diunggah.',
                'file.file' => 'Berkas gagal diunggah. Coba pilih ulang berkasnya.',
                'file.mimes' => 'Berkas harus berformat Excel (.xlsx atau .xls) atau CSV. '
                    .'Berkas yang Anda pilih bukan salah satunya — pastikan bukan file lain yang namanya sekadar diubah.',
                'file.max' => 'Ukuran berkas melebihi '.(self::MAKS_KB / 1024).' MB. Pecah datanya menjadi beberapa berkas.',
            ],
        ];
    }

    /**
     * @param  ImportDasar   $import         objek import yang akan dijalankan
     * @param  UploadedFile  $berkas         file yang diunggah
     * @param  string        $rutePenerima   nama route halaman import (tempat hasil ditampilkan)
     * @param  array         $parameterRute  parameter tambahan untuk route tsb
     */
    public static function jalankan(
        ImportDasar $import,
        UploadedFile $berkas,
        string $rutePenerima,
        array $parameterRute = []
    ): RedirectResponse {
        try {
            Excel::import($import, $berkas);
        } catch (ExcelValidationException $e) {
            return redirect()->route($rutePenerima, $parameterRute)
                ->with('error', 'Import dibatalkan karena ada isian yang tidak sah: '
                    .collect($e->failures())->take(5)->map(
                        fn ($f) => 'baris '.$f->row().' kolom "'.$f->attribute().'" — '.implode(', ', $f->errors())
                    )->implode('; '));
        } catch (Throwable $e) {
            report($e);

            return redirect()->route($rutePenerima, $parameterRute)
                ->with('error', 'Import gagal: '.self::pesanRamah($e));
        }

        $hasil = $import->hasil;

        return redirect()->route($rutePenerima, $parameterRute)
            ->with($hasil->gagalTotal() ? 'error' : 'success', $hasil->ringkasan())
            ->with('hasil_import', $hasil->toArray());
    }

    /**
     * Terjemahkan galat teknis menjadi kalimat yang berguna bagi operator.
     * Pesan asli tetap dicatat di log lewat report() untuk penelusuran.
     */
    private static function pesanRamah(Throwable $e): string
    {
        $pesan = $e->getMessage();

        // Dilempar sendiri oleh ImportDasar saat judul kolom tidak sesuai
        // template — pesannya memang sudah ditulis untuk dibaca operator.
        if ($e instanceof \RuntimeException) {
            return $pesan;
        }

        if (str_contains($pesan, 'Duplicate entry')) {
            return 'ada data yang bentrok dengan yang sudah tersimpan (kode/NIS kembar). '
                .'Periksa apakah ada baris ganda di dalam file Anda.';
        }

        if (str_contains($pesan, 'Unable to identify') || str_contains($pesan, 'Reader')) {
            return 'file tidak bisa dibaca. Pastikan yang diunggah benar-benar berkas Excel (.xlsx/.xls) '
                .'atau CSV, bukan file lain yang namanya diubah.';
        }

        if (str_contains($pesan, 'Undefined array key')) {
            return 'judul kolom pada file tidak sesuai template. Unduh ulang templatenya lalu isi '
                .'tanpa mengubah baris judul di baris pertama.';
        }

        return 'terjadi kesalahan saat memproses file. Pastikan file diisi mengikuti template, '
            .'lalu coba lagi. (Rincian teknis sudah dicatat di log sistem.)';
    }
}
