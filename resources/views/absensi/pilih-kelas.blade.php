@extends('layouts.app')
@section('title', 'Absensi & Jurnal Mengajar')

@section('content')
<div class="space-y-6">
    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-3">Pilih Hari</p>
        <div class="flex flex-wrap gap-2">
            @foreach($hariList as $h)
                <a href="{{ route('mengajar.index', ['hari' => $h]) }}"
                   class="px-4 py-2 rounded-lg text-sm font-semibold border {{ $h === $hari ? 'bg-brand-600 text-white border-brand-600' : 'border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                    {{ $h }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-4">Jadwal Mengajar - {{ $hari }}</p>

        @if(!$tahunAjaran)
            <p class="text-sm text-amber-600">Tidak ada Tahun Ajaran aktif.</p>
        @elseif($jadwal->isEmpty())
            <p class="text-sm text-slate-400 py-8 text-center">Tidak ada jadwal mengajar pada hari {{ $hari }}.</p>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($jadwal as $j)
                <a href="{{ route('mengajar.form', $j) }}"
                   class="border rounded-xl p-4 transition block {{ ($j->sudah_diisi ?? false) ? 'border-emerald-200 bg-emerald-50/60' : 'border-slate-200 hover:border-brand-400 hover:bg-brand-50/40' }}">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-xs font-bold text-brand-600">{{ $j->jamPelajaran->label }}</p>
                        @if($j->sudah_diisi ?? false)
                            <span class="badge bg-emerald-100 text-emerald-700">Terisi</span>
                        @endif
                    </div>
                    <p class="font-semibold text-slate-800">Kelas {{ $j->kelas->nama_kelas }}</p>
                    <p class="text-sm text-slate-500">{{ $j->mapel->nama_mapel }}</p>
                </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
