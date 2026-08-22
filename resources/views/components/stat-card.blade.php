@props(['color' => 'brand', 'icon' => 'fa-chart-column', 'label', 'value', 'suffix' => null])

<div class="relative overflow-hidden rounded-2xl border border-{{ $color }}-100 bg-gradient-to-br from-{{ $color }}-50 to-white p-5">
    <div class="relative z-10 flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs font-bold text-{{ $color }}-600 uppercase tracking-wide mb-1">{{ $label }}</p>
            <p class="text-2xl lg:text-3xl font-extrabold text-slate-800 truncate">
                {{ $value }}
                @if($suffix)<span class="text-sm font-semibold text-slate-400">{{ $suffix }}</span>@endif
            </p>
        </div>
        <div class="w-11 h-11 rounded-xl bg-{{ $color }}-500 text-white flex items-center justify-center text-lg shrink-0 shadow-lg shadow-{{ $color }}-500/30">
            <i class="fa-solid {{ $icon }}"></i>
        </div>
    </div>
    <div class="absolute -right-6 -bottom-8 w-28 h-28 rounded-full bg-{{ $color }}-200/40 blur-2xl pointer-events-none"></div>
</div>
