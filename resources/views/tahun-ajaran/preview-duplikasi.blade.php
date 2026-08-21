@extends('layouts.app')
@section('title', 'Preview Salin Mapping & Jadwal')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="card p-6">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Preview Salin</p>
        <p class="text-lg font-bold text-slate-800 mb-1">
            {{ $sumber->labelPeriode() }} <span class="text-slate-400">&rarr;</span> {{ $tujuan->labelPeriode() }}
        </p>
        <p class="text-sm text-slate-500">
            Tidak ada perubahan yang tersimpan pada tahap ini. Periksa daftar di bawah, lalu klik
            "Salin Sekarang" untuk benar-benar menyimpan, atau "Batal" untuk kembali.
        </p>
    </div>

    {{-- Guru Mengajar --}}
    <div class="card p-6">
        <p class="font-bold text-slate-800 mb-3">Mapping Guru Mengajar</p>

        <div class="grid sm:grid-cols-3 gap-3 mb-4 text-sm">
            <div class="rounded-lg bg-emerald-50 text-emerald-700 px-3 py-2">
                <span class="font-bold">{{ count($rencana['mengajar']['disalin']) }}</span> akan disalin
            </div>
            <div class="rounded-lg bg-slate-100 text-slate-500 px-3 py-2">
                <span class="font-bold">{{ count($rencana['mengajar']['sudah_ada']) }}</span> sudah ada di tujuan (dilewati)
            </div>
            <div class="rounded-lg bg-amber-50 text-amber-700 px-3 py-2">
                <span class="font-bold">{{ count($rencana['mengajar']['kelas_tidak_ada']) }}</span> kelasnya belum ada di tujuan
            </div>
        </div>

        @if(count($rencana['mengajar']['disalin']) > 0)
        <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Akan disalin:</p>
        <ol class="text-sm text-slate-700 list-decimal list-inside space-y-0.5 mb-4 max-h-48 overflow-y-auto">
            @foreach($rencana['mengajar']['disalin'] as $baris)
                <li>{{ $baris['sumber']->guru->name ?? '-' }} — {{ $baris['sumber']->mapel->nama_mapel ?? '-' }} — {{ $baris['kelas_tujuan']->nama_kelas }}</li>
            @endforeach
        </ol>
        @endif

        @if(count($rencana['mengajar']['kelas_tidak_ada']) > 0)
        <p class="text-xs font-semibold text-amber-600 uppercase mb-1">Dilewati — kelas belum tersedia di {{ $tujuan->nama }}:</p>
        <ol class="text-sm text-amber-700 list-decimal list-inside space-y-0.5 mb-2 max-h-48 overflow-y-auto">
            @foreach($rencana['mengajar']['kelas_tidak_ada'] as $m)
                <li>{{ $m->guru->name ?? '-' }} — {{ $m->mapel->nama_mapel ?? '-' }} — {{ $m->kelas->nama_kelas ?? '-' }} (Tingkat {{ $m->kelas->tingkat ?? '?' }})</li>
            @endforeach
        </ol>
        <p class="text-xs text-slate-400">
            Buat dulu kelasnya di menu Data Kelas untuk Tahun Ajaran {{ $tujuan->nama }}, lalu ulangi proses salin ini
            (data yang sudah tersalin tidak akan tersalin dobel).
        </p>
        @endif

        @if(count($rencana['mengajar']['disalin']) === 0 && count($rencana['mengajar']['kelas_tidak_ada']) === 0 && count($rencana['mengajar']['sudah_ada']) === 0)
        <p class="text-sm text-slate-400">Tidak ada mapping guru mengajar di {{ $sumber->nama }} {{ $sumber->semester }}.</p>
        @endif
    </div>

    {{-- Jadwal --}}
    <div class="card p-6">
        <p class="font-bold text-slate-800 mb-3">Jadwal Pelajaran</p>

        <div class="grid sm:grid-cols-3 gap-3 mb-4 text-sm">
            <div class="rounded-lg bg-emerald-50 text-emerald-700 px-3 py-2">
                <span class="font-bold">{{ count($rencana['jadwal']['disalin']) }}</span> akan disalin
            </div>
            <div class="rounded-lg bg-slate-100 text-slate-500 px-3 py-2">
                <span class="font-bold">{{ count($rencana['jadwal']['sudah_ada']) }}</span> sudah ada di tujuan (dilewati)
            </div>
            <div class="rounded-lg bg-amber-50 text-amber-700 px-3 py-2">
                <span class="font-bold">{{ count($rencana['jadwal']['kelas_tidak_ada']) }}</span> kelasnya belum ada di tujuan
            </div>
        </div>

        @if(count($rencana['jadwal']['disalin']) > 0)
        <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Akan disalin ({{ count($rencana['jadwal']['disalin']) }} jadwal):</p>
        <ol class="text-sm text-slate-700 list-decimal list-inside space-y-0.5 mb-4 max-h-48 overflow-y-auto">
            @foreach($rencana['jadwal']['disalin'] as $baris)
                <li>{{ $baris['sumber']->hari }}, {{ $baris['sumber']->jamPelajaran->label ?? '-' }} — {{ $baris['sumber']->mapel->nama_mapel ?? '-' }} — {{ $baris['kelas_tujuan']->nama_kelas }} ({{ $baris['sumber']->guru->name ?? '-' }})</li>
            @endforeach
        </ol>
        @endif

        @if(count($rencana['jadwal']['kelas_tidak_ada']) > 0)
        <p class="text-xs font-semibold text-amber-600 uppercase mb-1">Dilewati — kelas belum tersedia di {{ $tujuan->nama }}:</p>
        <ol class="text-sm text-amber-700 list-decimal list-inside space-y-0.5 mb-2 max-h-48 overflow-y-auto">
            @foreach($rencana['jadwal']['kelas_tidak_ada'] as $j)
                <li>{{ $j->hari }}, {{ $j->jamPelajaran->label ?? '-' }} — {{ $j->kelas->nama_kelas ?? '-' }} (Tingkat {{ $j->kelas->tingkat ?? '?' }})</li>
            @endforeach
        </ol>
        @endif

        @if(count($rencana['jadwal']['disalin']) === 0 && count($rencana['jadwal']['kelas_tidak_ada']) === 0 && count($rencana['jadwal']['sudah_ada']) === 0)
        <p class="text-sm text-slate-400">Tidak ada jadwal di {{ $sumber->nama }} {{ $sumber->semester }}.</p>
        @endif
    </div>

    <div class="flex justify-end gap-2">
        <a href="{{ route('tahun-ajaran.index') }}" class="btn-outline">Batal</a>
        <form method="POST" action="{{ route('tahun-ajaran.duplikasi', $tujuan) }}">
            @csrf
            <input type="hidden" name="dari_tahun_ajaran_id" value="{{ $sumber->id }}">
            <button type="submit" class="btn-primary"
                    {{ (count($rencana['mengajar']['disalin']) === 0 && count($rencana['jadwal']['disalin']) === 0) ? 'disabled' : '' }}>
                Salin Sekarang
            </button>
        </form>
    </div>
</div>
@endsection
