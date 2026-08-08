@props(['nama', 'size' => 'w-7 h-7 text-xs'])
@php
    $palet = ['indigo', 'emerald', 'amber', 'sky', 'rose', 'teal', 'violet', 'fuchsia'];
    $warna = $palet[crc32($nama ?? '?') % count($palet)];
@endphp
<span class="{{ $size }} rounded-full bg-{{ $warna }}-100 text-{{ $warna }}-700 font-bold flex items-center justify-center shrink-0">
    {{ strtoupper(substr($nama ?? '?', 0, 1)) }}
</span>
