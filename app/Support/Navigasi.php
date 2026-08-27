<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * SATU SUMBER KEBENARAN UNTUK SELURUH MENU APLIKASI.
 *
 * Sebelumnya seluruh struktur menu ditulis manual di dalam
 * layouts/app.blade.php sebagai tumpukan @if role — akibatnya:
 *   - nama menu tidak konsisten ("Pantau Pelanggaran", "Kasus/Pelanggaran",
 *     "Data Pelanggaran (Master)", "Surat — Dashboard", dst);
 *   - satu fitur ditulis berkali-kali untuk role berbeda (menu Surat
 *     ditulis 3x, menu Pelanggaran 2x) sehingga gampang tidak sinkron;
 *   - tidak ada breadcrumb/deskripsi halaman karena tidak ada tempat
 *     menyimpan metadata halaman.
 *
 * Sekarang semuanya dideklarasikan SEKALI di sini, lalu dipakai ulang
 * untuk: sidebar, breadcrumb, judul + deskripsi halaman, dan pengecekan
 * "apakah user boleh melihat tautan ini?" di dashboard (supaya Kepala
 * Sekolah tidak lagi disodori tautan yang ujungnya 403).
 *
 * Struktur data:
 *   seksi  => ['label' => 'Monitoring', 'item' => [ ...item... ]]
 *   item   => [
 *       'label'     => teks menu (yang dilihat pengguna)
 *       'icon'      => nama ikon Font Awesome
 *       'route'     => nama route tujuan
 *       'params'    => parameter route (opsional)
 *       'query'     => query string tambahan (opsional, mis. status=draft)
 *       'cocok'     => pola route yang membuat menu ini dianggap aktif
 *       'deskripsi' => kalimat penjelas, tampil di bawah judul halaman
 *       'roles'     => daftar role yang boleh melihat menu ini
 *       'syarat'    => closure tambahan (opsional), mis. khusus wali kelas
 *       'anak'      => sub menu (opsional, maksimal 1 tingkat)
 *   ]
 */
class Navigasi
{
    /** Semua role yang ada di aplikasi. */
    public const SEMUA_ROLE = ['admin', 'kepala_sekolah', 'kurikulum', 'guru', 'guru_bk', 'kesiswaan', 'tu'];

    /**
     * Definisi menu mentah (belum difilter role).
     *
     * @return array<int, array{label: string, item: array}>
     */
    public static function definisi(): array
    {
        // Dipanggil beberapa kali per request (sidebar, breadcrumb,
        // pengecekan hak akses di dashboard) — cukup disusun sekali.
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        // Menu yang khusus untuk Wali Kelas: role 'guru' hanya melihatnya
        // kalau memang sedang menjadi wali kelas di tahun ajaran aktif.
        $khususWaliKelas = fn (User $u) => $u->role !== 'guru' || $u->isWaliKelas();

        return $cache = [
            [
                'label' => 'Utama',
                'item' => [
                    [
                        'label' => 'Dashboard',
                        'icon' => 'fa-gauge-high',
                        'route' => 'dashboard',
                        'cocok' => ['dashboard'],
                        'deskripsi' => 'Ringkasan kondisi sekolah hari ini.',
                        'roles' => self::SEMUA_ROLE,
                    ],
                ],
            ],

            [
                'label' => 'Kegiatan Mengajar',
                'item' => [
                    [
                        'label' => 'Absensi & Jurnal Mengajar',
                        'icon' => 'fa-clipboard-check',
                        'route' => 'mengajar.index',
                        'cocok' => ['mengajar.*'],
                        'deskripsi' => 'Isi kehadiran siswa dan jurnal mengajar sesuai jadwal Anda hari ini.',
                        'roles' => ['guru', 'kurikulum', 'admin'],
                    ],
                    [
                        'label' => 'Absensi Kegiatan Sekolah',
                        'icon' => 'fa-flag-checkered',
                        'route' => 'kegiatan.absensi.pilih',
                        'cocok' => ['kegiatan.absensi.*'],
                        'deskripsi' => 'Isi kehadiran siswa pada kegiatan di luar jam KBM (lomba, asesmen, classmeeting, pesantren Ramadan).',
                        'roles' => ['guru', 'admin'],
                        // Hanya wali kelas yang berhak mengisi absensi kegiatan,
                        // jadi guru mapel biasa tidak perlu melihat menu ini.
                        'syarat' => $khususWaliKelas,
                    ],
                    [
                        'label' => 'Absensi Ekstrakurikuler',
                        'icon' => 'fa-person-running',
                        'route' => 'ekstrakurikuler.absensi.pilih',
                        'cocok' => ['ekstrakurikuler.absensi.*', 'ekstrakurikuler.rekap'],
                        'deskripsi' => 'Isi kehadiran peserta kegiatan ekstrakurikuler yang Anda bina.',
                        'roles' => ['guru', 'guru_bk', 'kesiswaan', 'admin'],
                    ],
                ],
            ],

            [
                'label' => 'Penilaian',
                'item' => [
                    [
                        'label' => 'Daftar Nilai',
                        'icon' => 'fa-table-list',
                        'route' => 'nilai.pilih',
                        'cocok' => ['nilai.pilih', 'nilai.form', 'nilai.store', 'nilai.finalisasi', 'nilai.buka-kunci', 'nilai.analisis', 'nilai.analisis.update'],
                        'deskripsi' => 'Isi nilai formatif, sumatif lingkup materi, ASTS, dan sumatif akhir untuk kelas yang Anda ampu.',
                        'roles' => ['guru', 'kurikulum', 'kepala_sekolah', 'admin'],
                    ],
                    [
                        'label' => 'Nilai Rapor Kelas',
                        'icon' => 'fa-award',
                        'route' => 'nilai.rekap-kelas',
                        'cocok' => ['nilai.rekap-kelas'],
                        'deskripsi' => 'Nilai akhir seluruh mata pelajaran satu kelas beserta rata-rata dan peringkatnya.',
                        'roles' => ['guru', 'guru_bk', 'kurikulum', 'kepala_sekolah', 'admin'],
                        'syarat' => $khususWaliKelas,
                    ],
                    [
                        'label' => 'Nilai per Mata Pelajaran',
                        'icon' => 'fa-list-ol',
                        'route' => 'nilai.per-mapel',
                        'cocok' => ['nilai.per-mapel'],
                        'deskripsi' => 'Rincian nilai formatif dan nilai akhir satu mata pelajaran untuk satu kelas.',
                        'roles' => ['guru', 'guru_bk', 'kurikulum', 'kepala_sekolah', 'admin'],
                        'syarat' => $khususWaliKelas,
                    ],
                    [
                        'label' => 'Monitoring Input Nilai',
                        'icon' => 'fa-clipboard-list',
                        'route' => 'nilai.monitoring',
                        'cocok' => ['nilai.monitoring'],
                        'deskripsi' => 'Pantau mata pelajaran dan kelas mana yang nilainya belum masuk atau belum difinalisasi.',
                        'roles' => ['kurikulum', 'kepala_sekolah', 'admin'],
                    ],
                ],
            ],

            [
                'label' => 'Monitoring',
                'item' => [
                    [
                        'label' => 'Rekap Absensi Kelas',
                        'icon' => 'fa-calendar-check',
                        'route' => 'walikelas.absensi-bulanan',
                        'cocok' => ['walikelas.absensi-bulanan'],
                        'deskripsi' => 'Rekap kehadiran siswa satu kelas selama satu bulan penuh.',
                        'roles' => ['guru', 'guru_bk', 'kurikulum', 'kepala_sekolah', 'kesiswaan', 'admin'],
                        'syarat' => $khususWaliKelas,
                    ],
                    [
                        'label' => 'Jurnal Mengajar Kelas',
                        'icon' => 'fa-book-open',
                        'route' => 'walikelas.jurnal-kelas',
                        'cocok' => ['walikelas.jurnal-kelas'],
                        'deskripsi' => 'Melihat jurnal mengajar seluruh guru yang masuk ke satu kelas.',
                        'roles' => ['guru', 'guru_bk', 'kurikulum', 'kepala_sekolah', 'admin'],
                        'syarat' => $khususWaliKelas,
                    ],
                    [
                        'label' => 'Jurnal Mengajar Guru',
                        'icon' => 'fa-pen-to-square',
                        'route' => 'laporan.jurnal-guru',
                        'cocok' => ['laporan.jurnal-guru'],
                        'deskripsi' => 'Pantau materi yang diajarkan tiap guru per mata pelajaran.',
                        'roles' => ['guru', 'kurikulum', 'kepala_sekolah', 'admin'],
                    ],
                    [
                        'label' => 'Kehadiran Mengajar Guru',
                        'icon' => 'fa-user-clock',
                        'route' => 'laporan.absensi-guru',
                        'cocok' => ['laporan.absensi-guru'],
                        'deskripsi' => 'Pantau guru yang sudah dan belum mengisi kehadiran mengajar.',
                        'roles' => ['guru', 'kurikulum', 'kepala_sekolah', 'admin'],
                    ],
                    [
                        'label' => 'Rekapitulasi Kepatuhan',
                        'icon' => 'fa-chart-line',
                        'route' => 'rekap.index',
                        'cocok' => ['rekap.index'],
                        'deskripsi' => 'Persentase kepatuhan pengisian jurnal & absensi per guru dan per kelas.',
                        'roles' => ['kurikulum', 'kepala_sekolah', 'admin'],
                    ],
                    [
                        'label' => 'Notifikasi WhatsApp Ortu',
                        'icon' => 'fa-comment-sms',
                        'route' => 'notifikasi-wa.index',
                        'cocok' => ['notifikasi-wa.*'],
                        'deskripsi' => 'Status pengiriman pesan WhatsApp pemberitahuan siswa Alfa ke orang tua.',
                        'roles' => ['guru', 'guru_bk', 'kurikulum', 'kepala_sekolah', 'kesiswaan', 'admin'],
                    ],
                ],
            ],

            [
                'label' => 'Kesiswaan',
                'item' => [
                    [
                        'label' => 'Bimbingan Konseling',
                        'icon' => 'fa-hand-holding-heart',
                        'roles' => ['guru', 'guru_bk', 'kurikulum', 'kepala_sekolah', 'kesiswaan', 'admin'],
                        'anak' => [
                            [
                                'label' => 'Ringkasan Pelanggaran',
                                'route' => 'bk.dashboard',
                                'cocok' => ['bk.dashboard'],
                                'deskripsi' => 'Ringkasan kasus, tahap pembinaan, dan siswa yang perlu perhatian.',
                                'roles' => ['guru_bk', 'kurikulum', 'kepala_sekolah', 'kesiswaan', 'admin'],
                            ],
                            [
                                'label' => 'Kasus & Pelanggaran',
                                'route' => 'bk.kasus.index',
                                'cocok' => ['bk.kasus.*'],
                                'deskripsi' => 'Catatan pelanggaran siswa beserta poin dan status penanganannya.',
                                'roles' => ['guru', 'guru_bk', 'kurikulum', 'kepala_sekolah', 'kesiswaan', 'admin'],
                            ],
                            [
                                'label' => 'Pembinaan Siswa',
                                'route' => 'bk.pembinaan.index',
                                'cocok' => ['bk.pembinaan.*'],
                                'deskripsi' => 'Proses pembinaan bertahap untuk siswa yang melanggar.',
                                'roles' => ['guru_bk', 'kurikulum', 'kepala_sekolah', 'kesiswaan', 'admin'],
                            ],
                            [
                                'label' => 'Pengurangan Poin',
                                'route' => 'bk.pengurangan.index',
                                'cocok' => ['bk.pengurangan.*'],
                                'deskripsi' => 'Pengurangan poin pelanggaran bagi siswa yang menunjukkan perbaikan.',
                                'roles' => ['guru_bk', 'kurikulum', 'kepala_sekolah', 'kesiswaan', 'admin'],
                            ],
                            [
                                'label' => 'Pemanggilan Orang Tua',
                                'route' => 'bk.pemanggilan.index',
                                'cocok' => ['bk.pemanggilan.*'],
                                'deskripsi' => 'Agenda dan hasil pertemuan dengan orang tua siswa.',
                                'roles' => ['guru_bk', 'kurikulum', 'kepala_sekolah', 'kesiswaan', 'admin'],
                            ],
                            [
                                'label' => 'Profil Poin Siswa',
                                'route' => 'bk.siswa.index',
                                'cocok' => ['bk.siswa.*'],
                                'deskripsi' => 'Rekam jejak perilaku dan poin pelanggaran tiap siswa.',
                                'roles' => ['guru_bk', 'kurikulum', 'kepala_sekolah', 'kesiswaan', 'admin'],
                            ],
                            [
                                'label' => 'Master Jenis Pelanggaran',
                                'route' => 'bk.jenis-pelanggaran.index',
                                'cocok' => ['bk.jenis-pelanggaran.*'],
                                'deskripsi' => 'Daftar jenis pelanggaran beserta kategori dan bobot poinnya.',
                                'roles' => ['guru_bk', 'admin'],
                            ],
                        ],
                    ],
                    [
                        'label' => 'Kegiatan Sekolah',
                        'icon' => 'fa-calendar-day',
                        'route' => 'kegiatan.index',
                        'cocok' => ['kegiatan.index', 'kegiatan.show'],
                        'deskripsi' => 'Jadwal kegiatan di luar jam KBM beserta pantauan pengisian absensinya oleh wali kelas.',
                        'roles' => ['kesiswaan', 'kurikulum', 'kepala_sekolah', 'guru_bk', 'admin'],
                    ],
                    [
                        'label' => 'Ekstrakurikuler',
                        'icon' => 'fa-people-group',
                        'route' => 'ekstrakurikuler.index',
                        'cocok' => ['ekstrakurikuler.index', 'ekstrakurikuler.store', 'ekstrakurikuler.update', 'ekstrakurikuler.destroy', 'ekstrakurikuler.anggota.*'],
                        'deskripsi' => 'Kelola kegiatan ekstrakurikuler, pembina, dan anggotanya.',
                        'roles' => ['kesiswaan', 'admin'],
                    ],
                ],
            ],

            [
                'label' => 'Administrasi Surat',
                'item' => [
                    [
                        'label' => 'Surat BK',
                        'icon' => 'fa-envelope-open-text',
                        'roles' => ['guru_bk', 'admin'],
                        'anak' => [
                            [
                                'label' => 'Ringkasan Surat',
                                'route' => 'surat.dashboard',
                                'cocok' => ['surat.dashboard'],
                                'deskripsi' => 'Ringkasan surat yang dibuat BK beserta statusnya.',
                                'roles' => ['guru_bk', 'admin'],
                            ],
                            [
                                'label' => 'Buat Surat',
                                'route' => 'surat.create',
                                'cocok' => ['surat.create', 'surat.store'],
                                'deskripsi' => 'Buat surat baru dengan penomoran otomatis.',
                                'roles' => ['guru_bk', 'admin'],
                            ],
                            [
                                'label' => 'Draft',
                                'route' => 'surat.index',
                                'query' => ['status' => 'draft'],
                                'cocok' => ['surat.index'],
                                'aktifJika' => fn () => request()->routeIs('surat.index') && request('status') === 'draft',
                                'deskripsi' => 'Surat yang masih berupa konsep dan belum diarsipkan.',
                                'roles' => ['guru_bk', 'admin'],
                            ],
                            [
                                'label' => 'Arsip',
                                'route' => 'surat.index',
                                'query' => ['status' => 'diarsipkan'],
                                'cocok' => ['surat.index'],
                                'aktifJika' => fn () => request()->routeIs('surat.index') && request('status') === 'diarsipkan',
                                'deskripsi' => 'Surat yang sudah selesai dan diarsipkan.',
                                'roles' => ['guru_bk', 'admin'],
                            ],
                            [
                                'label' => 'Semua Surat',
                                'route' => 'surat.index',
                                'cocok' => ['surat.index', 'surat.show', 'surat.edit'],
                                'aktifJika' => fn () => (request()->routeIs('surat.index') && ! request('status')) || request()->routeIs('surat.show') || request()->routeIs('surat.edit'),
                                'deskripsi' => 'Seluruh surat BK, dari draft sampai arsip.',
                                'roles' => ['guru_bk', 'admin'],
                            ],
                        ],
                    ],
                    [
                        // Versi baca-saja untuk role yang tidak boleh membuat surat.
                        'label' => 'Arsip Surat BK',
                        'icon' => 'fa-envelope',
                        'route' => 'surat.index',
                        'cocok' => ['surat.index', 'surat.show'],
                        'deskripsi' => 'Melihat dan mencetak surat yang dibuat oleh BK.',
                        'roles' => ['kurikulum', 'kepala_sekolah', 'kesiswaan'],
                    ],
                    [
                        'label' => 'Jenis Surat',
                        'icon' => 'fa-tags',
                        'route' => 'jenis-surat.index',
                        'cocok' => ['jenis-surat.*'],
                        'deskripsi' => 'Master jenis surat, kode penomoran, dan format formulirnya.',
                        'roles' => ['tu', 'admin'],
                    ],
                ],
            ],

            [
                'label' => 'Data Master',
                'item' => [
                    [
                        'label' => 'Pemetaan Guru Mengajar',
                        'icon' => 'fa-diagram-project',
                        'route' => 'kurikulum.guru-mengajar.index',
                        'cocok' => ['kurikulum.guru-mengajar.*'],
                        'deskripsi' => 'Tentukan guru mengajar mata pelajaran apa di kelas mana.',
                        'roles' => ['kurikulum', 'admin'],
                    ],
                    [
                        'label' => 'Pemetaan Guru BK',
                        'icon' => 'fa-user-shield',
                        'route' => 'kurikulum.guru-bk.index',
                        'cocok' => ['kurikulum.guru-bk.*'],
                        'deskripsi' => 'Tentukan kelas binaan tiap Guru BK.',
                        'roles' => ['kurikulum', 'admin'],
                    ],
                    [
                        'label' => 'Jadwal Pelajaran',
                        'icon' => 'fa-calendar-days',
                        'route' => 'jadwal.index',
                        'cocok' => ['jadwal.*'],
                        'deskripsi' => 'Susun jadwal pelajaran per kelas, manual atau lewat import Excel.',
                        'roles' => ['kurikulum', 'admin'],
                    ],
                    [
                        'label' => 'Data Siswa',
                        'icon' => 'fa-user-graduate',
                        'route' => 'siswa.index',
                        'cocok' => ['siswa.index', 'siswa.store', 'siswa.update', 'siswa.destroy', 'siswa.import*', 'siswa.template', 'siswa.pindah-kelas', 'siswa.riwayat-kelas'],
                        'deskripsi' => 'Data induk siswa, kelas, dan nomor WhatsApp orang tua.',
                        'roles' => ['kurikulum', 'admin'],
                    ],
                    [
                        'label' => 'Akun Orang Tua',
                        'icon' => 'fa-users',
                        'route' => 'orangtua-akun.index',
                        'cocok' => ['orangtua-akun.*'],
                        'deskripsi' => 'Akun portal orang tua untuk memantau kehadiran anaknya.',
                        'roles' => ['kurikulum', 'admin'],
                    ],
                    [
                        'label' => 'Data Kelas',
                        'icon' => 'fa-school',
                        'route' => 'kelas.index',
                        'cocok' => ['kelas.*'],
                        'deskripsi' => 'Rombongan belajar beserta wali kelasnya per tahun ajaran.',
                        'roles' => ['kurikulum', 'admin'],
                    ],
                    [
                        'label' => 'Mata Pelajaran',
                        'icon' => 'fa-book',
                        'route' => 'mapel.index',
                        'cocok' => ['mapel.*'],
                        'deskripsi' => 'Daftar mata pelajaran yang diajarkan di sekolah.',
                        'roles' => ['kurikulum', 'admin'],
                    ],
                    [
                        'label' => 'Tahun Ajaran',
                        'icon' => 'fa-calendar-plus',
                        'route' => 'tahun-ajaran.index',
                        'cocok' => ['tahun-ajaran.*'],
                        'deskripsi' => 'Periode akademik: buat, aktifkan, duplikasi, dan kunci periode.',
                        'roles' => ['kurikulum', 'admin'],
                    ],
                ],
            ],

            [
                'label' => 'Pengaturan',
                'item' => [
                    [
                        'label' => 'Pengaturan Sekolah',
                        'icon' => 'fa-gear',
                        'route' => 'pengaturan-sekolah.edit',
                        'cocok' => ['pengaturan-sekolah.*'],
                        'deskripsi' => 'Identitas sekolah & kepala sekolah yang dipakai di semua dokumen cetak.',
                        'roles' => ['kurikulum', 'admin'],
                    ],
                    [
                        'label' => 'Pengaturan Penilaian',
                        'icon' => 'fa-percent',
                        'route' => 'penilaian.pengaturan.edit',
                        'cocok' => ['penilaian.pengaturan.*'],
                        'deskripsi' => 'Bobot nilai rapor, KKTP tiap tingkat, dan bentuk kolom daftar nilai.',
                        'roles' => ['kurikulum', 'admin'],
                    ],
                    [
                        'label' => 'Jam Pelajaran',
                        'icon' => 'fa-clock',
                        'route' => 'jam-pelajaran.index',
                        'cocok' => ['jam-pelajaran.*'],
                        'deskripsi' => 'Pengaturan jam ke-1 sampai terakhir beserta rentang waktunya.',
                        'roles' => ['admin'],
                    ],
                    [
                        'label' => 'Kelola Pengguna',
                        'icon' => 'fa-user-gear',
                        'route' => 'users.index',
                        'cocok' => ['users.*'],
                        'deskripsi' => 'Akun guru dan staf beserta hak aksesnya.',
                        'roles' => ['admin'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Menu yang sudah difilter sesuai role user + dilengkapi URL & status aktif.
     * Inilah yang dirender oleh sidebar.
     */
    public static function untuk(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $hasil = [];

        foreach (self::definisi() as $seksi) {
            $itemTampil = [];

            foreach ($seksi['item'] as $item) {
                if (! self::bolehLihat($item, $user)) {
                    continue;
                }

                if (! empty($item['anak'])) {
                    $anakTampil = [];
                    foreach ($item['anak'] as $anak) {
                        if (! self::bolehLihat($anak, $user)) {
                            continue;
                        }
                        if (! self::routeTersedia($anak)) {
                            continue;
                        }
                        $anakTampil[] = self::siapkan($anak);
                    }

                    // Grup tanpa sub menu yang boleh dilihat = tidak usah ditampilkan.
                    if (empty($anakTampil)) {
                        continue;
                    }

                    $itemTampil[] = [
                        'label' => $item['label'],
                        'icon' => $item['icon'] ?? 'fa-circle',
                        'grup' => true,
                        'kunci' => \Illuminate\Support\Str::slug($item['label']),
                        'anak' => $anakTampil,
                        'aktif' => collect($anakTampil)->contains(fn ($a) => $a['aktif']),
                        'cari' => \Illuminate\Support\Str::lower($item['label'].' '.collect($anakTampil)->pluck('label')->implode(' ')),
                    ];

                    continue;
                }

                if (! self::routeTersedia($item)) {
                    continue;
                }

                $itemTampil[] = self::siapkan($item) + ['grup' => false, 'icon' => $item['icon'] ?? 'fa-circle'];
            }

            if (! empty($itemTampil)) {
                $hasil[] = ['label' => $seksi['label'], 'item' => $itemTampil];
            }
        }

        return $hasil;
    }

    /**
     * Metadata halaman yang sedang dibuka (label, deskripsi, seksi, induk).
     * Dipakai untuk breadcrumb & sub judul halaman.
     *
     * @return array{seksi: string, induk: ?string, label: string, deskripsi: ?string}|null
     */
    public static function halamanAktif(?User $user = null): ?array
    {
        foreach (self::definisi() as $seksi) {
            foreach ($seksi['item'] as $item) {
                // Kalau user diketahui, hanya menu yang memang boleh ia
                // lihat yang dipakai — supaya breadcrumb tidak menyebut
                // grup milik role lain (mis. Kesiswaan yang hanya bisa
                // membaca arsip surat tidak dilabeli grup "Surat BK").
                if ($user && ! self::bolehLihat($item, $user)) {
                    continue;
                }

                if (! empty($item['anak'])) {
                    foreach ($item['anak'] as $anak) {
                        if ($user && ! self::bolehLihat($anak, $user)) {
                            continue;
                        }
                        if (self::cocokDenganHalaman($anak)) {
                            return [
                                'seksi' => $seksi['label'],
                                'induk' => $item['label'],
                                'label' => $anak['label'],
                                'deskripsi' => $anak['deskripsi'] ?? null,
                            ];
                        }
                    }

                    continue;
                }

                if (self::cocokDenganHalaman($item)) {
                    return [
                        'seksi' => $seksi['label'],
                        'induk' => null,
                        'label' => $item['label'],
                        'deskripsi' => $item['deskripsi'] ?? null,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Apakah user boleh membuka route ini? Dipakai di dashboard supaya
     * tautan pintas tidak pernah mengarah ke halaman yang berujung 403
     * (masalah lama: Kepala Sekolah disodori tautan "Kelola Pengguna").
     */
    public static function bolehAkses(string $namaRoute, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        foreach (self::definisi() as $seksi) {
            foreach ($seksi['item'] as $item) {
                $kandidat = ! empty($item['anak']) ? $item['anak'] : [$item];
                foreach ($kandidat as $satu) {
                    if (($satu['route'] ?? null) === $namaRoute && self::bolehLihat($satu, $user)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    // ================= internal =================

    private static function bolehLihat(array $item, User $user): bool
    {
        if (! in_array($user->role, $item['roles'] ?? [], true)) {
            return false;
        }

        if (isset($item['syarat']) && is_callable($item['syarat'])) {
            return (bool) ($item['syarat'])($user);
        }

        return true;
    }

    private static function routeTersedia(array $item): bool
    {
        return isset($item['route']) && RouteFacade::has($item['route']);
    }

    private static function siapkan(array $item): array
    {
        $params = $item['params'] ?? [];
        $query = $item['query'] ?? [];

        return [
            'label' => $item['label'],
            'url' => route($item['route'], array_merge($params, $query)),
            'aktif' => self::cocokDenganHalaman($item),
            'deskripsi' => $item['deskripsi'] ?? null,
            'cari' => \Illuminate\Support\Str::lower($item['label']),
        ];
    }

    private static function cocokDenganHalaman(array $item): bool
    {
        if (isset($item['aktifJika']) && is_callable($item['aktifJika'])) {
            return (bool) ($item['aktifJika'])();
        }

        $pola = $item['cocok'] ?? [];

        return ! empty($pola) && request()->routeIs(...$pola);
    }
}
