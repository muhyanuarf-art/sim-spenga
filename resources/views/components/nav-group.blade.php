{{--
    Grup menu sidebar yang bisa dibuka/ditutup (accordion), berisi Menu -> Sub Menu.
    Props:
      - icon   : nama icon Font Awesome untuk menu induk, mis. "fa-triangle-exclamation"
      - label  : judul menu induk
      - active : true jika salah satu sub menu di dalamnya sedang aktif
                 (menu otomatis terbuka saat halaman dimuat & diberi highlight)
    Slot berisi daftar <x-nav-sublink> di dalamnya.
--}}
@props(['icon', 'label', 'active' => false])

<div x-data="{ open: @js($active) }" class="nav-group">
    <button type="button" @click="open = !open"
            class="nav-group-btn{{ $active ? ' nav-group-btn-active' : '' }}"
            :aria-expanded="open.toString()">
        <span class="nav-icon"><i class="fa-solid {{ $icon }}"></i></span>
        <span class="flex-1 text-left">{{ $label }}</span>
        <i class="fa-solid fa-chevron-down nav-chevron" :class="open ? 'rotate-180' : ''"></i>
    </button>
    <div x-show="open" x-collapse x-cloak class="nav-sub">
        {{ $slot }}
    </div>
</div>
