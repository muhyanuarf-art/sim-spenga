<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuruMengajarController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\JamPelajaranController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\LaporanGuruController;
use App\Http\Controllers\MataPelajaranController;
use App\Http\Controllers\MengajarController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\TahunAjaranController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaliKelasController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

// ===== WEBHOOK WHATSAPP CLOUD API =====
// Di LUAR middleware 'auth' (Meta yang memanggil, bukan user login) dan
// di-exempt dari CSRF (lihat bootstrap/app.php).
Route::get('webhook/whatsapp', [WhatsAppWebhookController::class, 'verifikasi']);
Route::post('webhook/whatsapp', [WhatsAppWebhookController::class, 'terimaStatus']);

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ===== GURU MAPEL: absensi siswa + jurnal mengajar =====
    Route::prefix('mengajar')->name('mengajar.')->middleware('role:guru,kurikulum,admin')->group(function () {
        Route::get('/', [MengajarController::class, 'index'])->name('index');
        // {ids} = id jadwal_pelajarans dipisah koma, mis. "12,13,14" untuk 1 sesi 3 jam berurutan
        Route::get('/{ids}', [MengajarController::class, 'form'])->where('ids', '[0-9,]+')->name('form');
        Route::post('/{ids}', [MengajarController::class, 'store'])->where('ids', '[0-9,]+')->name('store');
    });

    // ===== WALI KELAS: rekap absensi bulanan + jurnal kelas =====
    Route::prefix('wali-kelas')->name('walikelas.')->middleware('role:guru,kurikulum,kepala_sekolah,admin')->group(function () {
        Route::get('absensi-bulanan/{kelas?}', [WaliKelasController::class, 'absensiBulanan'])->name('absensi-bulanan');
        Route::get('jurnal-kelas/{kelas?}', [WaliKelasController::class, 'jurnalKelas'])->name('jurnal-kelas');
        Route::get('status-whatsapp/{kelas?}', [WaliKelasController::class, 'statusWhatsApp'])->name('status-whatsapp');
    });

    // ===== LAPORAN: jurnal mengajar & absensi guru per mata pelajaran =====
    Route::prefix('laporan')->name('laporan.')->middleware('role:guru,kurikulum,kepala_sekolah,admin')->group(function () {
        Route::get('jurnal-guru', [LaporanGuruController::class, 'jurnalMapel'])->name('jurnal-guru');
        Route::get('absensi-guru', [LaporanGuruController::class, 'absensiMapel'])->name('absensi-guru');
    });

    // ===== KURIKULUM: mapping guru mengajar & jadwal pelajaran =====
    Route::middleware('role:kurikulum,admin')->group(function () {
        Route::prefix('kurikulum/guru-mengajar')->name('kurikulum.guru-mengajar.')->group(function () {
            Route::get('/', [GuruMengajarController::class, 'index'])->name('index');
            Route::post('/', [GuruMengajarController::class, 'store'])->name('store');
            Route::put('/{guruMengajar}', [GuruMengajarController::class, 'update'])->name('update');
            Route::delete('/{guruMengajar}', [GuruMengajarController::class, 'destroy'])->name('destroy');
            Route::get('/import', [GuruMengajarController::class, 'importForm'])->name('import.form');
            Route::post('/import', [GuruMengajarController::class, 'import'])->name('import');
        });

        Route::prefix('jadwal')->name('jadwal.')->group(function () {
            Route::get('/', [JadwalController::class, 'index'])->name('index');
            Route::post('/', [JadwalController::class, 'store'])->name('store');
            Route::put('/{jadwal}', [JadwalController::class, 'update'])->name('update');
            Route::delete('/{jadwal}', [JadwalController::class, 'destroy'])->name('destroy');
            Route::get('/import', [JadwalController::class, 'importForm'])->name('import.form');
            Route::post('/import', [JadwalController::class, 'import'])->name('import');
        });

        Route::resource('siswa', SiswaController::class)->except(['create', 'edit', 'show'])->parameters(['siswa' => 'siswa']);
        Route::get('siswa-import', [SiswaController::class, 'importForm'])->name('siswa.import.form');
        Route::post('siswa-import', [SiswaController::class, 'import'])->name('siswa.import');

        Route::resource('kelas', KelasController::class)->except(['create', 'edit', 'show'])->parameters(['kelas' => 'kelas']);
        Route::resource('mapel', MataPelajaranController::class)->except(['create', 'edit', 'show']);
        Route::resource('tahun-ajaran', TahunAjaranController::class)
            ->except(['create', 'edit', 'show'])
            ->parameters(['tahun-ajaran' => 'tahunAjaran']);
        Route::post('tahun-ajaran/{tahunAjaran}/aktifkan', [TahunAjaranController::class, 'aktifkan'])->name('tahun-ajaran.aktifkan');
    });

    // ===== REKAPITULASI: dilihat Admin, Kurikulum, DAN Kepala Sekolah (view-only) =====
    Route::middleware('role:admin,kurikulum,kepala_sekolah')->group(function () {
        Route::get('rekap', [RekapController::class, 'index'])->name('rekap.index');
    });

    // Jam pelajaran: input fleksibel oleh Admin, tapi Kurikulum boleh lihat
    Route::middleware('role:admin')->group(function () {
        Route::resource('jam-pelajaran', JamPelajaranController::class)
            ->except(['create', 'edit', 'show'])
            ->parameters(['jam-pelajaran' => 'jamPelajaran']);
        Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);
    });
});
