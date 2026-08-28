<?php

namespace App\Http\Controllers;

use App\Models\TahunAjaran;
use App\Support\KonteksPeriode;
use Illuminate\Http\Request;

/**
 * Pemilih periode di kepala halaman — mengganti Tahun Ajaran + Semester
 * yang sedang DILIHAT pengguna ini (tidak mengubah periode aktif sekolah;
 * itu wewenang admin lewat menu Tahun Ajaran).
 *
 * Lihat App\Support\KonteksPeriode untuk penjelasan lengkap perbedaan
 * "periode aktif" dan "periode pilihan".
 */
class KonteksPeriodeController extends Controller
{
    public function ganti(Request $request)
    {
        $validated = $request->validate([
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajarans,id'],
        ], [], ['tahun_ajaran_id' => 'Periode']);

        $periode = TahunAjaran::findOrFail($validated['tahun_ajaran_id']);

        // Hanya periode yang memang ditawarkan ke peran ini yang boleh
        // dipilih — supaya tidak bisa diakali lewat request manual.
        abort_unless(
            KonteksPeriode::daftarPilihan($request->user()->role)->contains('id', $periode->id),
            403,
            'Periode tersebut tidak tersedia untuk Anda.'
        );

        KonteksPeriode::pilih($periode);

        $pesan = KonteksPeriode::melihatPeriodeAktif()
            ? 'Kembali ke '.$periode->labelPeriode().' (periode berjalan).'
            : 'Sekarang melihat '.$periode->labelPeriode().' — mode lihat saja, data tidak dapat diubah.';

        return back()->with('success', $pesan);
    }
}
