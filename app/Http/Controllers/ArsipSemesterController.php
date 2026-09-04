<?php

namespace App\Http\Controllers;

use App\Jobs\BuatArsipSemesterJob;
use App\Models\ArsipSemester;
use App\Models\TahunAjaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ARSIP SEMESTER — dibuat dan diunduh MANUAL oleh Admin.
 *
 * =====================================================================
 * KENAPA MANUAL, BUKAN OTOMATIS SAAT SEMESTER DITUTUP
 * =====================================================================
 * Menutup semester tidak selalu berarti "sudah final". Admin bisa saja
 * menutupnya, lalu menyadari ada nilai yang keliru, membukanya kembali,
 * dan menutupnya lagi. Bila arsip terbentuk otomatis di setiap penutupan,
 * yang tersimpan adalah tumpukan berkas setengah jadi — dan yang paling
 * berbahaya, Admin akan mengira arsip pertama itu yang final.
 *
 * Dengan tombol manual, arsip dibuat pada saat Admin memang menyatakan
 * datanya sudah beres. Itu keputusan yang hanya bisa diambil manusia.
 *
 * =====================================================================
 * MENJAGA ARSIP TETAP JUJUR
 * =====================================================================
 * Selama semesternya terkunci, tidak ada data yang bisa berubah, jadi
 * arsip dijamin tetap sesuai. Satu-satunya cara data bisa berubah adalah
 * Buka Kunci — dan di situlah arsipnya ditandai 'kedaluwarsa'
 * (lihat TahunAjaran::bukaKembali). Arsip lama tidak dihapus: bisa jadi
 * berkas itulah yang sudah diserahkan ke asesor.
 */
class ArsipSemesterController extends Controller
{
    public function buat(Request $request, TahunAjaran $tahunAjaran)
    {
        abort_unless($request->user()->isAdmin(), 403, 'Hanya Admin yang dapat membuat arsip semester.');

        $adaYangBerjalan = ArsipSemester::where('tahun_ajaran_id', $tahunAjaran->id)
            ->where('status', 'antre')
            ->exists();

        if ($adaYangBerjalan) {
            return back()->with('error', 'Arsip untuk periode ini sedang dibuat. Tunggu sampai selesai.');
        }

        $arsip = ArsipSemester::create([
            'tahun_ajaran_id' => $tahunAjaran->id,
            'status' => 'antre',
            'dibuat_oleh' => $request->user()->id,
        ]);

        BuatArsipSemesterJob::dispatch($arsip->id);

        return back()->with('success',
            'Arsip '.$tahunAjaran->labelPeriode().' sedang dibuat di latar belakang. '
            .'Muat ulang halaman ini beberapa menit lagi untuk mengunduhnya.');
    }

    public function unduh(Request $request, ArsipSemester $arsip): StreamedResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Hanya Admin yang dapat mengunduh arsip semester.');
        abort_unless($arsip->bisaDiunduh(), 404, 'Berkas arsip tidak ditemukan.');

        $nama = 'Arsip-'.str_replace(' ', '-', $arsip->tahunAjaran->nama)
            .'-'.$arsip->tahunAjaran->semester.'.zip';

        return Storage::disk('local')->download($arsip->path, $nama, [
            // Arsip berisi seluruh laporan sekolah — jangan sampai tersimpan
            // di cache peramban komputer bersama di ruang guru.
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function hapus(Request $request, ArsipSemester $arsip)
    {
        abort_unless($request->user()->isAdmin(), 403, 'Hanya Admin yang dapat menghapus arsip.');

        if ($arsip->path) {
            Storage::disk('local')->delete($arsip->path);
        }

        $periode = $arsip->tahunAjaran?->labelPeriode() ?? 'periode ini';
        $arsip->delete();

        return back()->with('success', 'Arsip '.$periode.' dihapus.');
    }
}
