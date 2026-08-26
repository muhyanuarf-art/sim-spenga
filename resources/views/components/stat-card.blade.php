{{--
    Kartu statistik ringkas. Dipakai di seluruh dashboard & beberapa halaman
    laporan. Props lama (color/icon/label/value/suffix) tetap didukung supaya
    halaman yang sudah memakainya tidak perlu diubah.

    Props tambahan:
      - hint : keterangan kecil di bawah angka
      - href : kalau diisi, seluruh kartu menjadi tautan
--}}
@props([
    'color' => 'brand',
    'icon' => 'fa-chart-column',
    'label',
    'value',
    'suffix' => null,
    'hint' => null,
    'href' => null,
])

<div {{ $attributes->merge(['class' => 'card p-5 relative overflow-hidden '.($href ? 'card-hover' : '')]) }}>
    <div class="relative z-10 flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">{{ $label }}</p>
            <p class="text-2xl lg:text-[1.75rem] font-extrabold text-slate-800 leading-tight mt-1">
                {{ $value }}
                @if($suffix)<span class="text-sm font-semibold text-slate-400">{{ $suffix }}</span>@endif
            </p>
            @if($hint)<p class="text-xs text-slate-400 mt-1">{{ $hint }}</p>@endif
        </div>
        <div class="w-11 h-11 rounded-xl bg-{{ $color }}-500 text-white flex items-center justify-center text-lg shrink-0 shadow-lg shadow-{{ $color }}-500/30">
            <i class="fa-solid {{ $icon }}"></i>
        </div>
    </div>

    @if($href)
        <p class="relative z-10 text-xs font-semibold text-brand-600 mt-3">
            Lihat detail <i class="fa-solid fa-arrow-right-long ml-0.5 text-[10px]"></i>
        </p>
        {{-- Tautan menutupi seluruh kartu supaya area kliknya luas (mudah
             ditekan di layar HP), tanpa perlu mengubah tag pembungkusnya. --}}
        <a href="{{ $href }}" class="absolute inset-0 z-20" aria-label="{{ $label }}"></a>
    @endif

    <div class="absolute -right-8 -bottom-10 w-28 h-28 rounded-full bg-{{ $color }}-100/70 blur-2xl pointer-events-none"></div>
</div>
