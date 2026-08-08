@props(['nama'])
@php
    $palet = ['violet', 'teal', 'amber', 'sky', 'rose', 'indigo', 'emerald', 'fuchsia'];
    $warna = $palet[crc32($nama ?? '?') % count($palet)];
@endphp
<span class="badge bg-{{ $warna }}-50 text-{{ $warna }}-700">{{ $nama ?? '-' }}</span>
