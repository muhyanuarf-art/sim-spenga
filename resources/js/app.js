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

/* =========================================================================
   PENILAIAN — pratinjau perhitungan saat guru mengetik.

   PENTING: yang BERWENANG menghitung nilai tetap server, yaitu
   App\Support\SkemaPenilaian (dipanggil ulang setiap kali daftar nilai
   disimpan). Kode di bawah ini HANYA cermin dari rumus itu supaya guru
   bisa langsung melihat RT, nilai akhir, dan predikat berubah tanpa harus
   menyimpan dulu — bukan sumber kebenaran. Kalau rumus di SkemaPenilaian
   diubah, ubah juga di sini (dan sebaliknya) supaya angka pratinjau tidak
   berbeda dengan angka yang akhirnya tersimpan.

   Didefinisikan SEBELUM Alpine.start() karena dipakai langsung di dalam
   x-data — Alpine mengevaluasi x-data saat start, jadi fungsinya harus
   sudah ada saat itu.
   ========================================================================= */

/** Ubah isi kotak input menjadi angka; kosong/bukan angka => null (belum dinilai). */
function angkaNilai(nilai) {
    if (nilai === null || nilai === undefined || nilai === '') return null;
    const angka = parseFloat(nilai);
    return Number.isNaN(angka) ? null : angka;
}

function bulatkanDua(angka) {
    return Math.round(angka * 100) / 100;
}

function rataRataNilai(daftar) {
    const ada = daftar.filter((n) => n !== null);
    if (ada.length === 0) return null;
    return bulatkanDua(ada.reduce((a, b) => a + b, 0) / ada.length);
}

/**
 * Satu baris siswa di lembar Daftar Nilai. Dipakai lewat:
 *   <tr x-data="barisNilai(dataAwal, skemaNilai)">
 *
 * dataAwal : { formatif: {1: 80, ...}, lm: {1: {sum: 70, rem: 75}, ...}, asts, asas }
 * skema    : lihat variabel $skemaJs di resources/views/nilai/daftar-nilai.blade.php
 */
window.barisNilai = function (dataAwal, skema) {
    return {
        skema: skema,
        formatif: dataAwal.formatif || {},
        lm: dataAwal.lm || {},
        asts: dataAwal.asts,
        asas: dataAwal.asas,

        /** Rata-rata TPF yang SUDAH terisi (kolom kosong tidak dianggap nol). */
        get rataFormatif() {
            return rataRataNilai(Object.values(this.formatif).map(angkaNilai));
        },

        /**
         * Satu lingkup materi diringkas dari sepasang kolom (SUM, REM)
         * menjadi SATU nilai — penjelasan lengkap tiap kebijakan ada di
         * docblock App\Support\SkemaPenilaian.
         */
        nilaiLm(nomor) {
            const baris = this.lm[nomor] || {};
            const sum = angkaNilai(baris.sum);
            const rem = angkaNilai(baris.rem);

            if (sum === null && rem === null) return null;
            if (sum === null) {
                return this.skema.kebijakan === 'batas_kktp' ? Math.min(rem, this.skema.kktpMin) : rem;
            }
            if (rem === null) return sum;

            if (this.skema.kebijakan === 'tertinggi') return Math.max(sum, rem);
            if (this.skema.kebijakan === 'rata_rata') return bulatkanDua((sum + rem) / 2);
            return Math.max(sum, Math.min(rem, this.skema.kktpMin));
        },

        /** Apakah kolom REM lingkup materi ini wajib diisi (SUM di bawah KKTP). */
        wajibRemedi(nomor) {
            const sum = angkaNilai((this.lm[nomor] || {}).sum);
            return sum !== null && sum < this.skema.kktpMin;
        },

        get rataSumatifLm() {
            const nomor = Array.from({ length: this.skema.jumlahLm }, (_, i) => i + 1);
            return rataRataNilai(nomor.map((n) => this.nilaiLm(n)));
        },

        /** Komponen berbobot 60% = gabungan RT Formatif & RT Sumatif Lingkup Materi. */
        get nilaiKomponen60() {
            const f = this.rataFormatif;
            const s = this.rataSumatifLm;
            if (f === null && s === null) return null;
            if (f === null) return s;
            if (s === null) return f;

            const total = this.skema.komposisiFormatif + this.skema.komposisiSumatifLm;
            if (total <= 0) return bulatkanDua((f + s) / 2);
            return bulatkanDua((f * this.skema.komposisiFormatif + s * this.skema.komposisiSumatifLm) / total);
        },

        /**
         * Nilai akhir dibagi HANYA dengan bobot komponen yang sudah ada
         * nilainya — supaya nilai sementara di tengah semester tidak
         * terlihat jatuh hanya karena ASTS/ASAS memang belum waktunya
         * diisi. Lihat alasan lengkapnya di SkemaPenilaian.
         */
        get nilaiAkhir() {
            const komponen = [
                [this.nilaiKomponen60, this.skema.bobot60],
                [angkaNilai(this.asts), this.skema.bobotAsts],
                [angkaNilai(this.asas), this.skema.bobotAsas],
            ];

            let jumlah = 0;
            let bobot = 0;
            komponen.forEach(([nilai, b]) => {
                if (nilai !== null && b > 0) {
                    jumlah += nilai * b;
                    bobot += b;
                }
            });

            return bobot > 0 ? bulatkanDua(jumlah / bobot) : null;
        },

        /** Sudah lengkap = tiap komponen yang punya bobot sudah ada nilainya. */
        get lengkap() {
            return [
                [this.nilaiKomponen60, this.skema.bobot60],
                [angkaNilai(this.asts), this.skema.bobotAsts],
                [angkaNilai(this.asas), this.skema.bobotAsas],
            ].every(([nilai, b]) => b === 0 || nilai !== null);
        },

        get nilaiRapor() {
            const na = this.nilaiAkhir;
            return na === null ? null : Math.round(na);
        },

        /**
         * Predikat & status tuntas mengikuti nilai yang BENAR-BENAR
         * TERTULIS di rapor (nilaiRapor, sudah dibulatkan) — bukan angka
         * desimalnya. Lihat alasannya di App\Support\SkemaPenilaian::hitung().
         */
        get predikat() {
            const na = this.nilaiRapor;
            if (na === null) return null;
            if (na < this.skema.kktpMin) return 'D';
            if (na <= this.skema.kktpMax) return 'C';
            return na >= this.skema.kktpMax + (100 - this.skema.kktpMax) / 2 ? 'A' : 'B';
        },

        get tuntas() {
            const na = this.nilaiRapor;
            return na === null ? null : na >= this.skema.kktpMin;
        },

        get warnaPredikat() {
            return {
                A: 'bg-emerald-50 text-emerald-700',
                B: 'bg-sky-50 text-sky-700',
                C: 'bg-amber-50 text-amber-700',
                D: 'bg-rose-50 text-rose-700',
            }[this.predikat] || 'bg-slate-100 text-slate-400';
        },

        /** Tampilkan angka apa adanya, atau tanda hubung kalau belum ada. */
        tampil(angka) {
            return angka === null || angka === undefined ? '–' : angka;
        },
    };
};

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
