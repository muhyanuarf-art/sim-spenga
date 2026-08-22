{{--
    Item sub menu, dipakai di dalam <x-nav-group>.
    Props:
      - href   : URL tujuan
      - active : true jika sub menu ini sedang aktif
--}}
@props(['href', 'active' => false])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'nav-sublink'.($active ? ' nav-sublink-active' : '')]) }}>
    <span class="nav-dot"></span>
    <span>{{ $slot }}</span>
</a>
