/** @type {import('tailwindcss').Config} */

/**
 * PERBAIKAN — beberapa komponen (stat-card, aksi-cepat, kelas-badge,
 * mapel-badge, initial-avatar, dashboard) menyusun nama class warna SECARA
 * DINAMIS dari variabel PHP, contoh:  <div class="bg-{{ $color }}-500">
 * Tailwind memindai file sumber sebagai teks mentah (ia tidak menjalankan
 * PHP/Blade), sehingga class seperti itu tidak pernah cocok dengan class
 * literal apa pun dan akan dibuang saat build — akibatnya warna/ikon
 * terlihat "hilang".
 *
 * Dulu masalah ini diatasi dengan safelist berpola regex yang juga
 * mencakup varian opacity (\/\d{1,3}) — akibatnya Tailwind membangkitkan
 * RIBUAN class yang tidak pernah dipakai dan file CSS hasil build
 * membengkak sampai ~1,9 MB (ikut memperlambat setiap halaman).
 *
 * Sekarang safelist dibuat EKSPLISIT: hanya kombinasi warna + utility yang
 * benar-benar dipakai secara dinamis di aplikasi ini. Hasilnya sama-sama
 * aman, tapi ukuran CSS turun drastis.
 */
const warnaDinamis = [
    'brand', 'blue', 'indigo', 'violet', 'fuchsia', 'sky', 'cyan', 'teal',
    'emerald', 'lime', 'amber', 'orange', 'rose', 'red', 'slate',
];

const safelist = warnaDinamis.flatMap((c) => [
    `bg-${c}-50`, `bg-${c}-100`, `bg-${c}-500`, `bg-${c}-100/70`, `bg-${c}-50/60`,
    `text-${c}-500`, `text-${c}-600`, `text-${c}-700`,
    `border-${c}-100`, `border-${c}-200`, `border-${c}-300`,
    `shadow-${c}-500/30`,
]);

export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/**/*.php',
    ],
    safelist,
    theme: {
        extend: {
            fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
            colors: {
                brand: {
                    50: '#eef7ff', 100: '#d9ecff', 200: '#bcdfff', 300: '#8ecbff',
                    400: '#59acff', 500: '#3388fd', 600: '#1c68f2', 700: '#1553de',
                    800: '#1844b3', 900: '#193c8c',
                },

                /*
                 * AKSEN — oranye pendamping biru.
                 *
                 * Keluarga warna BARU, bukan pengganti. Tidak satu pun nilai
                 * "brand" di atas berubah, jadi semua tombol, tautan, dan
                 * status yang sudah ada tetap persis seperti semula.
                 *
                 * Tugasnya hanya satu: menandai. Garis pendek di bawah judul
                 * halaman, penanda menu yang sedang dibuka, titik pada
                 * pemilih periode. Biru tetap memegang semua yang bisa
                 * diklik — oranye tidak pernah dipakai untuk tombol.
                 *
                 * Nadanya sengaja oranye-amber (#f5820f), bukan oranye
                 * kemerahan. Oranye yang terlalu merah akan tertukar dengan
                 * tombol Hapus (#e0392f) — justru di tempat yang paling tidak
                 * boleh salah baca.
                 */
                aksen: {
                    50: '#fff7ec', 100: '#ffecd1', 200: '#fed5a0', 300: '#fcb864',
                    400: '#f99a35', 500: '#f5820f', 600: '#e06f08', 700: '#b9550c',
                    800: '#934411', 900: '#783911',
                },
            },
            boxShadow: { soft: '0 2px 10px 0 rgba(20, 30, 60, 0.06)' },
        },
    },
    plugins: [],
};
