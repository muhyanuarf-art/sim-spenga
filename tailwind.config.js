/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/**/*.php',
    ],
    // PERBAIKAN — "icon/warna tidak muncul di semua dashboard".
    // Akar masalahnya BUKAN Font Awesome, tapi Tailwind: banyak komponen
    // (stat-card, kelas-badge, mapel-badge, initial-avatar, dashboard
    // admin/guru, dashboard orang tua) MEMBUAT NAMA CLASS WARNA SECARA
    // DINAMIS dari variabel PHP, contoh:
    //   <div class="bg-{{ $color }}-500 text-{{ $color }}-600">
    // Tailwind men-scan file SUMBER sebagai teks mentah untuk tahu class
    // apa saja yang perlu di-build — ia TIDAK menjalankan PHP/Blade, jadi
    // "bg-{{ $color }}-500" tidak pernah cocok dengan class literal
    // apa pun ("bg-emerald-500", "bg-amber-500", dst) dan SEMUANYA
    // dibuang saat build. Akibatnya kontainer icon jadi tanpa warna latar
    // (mis. teks putih di atas latar putih) — kelihatan seperti "icon
    // hilang", padahal Font Awesome-nya sendiri baik-baik saja.
    //
    // safelist di bawah memaksa Tailwind TETAP membuat seluruh kombinasi
    // warna+tingkat kegelapan yang dipakai lewat variabel di seluruh app
    // (daftar warna: lihat $palet di kelas-badge/mapel-badge/initial-
    // avatar/dashboard guru.blade.php, + $warna di orangtua/dashboard,
    // + $color di stat-card/dashboard admin), termasuk varian opacity
    // (mis. "bg-emerald-200/40") yang juga dipakai di beberapa tempat.
    safelist: [
        {
            pattern: /(bg|text|border|from|to|ring|shadow)-(indigo|amber|teal|emerald|rose|violet|sky|fuchsia|cyan|lime|red|slate)-(50|100|200|300|400|500|600|700|800|900)(\/\d{1,3})?/,
        },
    ],
    theme: {
        extend: {
            fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
            colors: {
                brand: {
                    50: '#eef7ff', 100: '#d9ecff', 200: '#bcdfff', 300: '#8ecbff',
                    400: '#59acff', 500: '#3388fd', 600: '#1c68f2', 700: '#1553de',
                    800: '#1844b3', 900: '#193c8c',
                },
            },
            boxShadow: { soft: '0 2px 10px 0 rgba(20, 30, 60, 0.06)' },
        },
    },
    plugins: [],
};
