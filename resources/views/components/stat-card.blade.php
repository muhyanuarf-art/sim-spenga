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

{{--
    PERUBAHAN TAMPILAN — isi, prop, dan pemanggilnya tidak berubah sedikit
    pun. Yang dilepas hanya tiga hiasan:

    1. Kotak ikon penuh warna + bayangan berwarna. Delapan kartu
       bersebelahan berarti delapan kotak menyala sekaligus — yang paling
       terang jadi yang paling dilihat, padahal warnanya ditentukan oleh
       kategori, bukan oleh mana yang penting. Sekarang ikonnya tetap
       berwarna sesuai kategorinya, hanya dengan latar muda.
    2. Bulatan kabur di sudut kanan bawah. Di balik angka, ia cuma kabut.
    3. HURUF BESAR SEMUA pada label. Dengan angka setinggi ini di
       bawahnya, label tidak perlu ikut berteriak.

    Warnanya sendiri tetap milik pemanggil — kartu yang dulu hijau tetap
    hijau, yang biru tetap biru.
--}}
<div {{ $attributes->merge(['class' => 'card p-5 relative '.($href ? 'card-hover' : '')]) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-[13px] font-semibold text-slate-500 leading-snug">{{ $label }}</p>
            <p class="text-2xl lg:text-[1.75rem] font-extrabold text-slate-800 leading-tight mt-1.5 tracking-tight">
                {{ $value }}
                @if($suffix)<span class="text-sm font-semibold text-slate-400">{{ $suffix }}</span>@endif
            </p>
            @if($hint)<p class="text-xs text-slate-400 mt-1">{{ $hint }}</p>@endif
        </div>
        <div class="w-9 h-9 rounded-lg bg-{{ $color }}-50 text-{{ $color }}-600 flex items-center justify-center text-base shrink-0">
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
</div>
