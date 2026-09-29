{{--
    PAGINATION MILIK APLIKASI INI.

    =====================================================================
    KENAPA HARUS DITULIS SENDIRI
    =====================================================================
    Sebelumnya dipakai view bawaan Laravel (pagination::tailwind). Fungsinya
    benar — tautan halamannya ada dan bisa diklik — tetapi TAMPIL POLOS
    tanpa satu pun gaya.

    Sebabnya bukan salah tulis: view bawaan itu berada di dalam folder
    `vendor/laravel/framework`, sedangkan Tailwind hanya memindai
    `resources/` (lihat content di tailwind.config.js). Kelas seperti
    `ring-gray-300` dan `dark:bg-gray-800` yang dipakainya karena itu tidak
    pernah ikut dibangun ke dalam CSS. Halamannya "berhasil", hanya saja
    tidak ada rupanya — persis jenis kegagalan yang tidak memunculkan galat
    apa pun.

    Dengan file ini, markup-nya berada di `resources/` sehingga kelasnya
    ikut terbangun, dan sekaligus memakai kosakata gaya aplikasi sendiri
    (warna brand, sudut membulat, ukuran tombol yang sama dengan .btn-*).

    =====================================================================
    UKURAN DAN BAHASA
    =====================================================================
    Sasaran tombolnya sengaja besar (40px) dan hurufnya tidak dikecilkan.
    Penggunanya guru sampai usia 60 tahun, dan ini elemen yang ditekan
    berulang kali. Seluruh teksnya berbahasa Indonesia — bawaan Laravel
    mencampur "Sebelumnya" dengan "Showing ... to ... of ... results".

    Dipakai oleh SELURUH halaman berdaftar panjang (Kelola Pengguna, Data
    Siswa, Surat, Prestasi, dan 11 lainnya) — cukup satu berkas.
--}}

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman"
         class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

        {{-- Keterangan jumlah: "Menampilkan 1–25 dari 31" --}}
        <p class="text-sm text-slate-500 order-2 sm:order-1">
            Menampilkan
            <span class="font-semibold text-slate-700">{{ $paginator->firstItem() }}</span>
            &ndash;
            <span class="font-semibold text-slate-700">{{ $paginator->lastItem() }}</span>
            dari
            <span class="font-semibold text-slate-700">{{ $paginator->total() }}</span>
            data
            <span class="text-slate-400">&middot; halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>
        </p>

        <div class="flex items-center gap-1.5 order-1 sm:order-2 flex-wrap">

            {{-- Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <span class="h-10 px-3.5 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 text-slate-300 text-sm font-semibold cursor-not-allowed">
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                    <span class="hidden sm:inline">Sebelumnya</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="h-10 px-3.5 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-slate-50 hover:border-slate-300 hover:text-slate-800 transition"
                   aria-label="Halaman sebelumnya">
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                    <span class="hidden sm:inline">Sebelumnya</span>
                </a>
            @endif

            {{-- Nomor halaman. Disembunyikan di layar sempit: di ponsel
                 tombol Sebelumnya/Berikutnya sudah cukup, dan deretan
                 nomor justru membuat barisnya pecah ke bawah. --}}
            <div class="hidden md:flex items-center gap-1.5">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="h-10 w-10 inline-flex items-center justify-center text-slate-400 text-sm">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page"
                                      class="h-10 min-w-10 px-2 inline-flex items-center justify-center rounded-xl bg-brand-600 text-white text-sm font-bold shadow-sm">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}"
                                   class="h-10 min-w-10 px-2 inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-brand-50 hover:border-brand-200 hover:text-brand-700 transition"
                                   aria-label="Halaman {{ $page }}">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Berikutnya --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="h-10 px-3.5 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white text-slate-600 text-sm font-semibold hover:bg-slate-50 hover:border-slate-300 hover:text-slate-800 transition"
                   aria-label="Halaman berikutnya">
                    <span class="hidden sm:inline">Berikutnya</span>
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </a>
            @else
                <span class="h-10 px-3.5 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 text-slate-300 text-sm font-semibold cursor-not-allowed">
                    <span class="hidden sm:inline">Berikutnya</span>
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </span>
            @endif

        </div>
    </nav>
@endif
