/* =========================================================================
   BATANG KEMAJUAN PEMBUATAN ARSIP SEMESTER.

   Membuat arsip memakan belasan detik sampai beberapa menit — merender
   belasan laporan lewat peramban satu per satu. Tanpa penanda, yang
   dilihat Admin hanyalah tulisan diam, dan ia akan menekan tombolnya
   berkali-kali karena mengira tidak terjadi apa-apa.

   Yang mengerjakan arsip adalah PEKERJA ANTRIAN — proses terpisah dari
   peramban ini. Karena itu kemajuannya tidak bisa dikirim langsung;
   halaman menanyakannya berkala ke server.
   ========================================================================= */

export function arsipProgres(id, awalPersen, awalLangkah) {
    return {
        persen: awalPersen || 0,
        langkah: awalLangkah || 'Menyiapkan…',
        selesai: false,
        gagal: null,
        urlUnduh: null,
        pewaktu: null,

        mulai() {
            this.periksa();
            // Dua detik: cukup rapat agar terasa hidup, cukup jarang agar
            // tidak membebani server yang sedang sibuk merender PDF.
            this.pewaktu = setInterval(() => this.periksa(), 2000);
        },

        hentikan() {
            if (this.pewaktu) {
                clearInterval(this.pewaktu);
                this.pewaktu = null;
            }
        },

        async periksa() {
            try {
                const jawab = await fetch(`/arsip/${id}/status`, {
                    headers: { Accept: 'application/json' },
                });

                if (!jawab.ok) return;

                const data = await jawab.json();

                this.persen = data.progres;
                this.langkah = data.langkah || this.langkah;

                if (data.status === 'gagal') {
                    this.gagal = data.catatan || 'Pembuatan arsip gagal.';
                    this.hentikan();
                    return;
                }

                if (data.selesai) {
                    this.persen = 100;
                    this.selesai = true;
                    this.urlUnduh = data.unduh;
                    this.hentikan();
                }
            } catch (e) {
                // Gangguan jaringan sesaat diabaikan — percobaan berikutnya
                // dua detik lagi. Menghentikan pemantauan karena satu
                // kegagalan justru membuat Admin mengira prosesnya mati.
            }
        },
    };
}
