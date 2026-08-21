<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OrangTuaLoginController;
use App\Http\Controllers\BkDashboardController;
use App\Http\Controllers\BkJenisPelanggaranController;
use App\Http\Controllers\BkKasusController;
use App\Http\Controllers\BkPemanggilanController;
use App\Http\Controllers\BkPembinaanController;
use App\Http\Controllers\BkPenguranganPoinController;
use App\Http\Controllers\BkSiswaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuruBkController;
use App\Http\Controllers\GuruMengajarController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\JamPelajaranController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\RiwayatKelasController;
use App\Http\Controllers\LaporanGuruController;
use App\Http\Controllers\MataPelajaranController;
use App\Http\Controllers\MengajarController;
use App\Http\Controllers\NotifikasiWhatsappController;
use App\Http\Controllers\OrangTuaController;
use App\Http\Controllers\OrangTuaDashboardController;
use App\Http\Controllers\PengaturanSekolahController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\TahunAjaranController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaliKelasController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:login');
});

// ===== PORTAL ORANG TUA: login terpisah pakai NIS (guard 'orangtua') =====
Route::prefix('orangtua')->name('orangtua.')->group(function () {
    Route::middleware('guest:orangtua')->group(function () {
        Route::get('login', [OrangTuaLoginController::class, 'create'])->name('login');
        Route::post('login', [OrangTuaLoginController::class, 'store'])->middleware('throttle:login-ortu');
    });

    Route::middleware('auth:orangtua')->group(function () {
        Route::post('logout', [OrangTuaLoginController::class, 'destroy'])->name('logout');
        Route::get('dashboard', [OrangTuaDashboardController::class, 'index'])->name('dashboard');
        Route::get('ganti-password', [OrangTuaDashboardController::class, 'gantiPasswordForm'])->name('ganti-password.form');
        Route::post('ganti-password', [OrangTuaDashboardController::class, 'gantiPassword'])->name('ganti-password');
    });
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ===== MODUL BK: kasus, pembinaan, poin, pemanggilan ortu =====
    // View-level: Guru (lapor + lihat kasus sendiri), Wali Kelas (lihat kelasnya),
    // Guru BK (kelola penuh sesuai kelas mapping), Kurikulum/Kepsek/Kesiswaan (view semua).
    Route::prefix('bk')->name('bk.')->middleware('role:guru,guru_bk,kurikulum,kepala_sekolah,kesiswaan,admin')->group(function () {
        Route::get('dashboard', [BkDashboardController::class, 'index'])->name('dashboard');

        Route::get('siswa', [BkSiswaController::class, 'index'])->name('siswa.index');
        Route::get('siswa/{siswa}', [BkSiswaController::class, 'show'])->name('siswa.show');

        Route::get('kasus', [BkKasusController::class, 'index'])->name('kasus.index');

        Route::get('pembinaan', [BkPembinaanController::class, 'index'])->name('pembinaan.index');
        Route::get('pengurangan', [BkPenguranganPoinController::class, 'index'])->name('pengurangan.index');
        Route::get('pemanggilan', [BkPemanggilanController::class, 'index'])->name('pemanggilan.index');

        // Lapor kasus baru: Guru (semua jenis), Guru BK, Admin — TIDAK Kurikulum/Kepsek.
        // 'periode-aktif' hanya di rute POST (tulis) — GET create tetap bebas dibuka.
        Route::middleware('role:guru,guru_bk,admin')->group(function () {
            Route::get('kasus/create', [BkKasusController::class, 'create'])->name('kasus.create');
            Route::post('kasus', [BkKasusController::class, 'store'])->name('kasus.store')->middleware('periode-aktif');
        });

        // Kelola poin/pembinaan/pemanggilan/master data: KHUSUS Guru BK & Admin
        // (Bagian 20 spec: "jangan beri akses pengurangan poin ke semua guru").
        // 'periode-aktif' hanya dipasang di aksi TULIS transaksi (bukan di
        // sub-grup jenis-pelanggaran/master data, dan bukan di rute GET).
        Route::middleware('role:guru_bk,admin')->group(function () {
            Route::middleware('periode-aktif')->group(function () {
                Route::post('kasus/{kasus}/batalkan', [BkKasusController::class, 'batalkan'])->name('kasus.batalkan');
                Route::patch('kasus/{kasus}/status', [BkKasusController::class, 'updateStatus'])->name('kasus.update-status');

                Route::post('pembinaan', [BkPembinaanController::class, 'store'])->name('pembinaan.store');
                Route::put('pembinaan/{pembinaan}', [BkPembinaanController::class, 'update'])->name('pembinaan.update');
                Route::post('pembinaan/{pembinaan}/evaluasi-harian', [BkPembinaanController::class, 'storeEvaluasiHarian'])->name('pembinaan.evaluasi-harian');

                Route::post('pengurangan', [BkPenguranganPoinController::class, 'store'])->name('pengurangan.store');
                Route::post('pengurangan/{pengurangan}/batalkan', [BkPenguranganPoinController::class, 'batalkan'])->name('pengurangan.batalkan');

                Route::post('pemanggilan', [BkPemanggilanController::class, 'store'])->name('pemanggilan.store');
            });

            Route::prefix('jenis-pelanggaran')->name('jenis-pelanggaran.')->group(function () {
                Route::get('/', [BkJenisPelanggaranController::class, 'index'])->name('index');
                Route::post('/', [BkJenisPelanggaranController::class, 'store'])->name('store');
                Route::put('/{jenisPelanggaran}', [BkJenisPelanggaranController::class, 'update'])->name('update');
            });
        });
    });

    // ===== GURU MAPEL: absensi siswa + jurnal mengajar =====
    Route::prefix('mengajar')->name('mengajar.')->middleware('role:guru,kurikulum,admin')->group(function () {
        Route::get('/', [MengajarController::class, 'index'])->name('index');
        // {ids} = id jadwal_pelajarans dipisah koma, mis. "12,13,14" untuk 1 sesi 3 jam berurutan
        Route::get('/{ids}', [MengajarController::class, 'form'])->where('ids', '[0-9,]+')->name('form');
        Route::post('/{ids}', [MengajarController::class, 'store'])->where('ids', '[0-9,]+')->name('store')->middleware('periode-aktif');
    });

    // ===== WALI KELAS / GURU BK: rekap absensi bulanan + jurnal kelas =====
    // Kesiswaan HANYA dapat akses rekap absensi bulanan (bukan jurnal
    // kelas — itu tetap urusan Guru/Wali Kelas/BK/Kurikulum/Kepsek).
    Route::prefix('wali-kelas')->name('walikelas.')->group(function () {
        Route::middleware('role:guru,guru_bk,kurikulum,kepala_sekolah,kesiswaan,admin')->group(function () {
            Route::get('absensi-bulanan/{kelas?}', [WaliKelasController::class, 'absensiBulanan'])->name('absensi-bulanan');
        });
        Route::middleware('role:guru,guru_bk,kurikulum,kepala_sekolah,admin')->group(function () {
            Route::get('jurnal-kelas/{kelas?}', [WaliKelasController::class, 'jurnalKelas'])->name('jurnal-kelas');
        });
    });

    // ===== LAPORAN: jurnal mengajar & absensi guru per mata pelajaran =====
    Route::prefix('laporan')->name('laporan.')->middleware('role:guru,kurikulum,kepala_sekolah,admin')->group(function () {
        Route::get('jurnal-guru', [LaporanGuruController::class, 'jurnalMapel'])->name('jurnal-guru');
        Route::get('absensi-guru', [LaporanGuruController::class, 'absensiMapel'])->name('absensi-guru');
    });

    // ===== STATUS WHATSAPP ORTU: histori notifikasi Alfa (semua role,
    // cakupan datanya dibatasi per role di controller) =====
    Route::middleware('role:guru,guru_bk,kurikulum,kepala_sekolah,kesiswaan,admin')->group(function () {
        Route::get('notifikasi-wa', [NotifikasiWhatsappController::class, 'index'])->name('notifikasi-wa.index');
    });

    // ===== KURIKULUM: mapping guru mengajar & jadwal pelajaran =====
    Route::middleware('role:kurikulum,admin')->group(function () {
        Route::prefix('kurikulum/guru-mengajar')->name('kurikulum.guru-mengajar.')->group(function () {
            Route::get('/', [GuruMengajarController::class, 'index'])->name('index');
            Route::post('/', [GuruMengajarController::class, 'store'])->name('store')->middleware('periode-aktif');
            Route::put('/{guruMengajar}', [GuruMengajarController::class, 'update'])->name('update');
            Route::delete('/{guruMengajar}', [GuruMengajarController::class, 'destroy'])->name('destroy');
            Route::get('/import', [GuruMengajarController::class, 'importForm'])->name('import.form');
            Route::get('/template', [GuruMengajarController::class, 'template'])->name('template');
            Route::post('/import', [GuruMengajarController::class, 'import'])->name('import')->middleware('periode-aktif');
        });

        Route::prefix('kurikulum/guru-bk')->name('kurikulum.guru-bk.')->group(function () {
            Route::get('/', [GuruBkController::class, 'index'])->name('index');
            Route::post('/', [GuruBkController::class, 'store'])->name('store');
            Route::delete('/{guruBk}', [GuruBkController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('jadwal')->name('jadwal.')->group(function () {
            Route::get('/', [JadwalController::class, 'index'])->name('index');
            Route::post('/', [JadwalController::class, 'store'])->name('store')->middleware('periode-aktif');
            Route::put('/{jadwal}', [JadwalController::class, 'update'])->name('update');
            Route::delete('/{jadwal}', [JadwalController::class, 'destroy'])->name('destroy');
            Route::get('/import', [JadwalController::class, 'importForm'])->name('import.form');
            Route::get('/template', [JadwalController::class, 'template'])->name('template');
            Route::post('/import', [JadwalController::class, 'import'])->name('import')->middleware('periode-aktif');
        });

        Route::resource('siswa', SiswaController::class)->except(['create', 'edit', 'show'])->parameters(['siswa' => 'siswa']);
        Route::get('siswa-import', [SiswaController::class, 'importForm'])->name('siswa.import.form');
        Route::get('siswa-import/template', [SiswaController::class, 'template'])->name('siswa.template');
        Route::post('siswa-import', [SiswaController::class, 'import'])->name('siswa.import');

        Route::get('data-orangtua', [OrangTuaController::class, 'index'])->name('orangtua-akun.index');
        Route::get('data-orangtua/import', [OrangTuaController::class, 'importForm'])->name('orangtua-akun.import.form');
        Route::post('data-orangtua/import', [OrangTuaController::class, 'import'])->name('orangtua-akun.import');
        Route::get('data-orangtua/template', [OrangTuaController::class, 'template'])->name('orangtua-akun.template');
        Route::post('data-orangtua/{orangTua}/reset-password', [OrangTuaController::class, 'resetPassword'])->name('orangtua-akun.reset-password');
        Route::delete('data-orangtua/{orangTua}', [OrangTuaController::class, 'destroy'])->name('orangtua-akun.destroy');

        Route::resource('kelas', KelasController::class)->except(['create', 'edit', 'show'])->parameters(['kelas' => 'kelas']);
        Route::get('kelas-import', [KelasController::class, 'importForm'])->name('kelas.import.form');
        Route::get('kelas-import/template', [KelasController::class, 'template'])->name('kelas.template');
        Route::post('kelas-import', [KelasController::class, 'import'])->name('kelas.import');
        Route::post('kelas-salin', [KelasController::class, 'salinDariTahunAjaran'])->name('kelas.salin');

        Route::resource('mapel', MataPelajaranController::class)->except(['create', 'edit', 'show']);
        Route::get('mapel-import', [MataPelajaranController::class, 'importForm'])->name('mapel.import.form');
        Route::get('mapel-import/template', [MataPelajaranController::class, 'template'])->name('mapel.template');
        Route::post('mapel-import', [MataPelajaranController::class, 'import'])->name('mapel.import');

        Route::resource('tahun-ajaran', TahunAjaranController::class)
            ->except(['create', 'edit', 'show'])
            ->parameters(['tahun-ajaran' => 'tahunAjaran']);
        Route::post('tahun-ajaran-baru', [TahunAjaranController::class, 'buatTahunAjaranBaru'])->name('tahun-ajaran.buat-baru');
        Route::post('tahun-ajaran/{tahunAjaran}/aktifkan', [TahunAjaranController::class, 'aktifkan'])->name('tahun-ajaran.aktifkan');
        Route::post('tahun-ajaran/{tahunAjaran}/kunci', [TahunAjaranController::class, 'kunci'])->name('tahun-ajaran.kunci');
        Route::post('tahun-ajaran/{tahunAjaran}/buka-kunci', [TahunAjaranController::class, 'bukaKunci'])->name('tahun-ajaran.buka-kunci');
        Route::get('tahun-ajaran-duplikasi/preview', [TahunAjaranController::class, 'previewDuplikasiMapping'])->name('tahun-ajaran.duplikasi.preview');
        Route::post('tahun-ajaran/{tahunAjaran}/duplikasi', [TahunAjaranController::class, 'duplikasiMapping'])->name('tahun-ajaran.duplikasi');

        // ===== RIWAYAT KELAS SISWA =====
        // (Revisi permintaan admin) Fitur "Kenaikan Kelas" (proses pindah
        // kelas massal lewat menu tersendiri) DIHAPUS — sekolah ini
        // memindahkan siswa antar kelas/tahun ajaran lewat Import Excel
        // Data Siswa (menu siswa.import), yang sekarang otomatis mencatat
        // riwayat_kelas_siswas juga (lihat app/Imports/SiswaImport.php).
        // Halaman melihat histori TETAP ADA di bawah ini — datanya tidak
        // pernah dihapus.
        Route::get('siswa/{siswa}/riwayat-kelas', [RiwayatKelasController::class, 'show'])->name('siswa.riwayat-kelas');
    });

    // ===== PENGATURAN SEKOLAH: data relatif tetap (lokasi, kepala sekolah, dst)
    // dipakai otomatis di semua halaman Cetak. Admin & Kurikulum yang mengelola. =====
    Route::middleware('role:admin,kurikulum')->group(function () {
        Route::get('pengaturan-sekolah', [PengaturanSekolahController::class, 'edit'])->name('pengaturan-sekolah.edit');
        Route::put('pengaturan-sekolah', [PengaturanSekolahController::class, 'update'])->name('pengaturan-sekolah.update');
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
        Route::post('jam-pelajaran-salin', [JamPelajaranController::class, 'salin'])->name('jam-pelajaran.salin');
        Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);
    });
});
