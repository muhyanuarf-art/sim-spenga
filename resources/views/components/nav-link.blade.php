{{--
    Item menu sidebar level-1 tanpa sub menu (mis. Dashboard, Rekapitulasi).
    Props:
      - href   : URL tujuan
      - icon   : nama icon Font Awesome, mis. "fa-house"
      - active : true jika menu ini sedang aktif
--}}
@props(['href', 'icon', 'active' => false])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'nav-link'.($active ? ' nav-active' : '')]) }}>
    <span class="nav-icon"><i class="fa-solid {{ $icon }}"></i></span>
    <span>{{ $slot }}</span>
</a>
