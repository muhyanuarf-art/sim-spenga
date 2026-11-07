<?php

/**
 * PEMBUAT DOKUMEN "TANYA JAWAB SIM-SPENGA".
 *
 * Isinya disusun dari pertanyaan yang BENAR-BENAR pernah diajukan selama
 * pembangunan aplikasi — bukan daftar yang dikarang supaya terlihat
 * lengkap. Tiap butir punya tiga bagian tetap: pertanyaannya, jawabannya,
 * dan apa yang harus dilakukan Admin supaya tidak salah setel.
 *
 * Jalankan:  php docs/_pembuat-faq.php
 */

require __DIR__.'/_Docx.php';

$d = new Docx();

// =====================================================================
$d->sampulJudul('TANYA JAWAB', 'SIM-SPENGA', [
    'SMP Negeri 3 Bumiayu',
    '',
    'Pertanyaan yang sering muncul,',
    'jawabannya, dan apa yang harus',
    'dilakukan Admin',
    '',
    'Edisi Oktober 2026',
]);

// =====================================================================
$d->h1('Cara memakai dokumen ini');

$d->p('Dokumen ini bukan panduan langkah demi langkah — untuk itu ada **Panduan SIM-SPENGA**. Yang ada di sini adalah jawaban atas keadaan yang benar-benar terjadi di sekolah: guru pensiun di tengah semester, semester berganti, hari libur, impor Excel gagal, dan sebagainya.');

$d->p('Tiap butir disusun sama: **pertanyaannya**, lalu **jawabannya**, lalu **yang harus dilakukan Admin**. Bagian ketiga itu yang paling penting — banyak masalah di aplikasi ini bukan kerusakan, melainkan urutan setelan yang terlewat.');

$d->tabel(['Bagian', 'Isi'], [
    ['A', 'Akun dan cara masuk'],
    ['B', 'Semester, tahun ajaran, dan data lama'],
    ['C', 'Pergantian guru di tengah semester'],
    ['D', 'Jadwal, jurnal mengajar, dan kehadiran'],
    ['E', 'Nilai dan rapor'],
    ['F', 'Impor data lewat Excel'],
    ['G', 'Pemberitahuan dan WhatsApp'],
    ['H', 'Arsip semester dan pencadangan'],
    ['I', 'Keamanan dan lisensi'],
    ['J', 'Aplikasi Android'],
    ['K', 'Kalau ada yang terasa tidak beres'],
], [12, 88]);

$d->catatan('Satu aturan yang berlaku di seluruh dokumen',
    'Di aplikasi ini **tidak ada data yang dihapus** karena pergantian semester, pergantian tahun ajaran, atau pergantian guru. Yang berubah hanya siapa yang boleh menulis dan apa yang ditampilkan. Kalau ada data yang terasa hilang, hampir selalu sebabnya periode yang sedang dilihat, bukan datanya yang lenyap.');

// =====================================================================
$d->h1('Bagian A — Akun dan cara masuk');

$d->h2('A.1 Guru pensiun atau pindah tugas. Akunnya dihapus?');
$d->p('**Jangan dihapus. Nonaktifkan.** Akun yang dinonaktifkan langsung ditolak saat masuk meski email dan kata sandinya benar, dan sesi yang sedang berjalan ikut terputus pada klik berikutnya. Seluruh jurnal, nilai, absensi, dan catatan BK-nya tetap utuh dan tetap atas namanya.');
$d->p('Menghapus akun akan membuat namanya hilang dari seluruh laporan lampau. Sebagian tabel memang menolak penghapusan itu, tetapi jangan diandalkan sebagai pengaman.');
$d->langkah([
    'Buka **Kelola Pengguna**.',
    'Cari namanya, tekan **Nonaktifkan**.',
    'Pastikan di bawah tulisan "Nonaktif" muncul tanggalnya — tanggal itu dipakai Rekapitulasi Kepatuhan.',
]);

$d->h2('A.2 Apakah data guru yang dinonaktifkan ikut hilang?');
$d->p('Tidak. Namanya tetap muncul di laporan jurnal, kehadiran mengajar, dan rekapitulasi. Yang hilang hanya kemampuannya untuk masuk.');

$d->h2('A.3 Guru lupa kata sandi. Apa yang dilakukan?');
$d->p('Ada dua jalan. Guru bisa memakai **Lupa kata sandi** sendiri di halaman masuk — kodenya dikirim lewat WhatsApp ke nomor yang terdaftar. Atau Admin menekan **Reset Sandi** di Kelola Pengguna.');
$d->catatan('Syarat yang sering terlewat',
    'Jalur Lupa Kata Sandi hanya berfungsi bila **nomor WhatsApp guru sudah terisi** di Kelola Pengguna. Kalau kosong, kodenya tidak akan pernah sampai dan guru akan mengira aplikasinya rusak. Periksa sekarang, jangan menunggu ada yang lupa.');

$d->h2('A.4 Kalau lupa keluar, apakah otomatis keluar sendiri?');
$d->p('Ya. Sesi bertahan **8 jam** sejak terakhir dipakai. Lewat dari itu aplikasi meminta masuk kembali.');

$d->h2('A.5 Apakah "Ingat saya" aman?');
$d->p('Aman di perangkat milik sendiri. Penanda yang disimpan di peramban tidak bisa dipakai ulang bila seseorang berhasil menyalin isi database — penandanya disimpan dalam bentuk teracak, bukan apa adanya.');
$d->catatan('Tetap ada satu aturan',
    'Jangan centang **Ingat saya** di komputer bersama, misalnya komputer ruang guru. Orang berikutnya yang memakai komputer itu akan masuk sebagai Anda.');

$d->h2('A.6 Boleh satu akun dipakai dua orang?');
$d->p('Tidak boleh. Setiap jurnal, nilai, catatan BK, dan prestasi tersimpan **beserta nama pembuatnya**. Akun yang dipakai berdua membuat catatan itu menunjuk orang yang salah, dan tidak ada cara memperbaikinya belakangan.');

$d->h2('A.7 Guru BK boleh menjadi wali kelas?');
$d->p('Boleh. Namanya muncul di pilihan wali kelas pada menu **Data Kelas**, sama seperti guru biasa. Seluruh tugas wali kelas ikut terbuka untuknya: Nilai Rapor Kelas, Jurnal Mengajar Kelas, Rekap Absensi Kelas, Prestasi Siswa, dan Absensi Kegiatan Sekolah.');

$d->h2('A.8 Kurikulum atau Kesiswaan mengampu mata pelajaran. Bisa dipetakan?');
$d->p('Bisa. Pilihan pengajar di **Pemetaan Guru Mengajar** memuat seluruh pengguna yang akunnya aktif, bukan hanya yang berperan Guru. Nama perannya ikut ditulis di belakang nama supaya tidak tertukar.');
$d->p('Jurnal dan kepatuhannya ikut terpantau seperti guru biasa.');

// =====================================================================
$d->h1('Bagian B — Semester, tahun ajaran, dan data lama');

$d->h2('B.1 Ganti semester. Apakah data semester lalu dihapus?');
$d->p('Tidak. Setiap nilai, jurnal, absensi, kasus BK, dan prestasi terikat pada tahun ajaran dan semesternya masing-masing. Mengganti semester berarti **menambah wadah baru**, bukan mengosongkan yang lama.');

$d->h2('B.2 Data lima tahun lalu masih bisa dibuka?');
$d->p('Masih, selama databasenya ada. Pilih periodenya di **pemilih periode** di kepala halaman, lalu seluruh aplikasi menampilkan keadaan sekolah pada semester itu.');
$d->p('Pemilih periode juga menentukan **peran Anda sendiri** pada periode itu. Guru yang dulu menjadi wali kelas 7A, lalu sekarang bukan, tetap bisa membuka rekap kelasnya dengan memilih periode itu.');

$d->h2('B.3 Wali kelas berganti di semester berikutnya. Guru lama kehilangan datanya?');
$d->p('Tidak. Penugasan wali kelas dicatat **per semester**. Guru lama tetap tercatat sebagai wali kelas pada semester ketika ia memang menjabat, dan tetap bisa membuka laporannya lewat pemilih periode.');

$d->h2('B.4 Kenapa tombol Simpan hilang atau datanya tidak bisa diubah?');
$d->p('Karena periode yang sedang Anda lihat **bukan periode aktif**, atau semesternya sudah ditutup. Periode selain yang aktif selalu baca-saja: bisa dilihat dan dicetak, tidak bisa diubah.');
$d->p('Ini yang menjaga agar data semester yang sudah lewat tidak berubah tanpa sengaja.');
$d->langkah([
    'Lihat pemilih periode di kepala halaman.',
    'Kalau tertulis periode lampau, kembalikan ke periode yang bertanda **berjalan**.',
    'Kalau periodenya memang aktif tetapi tetap terkunci, berarti semesternya sudah ditutup — hanya Admin yang dapat membukanya kembali di menu Tahun Ajaran.',
]);

$d->h2('B.5 Tombol Hapus di menu Tahun Ajaran — berbahaya?');
$d->p('Tidak lagi. Sebuah tahun ajaran **hanya bisa dihapus bila tidak ada apa pun yang menunjuk kepadanya**. Cukup satu kelas, satu nilai, satu prestasi, atau satu mata pelajaran yang terkait, penghapusan langsung ditolak, dan pesannya menyebut apa saja yang masih memakainya.');
$d->catatan('Untuk mengakhiri semester, gunakan Tutup Semester',
    'Hapus hanya untuk membatalkan tahun ajaran yang **baru dibuat karena salah ketik**. Untuk mengakhiri semester yang sudah berjalan, yang benar adalah **Tutup Semester** — datanya tetap, hanya menjadi baca-saja.');

$d->h2('B.6 Checklist di Dashboard menunjukkan belum selesai, padahal sudah diisi');
$d->p('Checklist memeriksa **periode yang sedang aktif**. Kalau yang tercentang masih kurang padahal datanya sudah lengkap, periksa apakah periode yang aktif memang periode yang sedang Anda isi.');
$d->p('Satu langkah yang wajar belum tercentang: **Susun Jadwal Pelajaran**. Checklist tidak akan menghijaukannya sampai jadwalnya benar-benar disusun.');

// =====================================================================
$d->h1('Bagian C — Pergantian guru di tengah semester');

$d->p('Bagian ini menjawab keadaan yang paling sering ditanyakan: seorang guru pensiun atau pindah tugas di pertengahan semester, lalu digantikan guru lain.');

$d->h2('C.1 Jurnal dan absensi sebelum dia keluar, bagaimana?');
$d->p('**Tetap atas namanya.** Setiap jurnal menyimpan nama gurunya sendiri, bukan sekadar menunjuk ke jadwal. Jadi jurnal yang diisi sebelum pergantian selamanya milik guru lama, dan jurnal setelahnya milik guru pengganti.');
$d->p('Absensi siswa **tidak menyimpan nama guru sama sekali** — ia menempel pada jurnalnya. Jadi tidak mungkin tertukar: ke mana jurnalnya tercatat, ke situ absensinya ikut.');
$d->tabel(['Tanggal', 'Tercatat atas nama', 'Absensi'], [
    ['5 Oktober', 'Guru lama', 'ikut guru lama'],
    ['12 Oktober', 'Guru lama', 'ikut guru lama'],
    ['15 Oktober', 'pergantian', '—'],
    ['19 Oktober', 'Guru pengganti', 'ikut guru pengganti'],
    ['26 Oktober', 'Guru pengganti', 'ikut guru pengganti'],
], [22, 44, 34]);

$d->h2('C.2 Apa saja yang harus Admin ubah?');
$d->p('Tiga langkah, dan **langkah ketiga yang paling sering terlewat**. Tanpa langkah ketiga, guru pengganti tidak melihat satu pun sesi untuk diisi.');
$d->langkah([
    '**Kelola Pengguna** — nonaktifkan akun guru yang keluar. Tanggalnya tercatat otomatis.',
    '**Pemetaan Guru Mengajar** — tambahkan guru pengganti untuk kelas dan mata pelajaran yang sama.',
    '**Jadwal Pelajaran** — ubah baris jadwalnya ke guru pengganti. Ini yang memotong tanggalnya.',
    'Bila ia juga wali kelas, ganti walinya di **Data Kelas**.',
]);

$d->h2('C.3 Rekapitulasi Kepatuhan jadi salah?');
$d->p('Tidak. Laporan itu menghitung berdasarkan **siapa yang benar-benar menulis jurnalnya**, bukan siapa yang memegang jadwalnya hari ini.');
$d->p('Jadi guru yang keluar tetap muncul dengan angka sebenarnya, dan guru pengganti tidak dibebani pekan-pekan sebelum ia datang. Tanggal setelah seorang guru dinonaktifkan juga tidak lagi dituntut darinya.');
$d->catatan('Sisa kecil yang perlu diketahui',
    'Tanggal sebelum pergantian yang **tidak diisi siapa pun** tetap dibebankan kepada guru pengganti. Hari-hari itu memang jurnal yang hilang, jadi layak terlihat — tetapi jangan dipakai menilai kinerja guru pengganti pada bulan pergantian.');

$d->h2('C.4 Tanda tangan "Guru Mata Pelajaran" di rapor ikut berpindah?');
$d->p('**Tidak.** Pemilik lembar nilai ditetapkan sekali saat lembar itu pertama dibuat, dan finalisasi mempertahankannya. Jadi rapor akan tercetak atas nama **guru lama** sepanjang semester itu, meski guru pengganti yang menyelesaikan nilainya.');
$d->catatan('Ini perlu diputuskan sekolah',
    'Kalau rapor harus ditandatangani guru yang menjabat sekarang, setelan ini perlu diubah oleh pengembang. Bukan kekeliruan sistem — hanya pilihan yang belum pernah ditentukan.');

$d->h2('C.5 Guru pengganti memperbaiki jurnal lama. Apa yang terjadi?');
$d->p('**Jurnalnya berpindah kepemilikan kepada yang menyunting**, beserta absensinya. Catatan guru lama menyusut, catatan guru pengganti bertambah, tanpa peringatan apa pun.');
$d->catatan('Hindari dulu sampai diperbaiki',
    'Jangan menyunting ulang jurnal yang diisi guru sebelumnya. Kalau ada yang perlu dibetulkan, catat di kolom keterangan jurnal baru, atau minta pengembang memperbaiki perilaku ini lebih dulu.');

// =====================================================================
$d->h1('Bagian D — Jadwal, jurnal mengajar, dan kehadiran');

$d->h2('D.1 Hari libur. Apakah pengingat WhatsApp tetap terkirim?');
$d->p('Tidak, **asal liburnya sudah dicatat**. Menu **Kalender Libur** menyimpan tanggal-tanggal tanpa KBM. Pada tanggal itu pengingat tidak dikirim, dan Rekapitulasi Kepatuhan tidak menghitungnya sebagai jurnal yang belum diisi.');
$d->p('Hari Minggu tidak perlu dicatat — sudah dilewati sejak awal. Begitu pula hari yang memang tidak punya jadwal pelajaran.');
$d->langkah([
    'Buka **Kalender Libur** di Data Master.',
    'Isi libur semester, tanggal merah, dan cuti bersama **sebelum tanggalnya tiba**.',
    'Untuk libur sehari, isi tanggal selesai sama dengan tanggal mulai.',
]);
$d->catatan('Kalau kalendernya kosong',
    'Selama Kalender Libur kosong, setiap hari mengajar dianggap hari efektif — termasuk tanggal merah. Guru akan ditagih jurnal pada hari sekolah libur, dan angka kepatuhannya terlihat lebih buruk daripada kenyataan.');

$d->h2('D.2 Ada kegiatan sekolah, KBM tidak berjalan. Jurnalnya bagaimana?');
$d->p('Kelas yang sedang berkegiatan **dikecualikan otomatis**. Pada hari itu yang mengisi kehadiran adalah wali kelas lewat Absensi Kegiatan, bukan guru mapel lewat jurnal.');
$d->p('Cakupan kegiatan dihormati apa adanya: kegiatan khusus kelas 7 tidak membebaskan kelas 8 dan 9.');

$d->h2('D.3 Guru lupa mengisi jurnal. Admin bisa membantu?');
$d->p('Bisa. Admin dan Kurikulum dapat membuka menu Absensi & Jurnal Mengajar atas nama guru tertentu dan mengisikannya. Jurnalnya tetap tercatat atas nama **guru yang terjadwal**, bukan atas nama Admin.');

$d->h2('D.4 Jadwal pelajaran sering berubah mendadak. Apakah tertangani?');
$d->p('Jadwal Pelajaran adalah jadwal tetap mingguan. Perubahan mendadak — guru berhalangan, jam dibatalkan karena rapat — **tidak tercatat di sistem**. Jurnal yang tidak terisi pada hari itu akan terlihat sebagai jurnal yang hilang.');
$d->p('Untuk kejadian yang direncanakan, catat sebagai **Kegiatan Sekolah** atau **Kalender Libur** supaya tidak ditagih.');

// =====================================================================
$d->h1('Bagian E — Nilai dan rapor');

$d->h2('E.1 Bagaimana nilai akhir dihitung?');
$d->p('Dua tahap. Pertama, rata-rata formatif (RT) dan rata-rata sumatif lingkup materi (LM) digabung. Kedua, gabungan itu ditimbang bersama ASTS dan ASAS.');
$d->kode("RT dan LM digabung 50 : 50  ->  komponen 60%\nNilai Akhir = 60% komponen + 20% ASTS + 20% ASAS");
$d->p('Susunan bobot, batas KKTP tiap tingkat, dan kebijakan remedial diatur Admin di menu **Pengaturan Penilaian**. Angka di atas adalah setelan yang berlaku sekarang, bukan aturan mati.');

$d->h2('E.2 Kolom nilai yang masih kosong dihitung nol?');
$d->p('Tidak. Kolom kosong tidak dianggap nol, dan komponen yang belum waktunya diisi tidak ikut membagi. Jadi nilai sementara di tengah semester tetap terlihat wajar.');

$d->h2('E.3 Nilai remedial diperlakukan bagaimana?');
$d->p('Menurut kebijakan yang dipilih di Pengaturan Penilaian. Bawaannya — dan yang disarankan — **nilai remedial dibatasi setinggi KKTP**. Pilihan lain: ambil yang tertinggi, atau rata-rata keduanya.');

$d->h2('E.4 Daftar nilai sudah difinalisasi, ternyata ada yang salah');
$d->p('Hanya **Kurikulum dan Admin** yang dapat membuka kuncinya kembali. Guru mata pelajaran tidak bisa membukanya sendiri — itu disengaja, supaya nilai yang sudah masuk rapor tidak berubah diam-diam.');

$d->h2('E.5 Semester sudah ditutup, ada nilai yang perlu diperbaiki');
$d->p('Admin membuka kembali periodenya di menu Tahun Ajaran. Setelah selesai, **tutup lagi**.');
$d->catatan('Arsip semesternya jadi kedaluwarsa',
    'Membuka kembali semester yang sudah ditutup membuat arsip PDF-nya tidak lagi sesuai dengan datanya. Arsip lama ditandai kedaluwarsa dan perlu dibuat ulang setelah perbaikannya selesai.');

// =====================================================================
$d->h1('Bagian F — Impor data lewat Excel');

$d->h2('F.1 Impor dijalankan tapi tidak ada data yang masuk');
$d->p('Laporan hasil impor selalu menyebut **nomor baris dan alasannya** untuk setiap baris yang dilewati. Baca laporan itu lebih dulu — hampir selalu sebabnya kolom wajib kosong, atau kode kelas yang tidak cocok.');
$d->langkah([
    'Unduh ulang templatenya dari halaman impor, jangan pakai berkas lama.',
    'Jangan mengubah baris judul di baris pertama.',
    'Hapus baris contoh sebelum mengisi data sebenarnya.',
    'Pastikan kode kelas persis sama dengan nama kelas di menu Data Kelas **untuk tahun ajaran aktif**.',
]);

$d->h2('F.2 NIS siswa sudah ada di sistem. Jadi ganda?');
$d->p('Tidak. NIS yang sudah ada akan **dipindahkan** ke kelas yang diisi — dipakai untuk kenaikan kelas atau pindah kelas. Riwayat kelasnya tercatat otomatis dan tetap bisa dilihat Admin maupun orang tua.');

$d->h2('F.3 NIP wali kelas ditolak saat impor Data Kelas');
$d->p('Kolom `nip_wali_kelas` harus berisi NIP guru atau guru BK yang **sudah terdaftar** di Kelola Pengguna. Kalau NIP-nya belum diisi di akun guru itu, impor akan melewatinya.');

$d->h2('F.4 NIP pengajar ditolak saat impor Pemetaan Guru Mengajar');
$d->p('Kolom `nip_guru` harus berisi NIP pengguna yang **akunnya masih aktif**. Perannya tidak harus Guru — Kurikulum, Kesiswaan, Guru BK, dan Kepala Sekolah juga diterima bila memang mengampu mata pelajaran.');

// =====================================================================
$d->h1('Bagian G — Pemberitahuan dan WhatsApp');

$d->h2('G.1 Lonceng di kanan atas itu isinya apa?');
$d->p('Laporan yang masuk untuk Anda. Ada enam jenis:');
$d->tabel(['Kejadian', 'Yang diberi tahu'], [
    ['Laporan kasus siswa', 'Guru BK kelas itu, wali kelas, Kesiswaan'],
    ['Prestasi menunggu verifikasi', 'Kesiswaan'],
    ['Prestasi sudah diverifikasi', 'Wali kelas yang mencatatnya'],
    ['Pemanggilan orang tua dicatat', 'Wali kelas, Kesiswaan'],
    ['Daftar nilai difinalisasi', 'Kurikulum dan wali kelas'],
    ['Surat diterbitkan untuk siswa', 'Wali kelas siswa itu'],
], [42, 58]);
$d->p('Diklik, pemberitahuan langsung mengantar ke menu yang sesuai isinya. Penandanya dibersihkan sendiri setelah 90 hari — yang dibuang hanya penunjuknya, datanya tetap di menunya masing-masing.');

$d->h2('G.2 Loncengnya tidak berbunyi');
$d->p('Memang tidak berbunyi. Peramban memblokir suara yang muncul sendiri tanpa interaksi pengguna — itu aturan peramban, bukan setelan aplikasi. Penandanya warna merah dan angka jumlahnya.');

$d->h2('G.3 Apakah pemberitahuan sampai ke HP saat aplikasi tertutup?');
$d->p('**Tidak.** Lonceng hanya terlihat saat aplikasi dibuka. Untuk kabar yang benar-benar mendesak, pakai WhatsApp — saluran itu sudah berjalan di aplikasi ini dan lebih pasti terbaca.');
$d->p('Pemberitahuan yang muncul sendiri di layar HP memerlukan layanan tambahan dan pembangunan ulang aplikasi Android. Belum dipasang.');

$d->h2('G.4 Pengingat jurnal lewat WhatsApp tidak terkirim');
$d->p('Periksa berurutan, dari yang paling sering:');
$d->tabel(['Gejala', 'Penyebab yang paling mungkin'], [
    ['Tidak ada riwayat sama sekali', 'Penjadwal tidak berjalan, atau saklarnya belum dinyalakan'],
    ['Riwayat ada, semuanya **Menunggu**', 'Pekerja antrian (`queue:work`) tidak berjalan'],
    ['Semuanya **Gagal**, keterangan nomor', 'Nomor WhatsApp guru belum diisi di Kelola Pengguna'],
    ['Semuanya **Gagal**, keterangan token', 'Token perangkat salah atau terputus di Fonnte'],
    ['Banyak **Kedaluwarsa**', 'Pekerja antrian sempat mati berjam-jam'],
], [40, 60]);

// =====================================================================
$d->h1('Bagian H — Arsip semester dan pencadangan');

$d->h2('H.1 Arsip semester itu apa, dan kapan dibuat?');
$d->p('Satu berkas ZIP berisi seluruh laporan semester dalam bentuk PDF berkop surat, tersusun per bagian. Dibuat **manual oleh Admin** lewat tombol di menu Tahun Ajaran, bukan otomatis saat semester ditutup.');
$d->p('Gunanya untuk hal yang tidak bisa dilakukan database: menyerahkan laporan sesemester kepada pengawas, atau menyimpannya di luar aplikasi.');

$d->h2('H.2 Pembuatan arsip lama sekali, atau tidak selesai');
$d->p('Hampir selalu karena **pekerja antrian tidak berjalan**. Arsip dikerjakan di latar belakang, bukan saat tombolnya ditekan.');
$d->kode('php artisan queue:work --queue=arsip,notifikasi,pengingat-guru,default');
$d->catatan('Daftar antriannya wajib disebutkan',
    '`queue:work` tanpa argumen hanya melayani antrian `default`, sehingga arsip dan pengingat WhatsApp menunggu selamanya **tanpa pesan galat apa pun**. Gejalanya "kok lama sekali", bukan "gagal".');

$d->h2('H.3 Semester lampau dibuka kembali. Arsipnya bagaimana?');
$d->p('Arsip lama ditandai **kedaluwarsa** secara otomatis, karena isinya tidak lagi sesuai dengan datanya. Buat arsip baru setelah perbaikan selesai dan semesternya ditutup lagi.');

$d->h2('H.4 Arsip hilang setelah setahun?');
$d->p('Berkas ZIP-nya dihapus setelah **12 bulan**, catatannya tetap tersimpan beserta keterangan kenapa hilang. Datanya sendiri tidak disentuh, jadi arsipnya selalu bisa dibuat ulang.');
$d->catatan('Kalau ada laporan yang wajib disimpan',
    'Unduh dan simpan di luar aplikasi sebelum setahun lewat. Arsip adalah **foto**, bukan tempat penyimpanan.');

$d->h2('H.5 Bagaimana mencadangkan seluruh data?');
$d->p('Satu perintah, menghasilkan satu berkas terenkripsi berisi database, berkas unggahan, dan kunci aplikasi:');
$d->kode("php artisan backup:buat");
$d->p('Membukanya kembali memakai `php artisan backup:buka`. Simpan berkasnya di luar komputer server — cadangan yang berada di mesin yang sama tidak menolong bila mesinnya rusak.');

// =====================================================================
$d->h1('Bagian I — Keamanan dan lisensi');

$d->h2('I.1 Masa aktif lisensi habis. Apa yang terjadi?');
$d->p('Aplikasi terkunci dan tidak bisa dibuka siapa pun. Tidak ada data yang hilang. Begitu lisensinya diperpanjang, aplikasi terbuka kembali sendiri — tekan tombol **Periksa Sekarang** di halaman terkunci bila tidak langsung terbuka.');

$d->h2('I.2 Apa beda mode lokal dan mode server?');
$d->p('Mode **lokal** memeriksa surat lisensi yang tersimpan di komputer sendiri. Mode **server** menanyakannya ke server FF Production secara berkala. Mode server lebih aman, tetapi memerlukan sambungan internet.');
$d->catatan('Setelan saat ini',
    'Aplikasi ini masih berjalan pada mode **lokal**. Jangan ubah ke mode server sebelum server lisensi benar-benar siap — aplikasi akan terkunci karena sapaannya tidak terjawab.');

$d->h2('I.3 Data uji coba dibersihkan. Apakah lisensinya ikut hilang?');
$d->p('Tidak. Identitas pemasangan dan surat lisensi termasuk yang **dipertahankan** oleh `data:kosongkan`, bersama akun pengguna, kelas, mata pelajaran, jam pelajaran, jenis pelanggaran, dan pengaturan sekolah.');
$d->p('Perintah itu juga membuat cadangan database otomatis sebelum menghapus apa pun, dan meminta ketikan konfirmasi.');

$d->h2('I.4 Guru pensiun tetapi masih bisa masuk');
$d->p('Berarti akunnya belum dinonaktifkan. Mengganti kata sandinya saja tidak cukup — gunakan tombol **Nonaktifkan** di Kelola Pengguna, yang sekaligus memutus sesi yang sedang berjalan.');

// =====================================================================
$d->h1('Bagian J — Aplikasi Android');

$d->h2('J.1 Setiap ada perubahan di web, apakah APK harus dibuat ulang?');
$d->p('Tidak. Aplikasi Android adalah pembungkus halaman web, jadi perubahan menu, tampilan, dan fitur langsung ikut tanpa pemasangan ulang.');
$d->p('Pembangunan ulang hanya diperlukan bila yang berubah bagian **native**-nya: halaman masuk, sidik jari, ikon aplikasi, atau alamat server.');

$d->h2('J.2 Cara mengaktifkan masuk dengan sidik jari');
$d->p('Masuk sekali dengan email dan kata sandi, lalu pilih **Aktifkan** saat ditawarkan. Berikutnya cukup menempelkan sidik jari.');
$d->p('Sidik jarinya tersimpan di ponsel masing-masing, bukan di server sekolah. Setelah kata sandi disetel ulang, sidik jari perlu diaktifkan lagi.');

$d->h2('J.3 Tidak bisa masuk dari HP padahal kata sandinya benar');
$d->p('Periksa berurutan: apakah ponsel tersambung ke jaringan yang sama dengan server; apakah akunnya aktif; apakah perannya diizinkan masuk lewat aplikasi. Akun Admin dan portal orang tua memakai jalur berbeda.');

// =====================================================================
$d->h1('Bagian K — Kalau ada yang terasa tidak beres');

$d->h2('K.1 Perubahan sudah dibuat tetapi tidak muncul di layar');
$d->p('Aplikasi berpindah halaman tanpa memuat ulang, jadi halaman yang pernah dibuka bisa tersimpan di peramban. Tekan **Ctrl+F5** pada halaman itu.');

$d->h2('K.2 Tampilan tiba-tiba polos, tombol dan warnanya hilang');
$d->p('Berkas tampilan belum dibangun ulang setelah kode berubah. Minta pengembang menjalankan pembangunan ulang, lalu tekan Ctrl+F5.');

$d->h2('K.3 Arsip, pengingat, atau pemberitahuan tidak jalan padahal kode sudah diperbarui');
$d->p('Pekerja antrian adalah proses yang hidup terus. Ia memuat kode **sekali** saat dijalankan, lalu memakai salinan itu seterusnya — pembaruan tidak pernah sampai kepadanya.');
$d->p('Gejalanya menyesatkan karena tidak ada galat sama sekali: pekerjaannya tetap selesai, hanya dikerjakan oleh versi lama.');
$d->kode("php artisan queue:restart");
$d->catatan('Jalankan setiap kali aplikasi diperbarui',
    'Perintah itu tidak menyalakan apa pun — ia hanya menyuruh pekerja yang sedang hidup berhenti dengan rapi setelah pekerjaan yang berjalan selesai. Arsip semester pernah keluar tanpa KOP surat persis karena langkah ini terlewat.');

$d->h2('K.4 Muncul "Anda tidak memiliki akses ke halaman ini"');
$d->p('Perannya memang tidak berhak. Ini bukan kerusakan — tetapi bila menurut Anda peran itu seharusnya berhak, catat **menu apa** dan **peran apa**, lalu sampaikan kepada pengembang.');

$d->h2('K.5 Daftar panjang tetapi tidak ada tombol halaman');
$d->p('Tombol halaman hanya muncul bila isinya lebih dari satu halaman. Kalau Anda sedang memakai penyaring — misalnya menyaring peran tertentu di Kelola Pengguna — hasilnya bisa muat satu halaman, dan itu bukan kerusakan.');

$d->h2('K.6 Ke mana melapor kalau tetap bermasalah');
$d->langkah([
    'Catat menu apa yang dibuka dan langkah apa yang ditekan.',
    'Bila ada pesan di layar, fotolah pesannya.',
    'Sebutkan peran akun yang dipakai dan periode yang sedang dilihat.',
    'Sampaikan ketiganya kepada Admin sekolah atau pengembang.',
]);

// =====================================================================
$d->h1('Lampiran — Delapan setelan yang paling sering terlewat');

$d->p('Daftar ini dirangkum dari seluruh pertanyaan di dokumen ini. Semuanya bukan kerusakan aplikasi, melainkan setelan yang belum dikerjakan.');

$d->tabel(['Yang terlewat', 'Akibatnya'], [
    ['Kalender Libur belum diisi', 'Guru ditagih jurnal pada tanggal merah, angka kepatuhan tampak buruk'],
    ['Nomor WhatsApp guru kosong', 'Lupa kata sandi dan pengingat jurnal tidak pernah sampai'],
    ['Pekerja antrian tidak berjalan', 'Arsip dan WhatsApp menunggu selamanya tanpa pesan galat'],
    ['`queue:restart` tidak dijalankan setelah pembaruan', 'Pekerjaan dikerjakan versi lama, tanpa tanda apa pun'],
    ['Jadwal Pelajaran tidak diubah saat guru berganti', 'Guru pengganti tidak melihat sesi untuk diisi'],
    ['Akun guru pensiun dihapus, bukan dinonaktifkan', 'Namanya hilang dari laporan lampau'],
    ['Periode aktif salah saat mengisi data', 'Data mendarat di semester yang keliru, terlihat seperti hilang'],
    ['Semester lampau dibuka lalu lupa ditutup', 'Data historis bisa berubah tanpa sengaja'],
], [42, 58]);

$d->catatan('Satu kebiasaan yang menyelamatkan banyak hal',
    'Sebelum mengisi apa pun, lihat **pemilih periode** di kepala halaman. Pastikan tertulis periode yang bertanda berjalan. Sebagian besar keluhan "datanya hilang" berakhir di sini.');

// =====================================================================
$keluar = __DIR__.'/FAQ-SIM-SPENGA.docx';
@mkdir(dirname($keluar), 0777, true);
$d->simpan($keluar);

echo "Dokumen dibuat: {$keluar}\n";
echo 'Ukuran: '.number_format(filesize($keluar) / 1024, 1)." KB\n";
