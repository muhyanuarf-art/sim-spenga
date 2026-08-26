{{--
    Sidebar aplikasi. TIDAK ADA lagi daftar menu yang ditulis manual di sini —
    seluruh isinya dibaca dari App\Support\Navigasi (satu sumber kebenaran),
    jadi menambah/mengubah menu cukup di satu file PHP itu saja.
--}}
@php
    $menu = \App\Support\Navigasi::untuk(auth()->user());
    $sekolah = $pengaturanSekolahGlobal ?? null;
@endphp

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="sidebar fixed z-40 inset-y-0 left-0 w-[17rem] transform transition-transform duration-200 lg:translate-x-0 lg:static lg:flex lg:flex-col"
       x-data="{ cari: '' }">

    {{-- Identitas aplikasi --}}
    <div class="h-16 flex items-center gap-3 px-5 border-b border-white/10 shrink-0">
        <div class="w-10 h-10 rounded-xl bg-white text-brand-800 flex items-center justify-center font-extrabold shadow-lg shadow-black/20">SP</div>
        <div class="min-w-0">
            <p class="font-extrabold text-white leading-tight tracking-tight">SIM-SPENGA</p>
            <p class="text-[11px] text-blue-200/70 leading-tight truncate">{{ $sekolah->nama_sekolah ?? 'Sistem Informasi Manajemen' }}</p>
        </div>
        <button @click="sidebarOpen = false" class="lg:hidden ml-auto text-blue-200/70 hover:text-white p-1">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    {{-- Pencarian menu: mempercepat pengguna yang sudah hafal nama menu,
         sekaligus menolong pengguna baru yang belum tahu menunya ada di grup mana. --}}
    <div class="px-3 pt-3 shrink-0">
        <div class="relative">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-[11px] text-blue-200/50"></i>
            <input type="search" x-model="cari" placeholder="Cari menu…"
                   class="w-full bg-white/5 border border-white/10 rounded-lg pl-8 pr-3 py-2 text-sm text-white placeholder:text-blue-200/40 focus:outline-none focus:border-white/30 focus:bg-white/10">
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-3 space-y-4 nav-scroll">
        @foreach($menu as $seksi)
            @php $cariSeksi = \Illuminate\Support\Str::lower($seksi['label'].' '.collect($seksi['item'])->pluck('cari')->implode(' ')); @endphp
            <div x-show="cari === '' || @js($cariSeksi).includes(cari.toLowerCase())">
                <p class="nav-section">{{ $seksi['label'] }}</p>

                <div class="space-y-0.5">
                    @foreach($seksi['item'] as $item)
                        @if($item['grup'])
                            <div x-data="{
                                    kunci: 'nav:{{ $item['kunci'] }}',
                                    open: {{ $item['aktif'] ? 'true' : 'false' }},
                                    init() {
                                        const simpan = localStorage.getItem(this.kunci);
                                        if (simpan !== null) { this.open = this.open || simpan === '1'; }
                                        this.$watch('open', v => localStorage.setItem(this.kunci, v ? '1' : '0'));
                                    }
                                 }"
                                 x-show="cari === '' || @js($item['cari']).includes(cari.toLowerCase())">
                                <button type="button" @click="open = !open"
                                        class="nav-link nav-link-group {{ $item['aktif'] ? 'nav-parent-active' : '' }}"
                                        :aria-expanded="open.toString()">
                                    <span class="nav-icon"><i class="fa-solid {{ $item['icon'] }}"></i></span>
                                    <span class="flex-1 text-left truncate">{{ $item['label'] }}</span>
                                    <i class="fa-solid fa-chevron-down nav-chevron" :class="(open || cari !== '') ? 'rotate-180' : ''"></i>
                                </button>

                                <div x-show="open || cari !== ''" x-collapse x-cloak class="nav-sub">
                                    @foreach($item['anak'] as $anak)
                                        <a href="{{ $anak['url'] }}"
                                           x-show="cari === '' || @js($anak['cari']).includes(cari.toLowerCase()) || @js($item['cari']).includes(cari.toLowerCase())"
                                           class="nav-sublink {{ $anak['aktif'] ? 'nav-sublink-active' : '' }}">
                                            <span class="nav-dot"></span>
                                            <span class="truncate">{{ $anak['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <a href="{{ $item['url'] }}"
                               x-show="cari === '' || @js($item['cari']).includes(cari.toLowerCase())"
                               class="nav-link {{ $item['aktif'] ? 'nav-active' : '' }}">
                                <span class="nav-icon"><i class="fa-solid {{ $item['icon'] }}"></i></span>
                                <span class="truncate">{{ $item['label'] }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach

        @php $semuaTeksCari = collect($menu)->flatMap(fn ($s) => collect($s['item'])->pluck('cari'))->values(); @endphp
        <p class="text-center text-xs text-blue-200/60 py-4"
           x-show="cari !== '' && !@js($semuaTeksCari).some(s => s.includes(cari.toLowerCase()))" x-cloak>
            Menu "<span x-text="cari"></span>" tidak ditemukan.
        </p>
    </nav>

    <div class="p-3 border-t border-white/10 shrink-0">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold text-rose-200 hover:bg-rose-500/15 hover:text-white transition">
                <span class="nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span> Keluar
            </button>
        </form>
    </div>
</aside>
