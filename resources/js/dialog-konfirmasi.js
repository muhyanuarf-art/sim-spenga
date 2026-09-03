/* =========================================================================
   DIALOG KONFIRMASI MILIK APLIKASI — pengganti confirm() bawaan peramban.

   =========================================================================
   KENAPA TIDAK MEMAKAI confirm() SAJA
   =========================================================================
   confirm() tidak bisa diatur tampilannya sama sekali: hurufnya kecil,
   kotaknya menempel di tepi atas layar, dan di WebView Android bentuknya
   berbeda lagi dari peramban biasa. Guru berusia 60-an praktis tidak
   membaca isinya — ditekan "OK" begitu saja. Untuk tombol seperti "Tutup
   Semester" atau "Hapus Permanen", kekeliruan seperti itu mahal.

   Dialog ini memakai huruf besar, tombol tinggi, dan warna merah khusus
   untuk tindakan yang menghapus atau mengunci.

   =========================================================================
   CARA PAKAI DI BLADE
   =========================================================================
   Ganti  onsubmit="return confirm('Pesan')"
   dengan data-konfirmasi="Pesan"

   Atribut tambahan, semuanya opsional:
     data-konfirmasi-judul       judul di atas pesan (bawaan "Konfirmasi")
     data-konfirmasi-gaya        "bahaya" | "biasa" (bawaan: ditebak dari
                                 kata pada pesannya)
     data-konfirmasi-lanjut      teks tombol lanjut
     data-konfirmasi-isian       bila diisi, dialog menampilkan satu kotak
                                 isian berlabel ini — pengganti prompt()
     data-konfirmasi-isian-untuk nama input di dalam form yang diisi nilai
                                 kotak tersebut

   Penyadapnya dipasang di `document` pada fase capture, sehingga tetap
   bekerja pada halaman yang isinya ditukar wire:navigate.
   ========================================================================= */

const KATA_BAHAYA = ['hapus', 'keluarkan', 'batalkan', 'tutup', 'kunci', 'nonaktifkan', 'reset'];

function tebakGaya(pesan) {
    const p = String(pesan || '').toLowerCase();
    return KATA_BAHAYA.some((k) => p.includes(k)) ? 'bahaya' : 'biasa';
}

/** Pesan panjang dipecah jadi beberapa paragraf, bukan satu blok padat. */
function isiPesan(wadah, pesan) {
    wadah.textContent = '';

    String(pesan)
        .split(/\\n|\n/)
        .map((b) => b.trim())
        .filter((b) => b !== '')
        .forEach((b) => {
            const p = document.createElement('p');
            p.textContent = b;
            wadah.appendChild(p);
        });
}

/**
 * Tampilkan dialog. Mengembalikan Promise yang berisi:
 *   false   → dibatalkan
 *   true    → dilanjutkan (dialog tanpa isian)
 *   string  → dilanjutkan, berisi teks yang diketik pengguna
 */
export function konfirmasi(opsi = {}) {
    return new Promise((selesai) => {
        const gaya = opsi.gaya || tebakGaya(opsi.pesan);
        const bahaya = gaya === 'bahaya';

        const latar = document.createElement('div');
        latar.className = 'dk-latar';
        latar.setAttribute('role', 'dialog');
        latar.setAttribute('aria-modal', 'true');

        const kotak = document.createElement('div');
        kotak.className = 'dk-kotak';

        const ikon = document.createElement('div');
        ikon.className = `dk-ikon ${bahaya ? 'dk-ikon-bahaya' : 'dk-ikon-biasa'}`;
        ikon.innerHTML = `<i class="fa-solid ${bahaya ? 'fa-triangle-exclamation' : 'fa-circle-question'}"></i>`;

        const judul = document.createElement('p');
        judul.className = 'dk-judul';
        judul.textContent = opsi.judul || 'Konfirmasi';

        const pesan = document.createElement('div');
        pesan.className = 'dk-pesan';
        isiPesan(pesan, opsi.pesan || '');

        kotak.append(ikon, judul, pesan);

        let isian = null;
        if (opsi.isian) {
            const label = document.createElement('p');
            label.className = 'dk-pesan';
            label.style.marginTop = '1.1rem';
            label.style.fontWeight = '600';
            label.textContent = opsi.isian;

            isian = document.createElement('input');
            isian.type = 'text';
            isian.className = 'dk-isian';

            kotak.append(label, isian);
        }

        const baris = document.createElement('div');
        baris.className = 'dk-tombol';

        const batal = document.createElement('button');
        batal.type = 'button';
        batal.className = 'dk-btn dk-btn-batal';
        batal.textContent = 'Batal';

        const lanjut = document.createElement('button');
        lanjut.type = 'button';
        lanjut.className = `dk-btn ${bahaya ? 'dk-btn-bahaya' : 'dk-btn-lanjut'}`;
        lanjut.textContent = opsi.lanjut || (bahaya ? 'Ya, Lanjutkan' : 'Ya');

        baris.append(batal, lanjut);
        kotak.append(baris);
        latar.append(kotak);
        document.body.append(latar);

        const tutup = (hasil) => {
            latar.remove();
            document.removeEventListener('keydown', padaTombol);
            selesai(hasil);
        };

        function padaTombol(e) {
            if (e.key === 'Escape') tutup(false);
            if (e.key === 'Enter' && document.activeElement !== batal) lanjut.click();
        }

        batal.addEventListener('click', () => tutup(false));

        lanjut.addEventListener('click', () => {
            if (!isian) {
                tutup(true);
                return;
            }

            // Isian kosong diperlakukan sebagai pembatalan — sama seperti
            // perilaku prompt() yang dikosongkan pengguna sebelumnya.
            const nilai = isian.value.trim();
            tutup(nilai === '' ? false : nilai);
        });

        // Menekan latar gelap membatalkan. Perilaku yang sudah dikenali
        // pengguna, dan kebetulan pilihan amannya memang membatalkan.
        latar.addEventListener('click', (e) => {
            if (e.target === latar) tutup(false);
        });

        document.addEventListener('keydown', padaTombol);

        (isian || batal).focus();
    });
}

export function pasangPenyadapForm() {
    document.addEventListener(
        'submit',
        (e) => {
            const form = e.target;
            if (!form || form.nodeName !== 'FORM') return;

            const pesan = form.getAttribute('data-konfirmasi');
            if (!pesan) return;

            // Pengiriman KEDUA (sesudah pengguna menekan "Ya") harus
            // dibiarkan lewat, kalau tidak dialognya muncul tanpa henti.
            if (form.dataset.konfirmasiLolos === '1') return;

            e.preventDefault();
            e.stopPropagation();

            konfirmasi({
                pesan,
                judul: form.getAttribute('data-konfirmasi-judul'),
                gaya: form.getAttribute('data-konfirmasi-gaya'),
                lanjut: form.getAttribute('data-konfirmasi-lanjut'),
                isian: form.getAttribute('data-konfirmasi-isian'),
            }).then((hasil) => {
                if (hasil === false) return;

                if (typeof hasil === 'string') {
                    const nama = form.getAttribute('data-konfirmasi-isian-untuk');
                    const sasaran = nama ? form.querySelector(`[name="${nama}"]`) : null;
                    if (sasaran) sasaran.value = hasil;
                }

                form.dataset.konfirmasiLolos = '1';

                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            });
        },
        true, // fase capture: harus berjalan sebelum penangan lain
    );
}
