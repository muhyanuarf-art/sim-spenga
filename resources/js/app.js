import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

// Font Awesome (dulu dari cdnjs, sekarang di-bundle & di-self-host oleh Vite)
import '@fortawesome/fontawesome-free/css/all.min.css';

// Plus Jakarta Sans (dulu dari Google Fonts CDN, sekarang di-self-host)
import '@fontsource/plus-jakarta-sans/400.css';
import '@fontsource/plus-jakarta-sans/500.css';
import '@fontsource/plus-jakarta-sans/600.css';
import '@fontsource/plus-jakarta-sans/700.css';
import '@fontsource/plus-jakarta-sans/800.css';

Alpine.plugin(collapse);
window.Alpine = Alpine;
Alpine.start();

/**
 * Cetak HANYA 1 bagian tertentu di halaman — bagian yang tombol Cetak-nya
 * ditekan. Dipakai mis. di Rekapitulasi (Rekap Guru & Rekap Kelas terpisah)
 * dan di Profil Perilaku Siswa (yang dicetak hanya tabel Riwayat
 * Perkembangan, bukan kartu ringkasan/identitas/filter di sekitarnya).
 *
 * Caranya: elemen target ditandai, lalu semua elemen SEBELAHNYA di tiap
 * tingkat sampai <body> disembunyikan sementara. Jadi tidak perlu lagi
 * menandai satu per satu elemen lain dengan class "no-print".
 */
window.cetakBagian = function (idElemen) {
    var target = document.getElementById(idElemen);

    document.querySelectorAll('.print-section').forEach(function (el) {
        el.classList.remove('print-target-selected');
    });

    if (target) {
        target.classList.add('print-target-selected');

        // PERBAIKAN — dulu hanya elemen ber-class "print-section" LAIN yang
        // disembunyikan, sehingga isi halaman di luar print-section (kartu
        // ringkasan, identitas, filter, kotak status, dst) IKUT tercetak
        // kecuali halamannya rajin menandai satu per satu dengan "no-print".
        //
        // Sekarang: telusuri dari elemen target naik sampai <body>, lalu
        // sembunyikan SEMUA elemen sebelah (saudara) di tiap tingkat. Yang
        // tersisa hanya jalur menuju target — jadi yang tercetak benar-benar
        // hanya bagian yang tombol Cetak-nya ditekan, di halaman mana pun,
        // tanpa perlu menandai elemen lain satu per satu.
        var node = target;
        while (node && node.parentElement && node !== document.body) {
            Array.prototype.forEach.call(node.parentElement.children, function (saudara) {
                if (saudara !== node) {
                    saudara.classList.add('print-sembunyi');
                }
            });
            node = node.parentElement;
        }
    }

    document.body.classList.add('print-target-active');
    window.print();

    // Sebagian browser tidak membunyikan event 'afterprint' (atau tidak
    // membunyikannya saat dialog dibatalkan), jadi pembersihan juga
    // dipanggil langsung — fungsinya aman dipanggil berkali-kali.
    setTimeout(window.bersihkanTandaCetak, 500);
};

window.bersihkanTandaCetak = function () {
    document.body.classList.remove('print-target-active');
    document.querySelectorAll('.print-sembunyi').forEach(function (el) {
        el.classList.remove('print-sembunyi');
    });
};

window.addEventListener('afterprint', function () {
    window.bersihkanTandaCetak();
});
