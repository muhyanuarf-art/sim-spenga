<?php

namespace Database\Seeders;

use App\Models\JenisSurat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Master Jenis Surat + template siap pakai — supaya Kesiswaan/BK tidak
 * perlu menulis surat dari nol (risiko typo/salah format). Semua
 * template pakai placeholder yang otomatis diisi sistem — lihat
 * App\Support\SuratMerge::DAFTAR_PLACEHOLDER.
 *
 * Aman dijalankan berulang kali (updateOrCreate berdasarkan kode_jenis)
 * — tidak membuat data dobel, dan TIDAK menimpa jenis surat yang sudah
 * ada dengan kode_jenis sama (kalau Kesiswaan/BK sudah pernah mengedit
 * template bawaan ini secara manual, edit tsb TETAP DIPAKAI — seeder
 * cuma mengisi kalau belum ada baris dengan kode itu; kalau mau paksa
 * kembalikan ke bawaan, hapus dulu barisnya lewat menu Jenis Surat).
 *
 * Jalankan dengan: php artisan db:seed --class=JenisSuratSeeder
 */
class JenisSuratSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach ($this->data() as $row) {
                // Kalau kode_jenis ini SUDAH ADA, jangan sentuh isinya
                // sama sekali (hormati perubahan manual yang mungkin
                // sudah dibuat) — cuma buat kalau memang belum ada.
                JenisSurat::firstOrCreate(
                    ['kode_jenis' => $row['kode_jenis']],
                    [
                        'nama_jenis' => $row['nama_jenis'],
                        'kategori' => $row['kategori'],
                        'template_isi' => $row['template_isi'],
                        'is_aktif' => true,
                    ]
                );
            }
        });
    }

    private function data(): array
    {
        $footer = "Demikian surat ini kami sampaikan untuk dapat dipergunakan sebagaimana mestinya. Atas perhatian Bapak/Ibu, kami ucapkan terima kasih.";

        return [
            [
                'kode_jenis' => 'SP',
                'nama_jenis' => 'Surat Panggilan Orang Tua/Wali Siswa',
                'kategori' => 'keluar',
                'template_isi' => "Sehubungan dengan permasalahan yang menyangkut ananda {nama_siswa} (NIS: {nis}), siswa kelas {kelas}, dengan ini kami mengundang Bapak/Ibu {nama_ortu} selaku orang tua/wali untuk hadir di sekolah pada:\n\nHari/Tanggal : {tanggal_acara}\nWaktu        : {waktu_acara}\nTempat       : Ruang Kesiswaan/BK {nama_sekolah}\nKeperluan    : Membahas perkembangan dan permasalahan ananda di sekolah\n\n$footer",
            ],
            [
                'kode_jenis' => 'SPr',
                'nama_jenis' => 'Surat Peringatan Siswa',
                'kategori' => 'keluar',
                'template_isi' => "Sehubungan dengan pelanggaran tata tertib sekolah yang dilakukan oleh ananda {nama_siswa} (NIS: {nis}), siswa kelas {kelas}, dengan ini kami sampaikan Surat Peringatan kepada yang bersangkutan.\n\nKami mohon perhatian dan kerja sama Bapak/Ibu {nama_ortu} selaku orang tua/wali untuk turut membimbing ananda agar tidak mengulangi pelanggaran serupa. Apabila pelanggaran masih berlanjut, sekolah akan mengambil tindakan sesuai tata tertib yang berlaku.\n\n$footer",
            ],
            [
                'kode_jenis' => 'SKA',
                'nama_jenis' => 'Surat Keterangan Siswa Aktif',
                'kategori' => 'keluar',
                'template_isi' => "Yang bertanda tangan di bawah ini menerangkan bahwa:\n\nNama         : {nama_siswa}\nNIS/NISN     : {nis} / {nisn}\nKelas        : {kelas}\n\nadalah benar tercatat sebagai siswa aktif di {nama_sekolah} pada tahun ajaran yang sedang berjalan.\n\nSurat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.\n\n$footer",
            ],
            [
                'kode_jenis' => 'SKP',
                'nama_jenis' => 'Surat Keterangan Pindah Sekolah',
                'kategori' => 'keluar',
                'template_isi' => "Yang bertanda tangan di bawah ini menerangkan bahwa:\n\nNama         : {nama_siswa}\nNIS/NISN     : {nis} / {nisn}\nKelas        : {kelas}\nOrang Tua/Wali : {nama_ortu}\n\nbenar merupakan siswa {nama_sekolah} dan bermaksud untuk pindah ke sekolah lain terhitung sejak tanggal {tanggal}. Selama menjadi siswa di sekolah ini, yang bersangkutan berkelakuan baik dan tidak memiliki tunggakan administrasi.\n\n$footer",
            ],
            [
                'kode_jenis' => 'SKB',
                'nama_jenis' => 'Surat Keterangan Berkelakuan Baik',
                'kategori' => 'keluar',
                'template_isi' => "Yang bertanda tangan di bawah ini menerangkan bahwa:\n\nNama         : {nama_siswa}\nNIS/NISN     : {nis} / {nisn}\nKelas        : {kelas}\n\nselama menjadi siswa di {nama_sekolah}, yang bersangkutan menunjukkan sikap dan perilaku yang baik serta tidak pernah terlibat pelanggaran berat terhadap tata tertib sekolah.\n\nSurat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.\n\n$footer",
            ],
            [
                'kode_jenis' => 'SU',
                'nama_jenis' => 'Surat Undangan Orang Tua/Wali',
                'kategori' => 'keluar',
                'template_isi' => "Dengan hormat, kami mengundang Bapak/Ibu {nama_ortu}, orang tua/wali dari ananda {nama_siswa} kelas {kelas}, untuk hadir pada kegiatan sekolah yang akan dilaksanakan pada:\n\nHari/Tanggal : {tanggal_acara}\nWaktu        : {waktu_acara}\nTempat       : {nama_sekolah}\n\n$footer",
            ],
            [
                'kode_jenis' => 'SI',
                'nama_jenis' => 'Surat Izin Tidak Masuk Sekolah',
                'kategori' => 'internal',
                'template_isi' => "Sehubungan dengan permohonan izin yang disampaikan oleh Bapak/Ibu {nama_ortu}, dengan ini kami menyetujui bahwa ananda {nama_siswa} (NIS: {nis}), siswa kelas {kelas}, diizinkan tidak mengikuti kegiatan belajar mengajar pada tanggal {tanggal_acara} dengan keperluan yang telah disampaikan.\n\nMohon ananda tetap mengejar ketertinggalan materi pelajaran selama tidak masuk sekolah.\n\n$footer",
            ],
            [
                'kode_jenis' => 'SD',
                'nama_jenis' => 'Surat Dispensasi Kegiatan',
                'kategori' => 'keluar',
                'template_isi' => "Sehubungan dengan keikutsertaan ananda {nama_siswa} (NIS: {nis}), siswa kelas {kelas}, dalam kegiatan di luar sekolah, dengan ini kami memberikan dispensasi/izin kepada yang bersangkutan untuk tidak mengikuti kegiatan belajar mengajar pada tanggal {tanggal_acara}.\n\nKami mohon Bapak/Ibu {nama_ortu} berkenan memantau agar ananda tetap mengejar ketertinggalan materi pelajaran.\n\n$footer",
            ],
            [
                'kode_jenis' => 'SR',
                'nama_jenis' => 'Surat Rekomendasi/Pengantar',
                'kategori' => 'keluar',
                'template_isi' => "Yang bertanda tangan di bawah ini memberikan rekomendasi/surat pengantar untuk:\n\nNama         : {nama_siswa}\nNIS/NISN     : {nis} / {nisn}\nKelas        : {kelas}\n\nSelama menjadi siswa di {nama_sekolah}, yang bersangkutan menunjukkan sikap, prestasi, dan perilaku yang baik. Surat ini dibuat sebagai bahan pertimbangan sesuai keperluan yang bersangkutan.\n\n$footer",
            ],
            [
                'kode_jenis' => 'SKL',
                'nama_jenis' => 'Surat Keterangan Lulus',
                'kategori' => 'keluar',
                'template_isi' => "Yang bertanda tangan di bawah ini menerangkan bahwa:\n\nNama         : {nama_siswa}\nNIS/NISN     : {nis} / {nisn}\nKelas        : {kelas}\n\ndinyatakan LULUS dari {nama_sekolah} pada tahun ajaran yang bersangkutan, dan berhak untuk melanjutkan pendidikan ke jenjang berikutnya.\n\nSurat keterangan ini berlaku sampai ijazah asli diterbitkan.\n\n$footer",
            ],
        ];
    }
}
