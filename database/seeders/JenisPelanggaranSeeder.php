<?php

namespace Database\Seeders;

use App\Models\JenisPelanggaran;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Master data pelanggaran — lengkap per kategori (Ringan/Sedang/Berat/Sangat
 * Berat), poin mengikuti rentang yang sudah ditetapkan (App\Services\PoinSiswaService::RENTANG_KATEGORI):
 *   Ringan       : 5-15
 *   Sedang       : 16-50
 *   Berat        : 51-75
 *   Sangat Berat : 76-100
 *
 * Aman dijalankan berulang kali (pakai updateOrCreate berdasarkan kode) —
 * tidak akan membuat data dobel.
 *
 * Jalankan dengan: php artisan db:seed --class=JenisPelanggaranSeeder
 */
class JenisPelanggaranSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach ($this->data() as $row) {
                JenisPelanggaran::updateOrCreate(
                    ['kode' => $row[0]],
                    ['nama' => $row[1], 'kategori' => $row[2], 'poin_default' => $row[3], 'is_active' => true]
                );
            }
        });
    }

    /** [kode, nama, kategori, poin_default] */
    private function data(): array
    {
        return [
            // ==================== RINGAN (5-15 poin) ====================
            ['R001', 'Terlambat masuk sekolah/kelas', 'Ringan', 5],
            ['R002', 'Atribut seragam tidak lengkap (dasi, topi, badge, lokasi, dll)', 'Ringan', 5],
            ['R003', 'Tidak membawa buku/alat pelajaran', 'Ringan', 5],
            ['R004', 'Gaduh/ribut di kelas saat KBM berlangsung', 'Ringan', 5],
            ['R005', 'Tidak melaksanakan piket kelas sesuai jadwal', 'Ringan', 5],
            ['R006', 'Makan/minum saat pelajaran berlangsung', 'Ringan', 5],
            ['R007', 'Tidak mengumpulkan tugas tepat waktu', 'Ringan', 8],
            ['R008', 'Tidak mengikuti upacara bendera tanpa keterangan', 'Ringan', 8],
            ['R009', 'Rambut tidak rapi/tidak sesuai aturan sekolah', 'Ringan', 8],
            ['R010', 'Kuku panjang dan/atau dicat', 'Ringan', 5],
            ['R011', 'Memakai aksesoris berlebihan (gelang, kalung, dll)', 'Ringan', 5],
            ['R012', 'Tidak membawa buku penghubung/agenda siswa', 'Ringan', 5],
            ['R013', 'Tidur di kelas saat KBM berlangsung', 'Ringan', 8],
            ['R014', 'Membuang sampah tidak pada tempatnya', 'Ringan', 8],
            ['R015', 'Sepatu/kaos kaki/seragam tidak sesuai ketentuan', 'Ringan', 8],
            ['R016', 'Menggunakan HP tanpa izin saat KBM berlangsung', 'Ringan', 10],
            ['R017', 'Berada di luar kelas saat jam pelajaran tanpa keterangan jelas', 'Ringan', 10],
            ['R018', 'Tidak menjaga kebersihan kelas/lingkungan sekolah', 'Ringan', 8],
            ['R019', 'Corat-coret pada meja, kursi, atau dinding', 'Ringan', 12],
            ['R020', 'Bercanda berlebihan hingga mengganggu jalannya KBM', 'Ringan', 10],
            ['R021', 'Berkata tidak sopan kepada teman (taraf ringan)', 'Ringan', 10],
            ['R022', 'Terlambat mengikuti kegiatan sekolah (ekskul, upacara, apel)', 'Ringan', 8],
            ['R023', 'Membawa mainan/benda yang tidak berkaitan dengan pelajaran', 'Ringan', 8],
            ['R024', 'Tidak masuk sekolah tanpa surat keterangan (1 hari)', 'Ringan', 10],
            ['R025', 'Parkir kendaraan tidak pada tempat yang ditentukan', 'Ringan', 8],
            ['R026', 'Menggunakan aksesoris rambut/dandanan berlebihan', 'Ringan', 5],
            ['R027', 'Tidak memperhatikan guru saat KBM (bermain sendiri)', 'Ringan', 8],

            // ==================== SEDANG (16-50 poin) ====================
            ['S001', 'Membolos sekolah atau mata pelajaran tertentu', 'Sedang', 20],
            ['S002', 'Tidak mengerjakan tugas secara berulang kali', 'Sedang', 20],
            ['S003', 'Berkata kasar/tidak sopan kepada teman', 'Sedang', 20],
            ['S004', 'Merokok/menggunakan vape di lingkungan sekolah', 'Sedang', 30],
            ['S005', 'Keluar kelas/lingkungan sekolah tanpa izin saat jam pelajaran', 'Sedang', 20],
            ['S006', 'Mencontek saat ulangan/ujian (perorangan)', 'Sedang', 25],
            ['S007', 'Menyalahgunakan HP untuk konten/game tidak pantas saat sekolah', 'Sedang', 25],
            ['S008', 'Berpacaran secara terbuka di lingkungan sekolah', 'Sedang', 25],
            ['S009', 'Mengejek/merundung teman secara verbal berulang kali', 'Sedang', 30],
            ['S010', 'Memalsukan tanda tangan orang tua/guru pada surat izin', 'Sedang', 30],
            ['S011', 'Terlibat keributan/dorong-dorongan antar siswa', 'Sedang', 25],
            ['S012', 'Tidak mengikuti kegiatan sekolah wajib tanpa alasan yang jelas', 'Sedang', 20],
            ['S013', 'Merusak fasilitas sekolah karena kelalaian', 'Sedang', 20],
            ['S014', 'Membawa rokok/vape ke sekolah (belum digunakan)', 'Sedang', 25],
            ['S015', 'Melawan/membantah guru dengan nada tidak sopan', 'Sedang', 30],
            ['S016', 'Adu argumen/berkata kasar secara terbuka dengan teman', 'Sedang', 25],
            ['S017', 'Menyebarkan gosip/fitnah kepada/tentang teman', 'Sedang', 25],
            ['S018', 'Mengambil barang milik teman tanpa izin (belum dikategorikan mencuri)', 'Sedang', 30],
            ['S019', 'Menyebarkan konten media sosial yang mencemarkan nama baik teman/sekolah', 'Sedang', 35],
            ['S020', 'Berbohong kepada guru terkait suatu pelanggaran', 'Sedang', 20],
            ['S021', 'Kabur dari kegiatan sekolah/study tour tanpa izin', 'Sedang', 30],
            ['S022', 'Vandalisme ringan pada fasilitas sekolah (grafiti, coretan permanen)', 'Sedang', 25],
            ['S023', 'Membawa benda tajam kecil tanpa izin guru (mis. cutter, gunting besar)', 'Sedang', 25],
            ['S024', 'Memancing/memprovokasi perkelahian', 'Sedang', 30],
            ['S025', 'Tidak masuk sekolah tanpa keterangan berulang (3 hari akumulatif)', 'Sedang', 30],

            // ==================== BERAT (51-75 poin) ====================
            ['B001', 'Mencontek secara massal/kerja sama saat ujian', 'Berat', 55],
            ['B002', 'Berkelahi dengan kontak fisik antar siswa', 'Berat', 60],
            ['B003', 'Perundungan/bullying fisik atau yang dilakukan berulang', 'Berat', 60],
            ['B004', 'Memalsukan surat/dokumen resmi sekolah (izin, rapor, dll)', 'Berat', 55],
            ['B005', 'Merusak fasilitas sekolah dengan sengaja', 'Berat', 60],
            ['B006', 'Mencuri barang milik teman/sekolah (nilai kecil)', 'Berat', 65],
            ['B007', 'Melakukan ujaran/pelecehan verbal bernuansa SARA', 'Berat', 65],
            ['B008', 'Membawa/menyalakan petasan atau benda berbahaya di sekolah', 'Berat', 55],
            ['B009', 'Membawa/mengonsumsi minuman keras di area sekolah', 'Berat', 70],
            ['B010', 'Melawan guru/tenaga kependidikan secara fisik (mendorong, dsb)', 'Berat', 70],
            ['B011', 'Terlibat perundungan siber (cyberbullying) terhadap teman', 'Berat', 60],
            ['B012', 'Merokok berulang kali meskipun sudah mendapat pembinaan', 'Berat', 55],
            ['B013', 'Membawa senjata tajam tanpa indikasi niat kekerasan', 'Berat', 60],
            ['B014', 'Melakukan intimidasi/pemerasan ringan terhadap teman', 'Berat', 65],

            // ==================== SANGAT BERAT (76-100 poin) ====================
            ['SB001', 'Membawa dan/atau mengonsumsi minuman keras/narkoba', 'Sangat Berat', 90],
            ['SB002', 'Membawa senjata tajam dengan indikasi niat kekerasan', 'Sangat Berat', 85],
            ['SB003', 'Melakukan tindak asusila/pelecehan seksual', 'Sangat Berat', 100],
            ['SB004', 'Melakukan pencurian dengan nilai besar/direncanakan', 'Sangat Berat', 85],
            ['SB005', 'Terlibat tawuran antar sekolah/kelompok', 'Sangat Berat', 90],
            ['SB006', 'Melakukan tindak kekerasan berat/penganiayaan serius', 'Sangat Berat', 90],
            ['SB007', 'Mengedarkan dan/atau menjual narkoba/zat terlarang', 'Sangat Berat', 100],
            ['SB008', 'Membawa senjata api atau bahan peledak', 'Sangat Berat', 100],
            ['SB009', 'Perundungan yang menyebabkan korban cedera serius', 'Sangat Berat', 90],
            ['SB010', 'Melakukan pemerasan disertai ancaman kekerasan', 'Sangat Berat', 85],
            ['SB011', 'Terlibat tindak kriminal di luar sekolah yang mencoreng nama baik sekolah', 'Sangat Berat', 85],
            ['SB012', 'Memprovokasi tawuran/kerusuhan massal', 'Sangat Berat', 90],
        ];
    }
}
