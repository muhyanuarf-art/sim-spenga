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
 * Cetak HANYA 1 bagian tertentu di halaman (dipakai kalau halaman punya
 * lebih dari 1 "print-section", mis. Rekapitulasi punya Rekap Guru &
 * Rekap Kelas terpisah). Elemen lain yang juga ber-class "print-section"
 * otomatis disembunyikan sementara selama proses cetak.
 */
window.cetakBagian = function (idElemen) {
    document.querySelectorAll('.print-section').forEach(function (el) {
        el.classList.remove('print-target-selected');
    });
    var target = document.getElementById(idElemen);
    if (target) {
        target.classList.add('print-target-selected');
    }
    document.body.classList.add('print-target-active');
    window.print();
};
window.addEventListener('afterprint', function () {
    document.body.classList.remove('print-target-active');
});
