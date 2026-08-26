{{--
    Tombol "Aksi Cepat" di dashboard: satu klik menuju pekerjaan yang paling
    sering dilakukan role tersebut, supaya pengguna tidak perlu menelusuri
    sidebar lebih dulu.
--}}
@props(['icon' => 'fa-arrow-right', 'label', 'deskripsi' => null, 'href', 'color' => 'brand'])

<a href="{{ $href }}" class="card card-hover p-4 flex items-start gap-3 group">
    <div class="w-10 h-10 rounded-xl bg-{{ $color }}-50 text-{{ $color }}-600 flex items-center justify-center text-base shrink-0">
        <i class="fa-solid {{ $icon }}"></i>
    </div>
    <div class="min-w-0 flex-1">
        <p class="font-semibold text-slate-800 text-sm leading-tight group-hover:text-brand-700 transition">{{ $label }}</p>
        @if($deskripsi)<p class="text-xs text-slate-400 mt-0.5 leading-snug">{{ $deskripsi }}</p>@endif
    </div>
    <i class="fa-solid fa-chevron-right text-[10px] text-slate-300 mt-1.5 group-hover:text-brand-500 transition"></i>
</a>
