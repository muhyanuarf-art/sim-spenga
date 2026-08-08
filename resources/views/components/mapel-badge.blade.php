@props(['nama'])
@php
    $palet = ['indigo', 'emerald', 'amber', 'sky', 'rose', 'teal', 'violet', 'fuchsia', 'cyan', 'lime'];
    $warna = $palet[crc32($nama ?? '?') % count($palet)];
@endphp
<span class="badge bg-{{ $warna }}-50 text-{{ $warna }}-700">{{ $nama ?? '-' }}</span>
