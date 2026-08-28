<?php

use App\Http\Controllers\AnalisisSumatifController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OrangTuaLoginController;
use App\Http\Controllers\BkDashboardController;
use App\Http\Controllers\BkJenisPelanggaranController;
use App\Http\Controllers\BkKasusController;
use App\Http\Controllers\BkLaporanBulananController;
use App\Http\Controllers\BkPemanggilanController;
use App\Http\Controllers\BkPembinaanController;
use App\Http\Controllers\BkPenguranganPoinController;
use App\Http\Controllers\BkSiswaController;
use App\Http\Controllers\AbsensiKegiatanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EkskulAbsensiController;
use App\Http\Controllers\EkskulRekapController;
use App\Http\Controllers\EkstrakurikulerAnggotaController;
use App\Http\Controllers\EkstrakurikulerController;
use App\Http\Controllers\GuruBkController;
use App\Http\Controllers\GuruMengajarController;
use App\Http\Controllers\IkonAplikasiController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\JamPelajaranController;
use App\Http\Controllers\JenisSuratController;
use App\Http\Controllers\KegiatanSekolahController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\RiwayatKelasController;
use App\Http\Controllers\LaporanGuruController;
use App\Http\Controllers\LaporanSemesterController;
use App\Http\Controllers\MataPelajaranController;
use App\Http\Controllers\MengajarController;
use App\Http\Controllers\NilaiController;
use App\Http\Controllers\NilaiMonitoringController;
use App\Http\Controllers\NilaiWaliKelasController;
use App\Http\Controllers\NotifikasiWhatsappController;
use App\Http\Controllers\PengaturanPenilaianController;
use App\Http\Controllers\ProgramPerbaikanController;
use App\Http\Controllers\OrangTuaController;
use App\Http\Controllers\OrangTuaDashboardController;
use App\Http\Controllers\PengaturanSekolahController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\SuratController;
use App\Http\Controllers\SuratDashboardController;
use App\Http\Controllers\TahunAjaranController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaliKelasController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

// Ikon aplikasi — sengaja TERBUKA tanpa login, karena peramban meminta
// keduanya sebelum pengguna sempat masuk (mis. saat membuka halaman login).
// Isinya mengikuti Logo Aplikasi & Nama Sekolah di menu Pengaturan Sekolah.
Route::get('favicon.ico', [IkonAplikasiController::class, 'favicon'])->name('favicon');
Route::get('site.webmanifest', [IkonAplikasiController::class, 'manifest'])->name('site.webmanifest');

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

        // Laporan Bulanan BK — rekap sebulan yang bisa dicetak untuk Kepala
        // Sekolah. Baca saja, jadi ikut hak akses yang sama dengan daftar
        // BK lainnya di grup ini.
        Route::get('laporan-bulanan', [BkLaporanBulananController::class, 'index'])->name('laporan-bulanan');

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
            // Seluruh pencatatan BK berpangkal dari Buku Catatan BK. Dulu
            // Pembinaan & Pengurangan Poin TIDAK punya halaman pencatatan
            // sama sekali — satu-satunya jalan lewat modal di halaman Profil
            // Perilaku Siswa, sehingga pencatatan tersebar di dua tempat.
            Route::get('pembinaan/create', [BkPembinaanController::class, 'create'])->name('pembinaan.create');
            Route::get('pengurangan/create', [BkPenguranganPoinController::class, 'create'])->name('pengurangan.create');
            Route::get('pemanggilan/create', [BkPemanggilanController::class, 'create'])->name('pemanggilan.create');
            Route::get('pemanggilan/{pemanggilan}/hasil', [BkPemanggilanController::class, 'editHasil'])->name('pemanggilan.hasil.edit');

            Route::middleware('periode-aktif')->group(function () {
                Route::post('kasus/{kasus}/batalkan', [BkKasusController::class, 'batalkan'])->name('kasus.batalkan');
                Route::patch('kasus/{kasus}/status', [BkKasusController::class, 'updateStatus'])->name('kasus.update-status');

                Route::post('pembinaan', [BkPembinaanController::class, 'store'])->name('pembinaan.store');
                Route::put('pembinaan/{pembinaan}', [BkPembinaanController::class, 'update'])->name('pembinaan.update');
                Route::post('pembinaan/{pembinaan}/evaluasi-harian', [BkPembinaanController::class, 'storeEvaluasiHarian'])->name('pembinaan.evaluasi-harian');

                Route::post('pengurangan', [BkPenguranganPoinController::class, 'store'])->name('pengurangan.store');
                Route::post('pengurangan/{pengurangan}/batalkan', [BkPenguranganPoinController::class, 'batalkan'])->name('pengurangan.batalkan');

                Route::post('pemanggilan', [BkPemanggilanController::class, 'store'])->name('pemanggilan.store');
                Route::put('pemanggilan/{pemanggilan}/hasil', [BkPemanggilanController::class, 'updateHasil'])->name('pemanggilan.hasil.update');
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

    // ===== PENILAIAN: daftar nilai guru mapel → nilai rapor wali kelas =====
    // Pembagian tugasnya tegas:
    // - MENGISI daftar nilai: Guru Mapel (hanya kelas & mapel yang diampu —
    //   dicek lagi di NilaiController lewat guru_mengajar_kelas) + Admin
    //   sebagai perwakilan bila guru berhalangan.
    // - MEMBACA & MENCETAK lembar mana pun: + Kurikulum & Kepala Sekolah.
    // - LAPORAN WALI KELAS: Wali Kelas (kelasnya sendiri), Guru BK (kelas
    //   binaannya), Kurikulum/Kepsek/Admin (semua kelas).
    // - BUKA KUNCI lembar yang sudah final: HANYA Kurikulum & Admin.
    Route::prefix('nilai')->name('nilai.')->group(function () {
        // Daftar lembar + pengisian oleh guru mata pelajaran.
        Route::middleware('role:guru,kurikulum,kepala_sekolah,admin')->group(function () {
            Route::get('/', [NilaiController::class, 'pilih'])->name('pilih');
            Route::get('lembar/{kelas}/{mapel}', [NilaiController::class, 'form'])->name('form');

            // Analisis Hasil Tes Sumatif Lingkup Materi — turunan dari nilai
            // SUM yang sudah diinput di lembar Daftar Nilai di atas. Satu
            // lembar per Lingkup Materi yang sudah ada nilainya (?lm=n).
            Route::get('analisis/{kelas}/{mapel}', [AnalisisSumatifController::class, 'index'])->name('analisis');

            // Program Pengayaan & Perbaikan — kelanjutan lembar analisis di
            // atas: siapa yang remedial (beserta butir soal yang belum
            // dikuasainya) dan siapa yang berhak pengayaan.
            Route::get('program/{kelas}/{mapel}', [ProgramPerbaikanController::class, 'index'])->name('program');
        });
        Route::middleware(['role:guru,admin', 'periode-aktif'])->group(function () {
            // Hanya keterangan lembar (Materi Ajar, banyak soal, tanggal) —
            // skor tiap butir soal tidak pernah diketik, selalu diturunkan.
            Route::put('analisis/{kelas}/{mapel}', [AnalisisSumatifController::class, 'update'])->name('analisis.update');
            // Hanya rencana pelaksanaan (bentuk & tanggal) — daftar pesertanya
            // selalu diturunkan dari nilai, tidak pernah diketik.
            Route::put('program/{kelas}/{mapel}', [ProgramPerbaikanController::class, 'update'])->name('program.update');
        });
        Route::middleware(['role:guru,admin', 'periode-aktif'])->group(function () {
            Route::post('lembar/{kelas}/{mapel}', [NilaiController::class, 'store'])->name('store');
            Route::post('lembar/{kelas}/{mapel}/finalisasi', [NilaiController::class, 'finalisasi'])->name('finalisasi');
        });
        Route::middleware(['role:kurikulum,admin', 'periode-aktif'])->group(function () {
            Route::post('lembar/{kelas}/{mapel}/buka-kunci', [NilaiController::class, 'bukaKunci'])->name('buka-kunci');
        });

        // Laporan untuk wali kelas (baca saja — nilainya otomatis dari guru mapel).
        Route::middleware('role:guru,guru_bk,kurikulum,kepala_sekolah,admin')->group(function () {
            Route::get('rekap-kelas/{kelas?}', [NilaiWaliKelasController::class, 'rekapKelas'])->name('rekap-kelas');
            Route::get('per-mapel/{kelas?}', [NilaiWaliKelasController::class, 'laporanMapel'])->name('per-mapel');

            // Rekap SATU SEMESTER PENUH lintas modul (nilai, kehadiran,
            // kedisiplinan, ekstrakurikuler) — bahan rapat penerimaan rapor.
            Route::get('laporan-semester/{kelas?}', [LaporanSemesterController::class, 'index'])->name('laporan-semester');
        });

        // Monitoring pengisian nilai seluruh sekolah.
        Route::middleware('role:kurikulum,kepala_sekolah,admin')->group(function () {
            Route::get('monitoring', [NilaiMonitoringController::class, 'index'])->name('monitoring');
        });
    });

    // Skema penilaian (bobot 60/20/20, KKTP per tingkat, jumlah kolom TPF/LM).
    Route::middleware('role:kurikulum,admin')->group(function () {
        Route::get('pengaturan-penilaian', [PengaturanPenilaianController::class, 'edit'])->name('penilaian.pengaturan.edit');
        Route::put('pengaturan-penilaian', [PengaturanPenilaianController::class, 'update'])->name('penilaian.pengaturan.update');
    });

    // ===== KESISWAAN: master data Kegiatan Ekstrakurikuler =====
    // Master kegiatan + pembina: HANYA Kesiswaan & Admin yang mengelola.
    Route::middleware('role:kesiswaan,admin')->group(function () {
        Route::resource('ekstrakurikuler', EkstrakurikulerController::class)->except(['create', 'edit', 'show']);

        // Anggota (siswa) per kegiatan — juga khusus Kesiswaan & Admin.
        Route::prefix('ekstrakurikuler/{ekstrakurikuler}/anggota')->name('ekstrakurikuler.anggota.')->group(function () {
            Route::get('/', [EkstrakurikulerAnggotaController::class, 'index'])->name('index');
            Route::post('/', [EkstrakurikulerAnggotaController::class, 'store'])->name('store');
            Route::post('/sync-kelas', [EkstrakurikulerAnggotaController::class, 'syncKelas'])->name('sync-kelas');
            Route::delete('/{anggota}', [EkstrakurikulerAnggotaController::class, 'destroy'])->name('destroy');
        });
    });

    // ===== KEGIATAN SEKOLAH DI LUAR JAM KBM =====
    // (lomba Agustus, tryout & asesmen sumatif, classmeeting, pesantren
    // Ramadan, dsb). Pembagian tugasnya sengaja tegas:
    //
    // - MENJADWALKAN kegiatan: Kesiswaan, Kurikulum, Admin.
    // - MEMANTAU pengisian (baca saja): + Kepala Sekolah & Guru BK.
    // - MENGISI ABSENSI: HANYA WALI KELAS dari kelas sasaran (Admin
    //   diizinkan sebagai perwakilan bila wali kelas berhalangan).
    //   Middleware role di bawah baru saringan pertama; kepemilikan kelas
    //   dicek lagi di AbsensiKegiatanController::pastikanBoleh().
    Route::middleware('role:kesiswaan,kurikulum,kepala_sekolah,guru_bk,admin')->group(function () {
        Route::get('kegiatan', [KegiatanSekolahController::class, 'index'])->name('kegiatan.index');
        Route::get('kegiatan/{kegiatan}', [KegiatanSekolahController::class, 'show'])->name('kegiatan.show');
    });
    Route::middleware(['role:kesiswaan,kurikulum,admin', 'periode-aktif'])->group(function () {
        Route::post('kegiatan', [KegiatanSekolahController::class, 'store'])->name('kegiatan.store');
        Route::put('kegiatan/{kegiatan}', [KegiatanSekolahController::class, 'update'])->name('kegiatan.update');
        Route::delete('kegiatan/{kegiatan}', [KegiatanSekolahController::class, 'destroy'])->name('kegiatan.destroy');
    });
    Route::middleware('role:guru,admin')->group(function () {
        Route::get('kegiatan-absensi', [AbsensiKegiatanController::class, 'pilih'])->name('kegiatan.absensi.pilih');
        Route::get('kegiatan/{kegiatan}/absensi/{kelas}', [AbsensiKegiatanController::class, 'form'])->name('kegiatan.absensi.form');
        Route::post('kegiatan/{kegiatan}/absensi/{kelas}', [AbsensiKegiatanController::class, 'store'])
            ->name('kegiatan.absensi.store')->middleware('periode-aktif');
    });

    // ===== ABSENSI EKSTRAKURIKULER =====
    // Yang boleh MENGISI: guru/guru_bk (HANYA kegiatan yang mereka bina —
    // dicek lagi di controller, bukan cuma lewat middleware role ini) serta
    // Kesiswaan/Admin (kegiatan apa pun, mewakili). Lihat
    // EkskulAbsensiController::otorisasiPengisi().
    Route::middleware('role:guru,guru_bk,kesiswaan,admin')->group(function () {
        Route::get('ekstrakurikuler-absensi', [EkskulAbsensiController::class, 'pilihKegiatan'])->name('ekstrakurikuler.absensi.pilih');
        Route::get('ekstrakurikuler/{ekstrakurikuler}/absensi', [EkskulAbsensiController::class, 'form'])->name('ekstrakurikuler.absensi.form');
        Route::post('ekstrakurikuler/{ekstrakurikuler}/absensi', [EkskulAbsensiController::class, 'store'])->name('ekstrakurikuler.absensi.store');

        // Rekap bulanan: sama, guru/guru_bk hanya kegiatan yang dibina
        // (dicek lagi di controller), Kesiswaan/Admin bebas.
        Route::get('ekstrakurikuler/{ekstrakurikuler}/rekap', [EkskulRekapController::class, 'bulanan'])->name('ekstrakurikuler.rekap');
    });

    // ===== SURAT: KHUSUS keperluan BK (2026-08-26, rombak total) — hanya
    // Guru BK yang buat/edit/hapus surat. Kesiswaan/Kurikulum/Kepala
    // Sekolah cuma baca (tahu surat sudah ada). Master Jenis Surat tetap
    // dikelola TU. Disposisi/Lampiran/Activity Log DIHILANGKAN dari alur
    // ini (tidak ada di spesifikasi baru — tabelnya tetap ada di database,
    // cuma tidak dipakai lagi, supaya data lama kalau ada tidak hilang). =====
    Route::middleware('role:tu,admin')->group(function () {
        Route::resource('jenis-surat', JenisSuratController::class)->except(['create', 'edit', 'show']);
    });
    Route::middleware('role:guru_bk,admin')->group(function () {
        Route::get('surat-dashboard', [SuratDashboardController::class, 'index'])->name('surat.dashboard');
        Route::get('surat/create', [SuratController::class, 'create'])->name('surat.create');
        Route::post('surat', [SuratController::class, 'store'])->name('surat.store');
        Route::get('surat/{surat}/edit', [SuratController::class, 'edit'])->name('surat.edit');
        Route::put('surat/{surat}', [SuratController::class, 'update'])->name('surat.update');
        Route::delete('surat/{surat}', [SuratController::class, 'destroy'])->name('surat.destroy');
    });
    // Baca saja — Kesiswaan/Kurikulum/Kepala Sekolah/Guru BK/Admin.
    Route::middleware('role:guru_bk,kurikulum,kepala_sekolah,kesiswaan,admin')->group(function () {
        Route::get('surat', [SuratController::class, 'index'])->name('surat.index');
        Route::get('surat/{surat}', [SuratController::class, 'show'])->name('surat.show');
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
        // (2026-08-23) — aksi khusus untuk siswa pindah kelas DI TENGAH tahun
        // ajaran berjalan (beda dengan siswa.update yang untuk koreksi data
        // biasa). Lihat SiswaController::pindahKelas().
        Route::post('siswa/{siswa}/pindah-kelas', [SiswaController::class, 'pindahKelas'])->name('siswa.pindah-kelas');
        Route::get('siswa-import', [SiswaController::class, 'importForm'])->name('siswa.import.form');
        Route::get('siswa-import/template', [SiswaController::class, 'template'])->name('siswa.template');
        Route::post('siswa-import', [SiswaController::class, 'import'])->name('siswa.import');

        // ===== AKUN PORTAL ORANG TUA =====
        // (2026-08-28) Menu tersendiri dihapus — pengelolaannya kini menyatu
        // di menu Data Siswa (lihat catatan lengkap di OrangTuaController).
        // Rute lama 'orangtua-akun.index/import/import.form/template' DIBUANG:
        // ketiganya menunjuk method yang sudah tidak ada di controller,
        // sehingga selama ini menghasilkan error 500.
        Route::post('akun-ortu/buat-semua', [OrangTuaController::class, 'generate'])->name('akun-ortu.buat-semua');
        Route::post('akun-ortu/siswa/{siswa}', [OrangTuaController::class, 'buatSatu'])->name('akun-ortu.buat-satu');
        Route::post('akun-ortu/{orangTua}/reset-password', [OrangTuaController::class, 'resetPassword'])->name('akun-ortu.reset-password');
        Route::delete('akun-ortu/{orangTua}', [OrangTuaController::class, 'destroy'])->name('akun-ortu.destroy');

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
