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
        <p class="text-xs text-slate-400 -mt-3 mb-4">Jam yang berurutan untuk kelas & mapel yang sama otomatis digabung jadi 1 sesi — cukup isi absensi & jurnal 1x.</p>

        @if(!$tahunAjaran)
            <p class="text-sm text-amber-600">Tidak ada Tahun Ajaran aktif.</p>
        @elseif($sesiList->isEmpty())
            <p class="text-sm text-slate-400 py-8 text-center">Tidak ada jadwal mengajar pada hari {{ $hari }}.</p>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($sesiList as $sesi)
                <a href="{{ route('mengajar.form', $sesi['ids']) }}"
                   class="border rounded-xl p-4 transition block {{ ($sesi['sudah_diisi'] ?? false) ? 'border-emerald-200 bg-emerald-50/60' : 'border-slate-200 hover:border-brand-400 hover:bg-brand-50/40' }}">
                    <div class="flex items-center justify-between mb-1 gap-2">
                        <p class="text-xs font-bold text-brand-600">
                            @if($sesi['jam_awal']->id === $sesi['jam_akhir']->id)
                                {{ $sesi['jam_awal']->label }}
                            @else
                                Jam ke-{{ $sesi['jam_awal']->jam_ke }} s.d ke-{{ $sesi['jam_akhir']->jam_ke }}
                                ({{ substr($sesi['jam_awal']->jam_mulai, 0, 5) }} - {{ substr($sesi['jam_akhir']->jam_selesai, 0, 5) }})
                            @endif
                        </p>
                        @if($sesi['sudah_diisi'] ?? false)
                            <span class="badge bg-emerald-100 text-emerald-700 shrink-0">Terisi</span>
                        @endif
                    </div>
                    <p class="font-semibold text-slate-800">Kelas {{ $sesi['kelas']->nama_kelas }}</p>
                    <p class="text-sm text-slate-500">{{ $sesi['mapel']->nama_mapel }}</p>
                    @if($sesi['slots']->count() > 1)
                        <p class="text-xs text-slate-400 mt-1">{{ $sesi['slots']->count() }} jam pelajaran &middot; 1x isi absensi</p>
                    @endif
                </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
