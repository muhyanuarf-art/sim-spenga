@extends('layouts.app')
@section('title', 'Dashboard Kesiswaan')

@section('content')
<div class="space-y-6">
    @if(!$tahunAjaran)
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ Belum ada Tahun Ajaran aktif.
        </div>
    @endif

    <div class="grid grid-cols-2 gap-4 max-w-md">
        <x-stat-card color="brand" icon="🎓" label="Total Siswa Aktif" :value="$totalSiswa" />
        <x-stat-card color="rose" icon="🚩" label="Alfa Hari Ini" :value="$siswaAlfaHariIni->count()" />
    </div>

    <x-alfa-widget :data="$siswaAlfaHariIni" />
</div>
@endsection
