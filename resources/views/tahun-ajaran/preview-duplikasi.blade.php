@extends('layouts.app')
@section('title', 'Preview Salin Data')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="card p-6">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Preview Salin Data</p>
        <p class="text-lg font-bold text-slate-800 mb-1">
            {{ $sumber->labelPeriode() }} <span class="text-slate-400">&rarr;</span> {{ $tujuan->labelPeriode() }}
        </p>
        <p class="text-sm text-slate-500">
            Belum ada perubahan yang tersimpan. Periksa daftar di bawah, lalu klik
            "Salin Sekarang" untuk benar-benar menyimpan, atau "Batal" untuk kembali.
        </p>
    </div>

    {{-- Kelas & Wali Kelas --}}
    <div class="card p-6">
        <p class="font-bold text-slate-800 mb-3">Kelas &amp; Wali Kelas</p>

        @if($sumber->nama === $tujuan->nama)
            <p class="text-sm text-slate-500">
                {{ $sumber->nama }} sama dengan {{ $tujuan->nama }} (hanya beda semester) — kelas & wali kelasnya
                memang sama, tidak perlu disalin.
            </p>
        @else
            <div class="grid sm:grid-cols-2 gap-3 mb-4 text-sm">
                <div class="rounded-lg bg-emerald-50 text-emerald-700 px-3 py-2">
                    <span class="font-bold">{{ count($rencana['kelas']['disalin']) }}</span> kelas akan dibuat
                </div>
                <div class="rounded-lg bg-slate-100 text-slate-500 px-3 py-2">
                    <span class="font-bold">{{ count($rencana['kelas']['sudah_ada']) }}</span> kelas sudah ada di tujuan (dilewati)
                </div>
            </div>

            @if(count($rencana['kelas']['disalin']) > 0)
            <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Akan dibuat di {{ $tujuan->nama }}:</p>
            <ol class="text-sm text-slate-700 list-decimal list-inside space-y-0.5 mb-2 max-h-48 overflow-y-auto">
                @foreach($rencana['kelas']['disalin'] as $baris)
                    <li>{{ $baris['sumber']->nama_kelas }} (Tingkat {{ $baris['sumber']->tingkat }}) — Wali Kelas: {{ $baris['sumber']->waliKelas->name ?? '(belum diatur)' }}</li>
                @endforeach
            </ol>
            <p class="text-xs text-amber-600 mb-2">
                ⚠️ Catatan: Kelas & Wali Kelas disalin sebagai titik awal untuk {{ $tujuan->nama }} — silakan
                sesuaikan lagi di menu Data Kelas kalau ada perubahan wali kelas untuk periode ini.
            </p>
            @endif

            @if(count($rencana['kelas']['disalin']) === 0 && count($rencana['kelas']['sudah_ada']) === 0)
            <p class="text-sm text-slate-400">Tidak ada kelas di {{ $sumber->nama }} untuk disalin.</p>
            @endif
        @endif
    </div>

    {{-- Guru Mengajar --}}
    <div class="card p-6">
        <p class="font-bold text-slate-800 mb-3">Mapping Guru Mengajar</p>

        <div class="grid sm:grid-cols-2 gap-3 mb-4 text-sm">
            <div class="rounded-lg bg-emerald-50 text-emerald-700 px-3 py-2">
                <span class="font-bold">{{ count($rencana['mengajar']['disalin']) }}</span> akan disalin
            </div>
            <div class="rounded-lg bg-slate-100 text-slate-500 px-3 py-2">
                <span class="font-bold">{{ count($rencana['mengajar']['sudah_ada']) }}</span> sudah ada di tujuan (dilewati)
            </div>
        </div>

        @if(count($rencana['mengajar']['disalin']) > 0)
        <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Akan disalin:</p>
        <ol class="text-sm text-slate-700 list-decimal list-inside space-y-0.5 mb-2 max-h-48 overflow-y-auto">
            @foreach($rencana['mengajar']['disalin'] as $baris)
                <li>
                    {{ $baris['sumber']->guru->name ?? '-' }} — {{ $baris['sumber']->mapel->nama_mapel ?? '-' }} — {{ $baris['kelas_tujuan']->nama_kelas }}
                    @if($baris['kelas_baru']) <span class="text-xs text-amber-600">(kelas baru)</span> @endif
                </li>
            @endforeach
        </ol>
        @endif

        @if(count($rencana['mengajar']['disalin']) === 0 && count($rencana['mengajar']['sudah_ada']) === 0)
        <p class="text-sm text-slate-400">Tidak ada mapping guru mengajar di {{ $sumber->nama }} {{ $sumber->semester }}.</p>
        @endif
    </div>

    {{-- Jadwal --}}
    <div class="card p-6">
        <p class="font-bold text-slate-800 mb-3">Jadwal Pelajaran</p>

        <div class="grid sm:grid-cols-2 gap-3 mb-4 text-sm">
            <div class="rounded-lg bg-emerald-50 text-emerald-700 px-3 py-2">
                <span class="font-bold">{{ count($rencana['jadwal']['disalin']) }}</span> akan disalin
            </div>
            <div class="rounded-lg bg-slate-100 text-slate-500 px-3 py-2">
                <span class="font-bold">{{ count($rencana['jadwal']['sudah_ada']) }}</span> sudah ada di tujuan (dilewati)
            </div>
        </div>

        @if(count($rencana['jadwal']['disalin']) > 0)
        <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Akan disalin ({{ count($rencana['jadwal']['disalin']) }} jadwal):</p>
        <ol class="text-sm text-slate-700 list-decimal list-inside space-y-0.5 mb-2 max-h-48 overflow-y-auto">
            @foreach($rencana['jadwal']['disalin'] as $baris)
                <li>
                    {{ $baris['sumber']->hari }}, {{ $baris['sumber']->jamPelajaran->label ?? '-' }} — {{ $baris['sumber']->mapel->nama_mapel ?? '-' }} — {{ $baris['kelas_tujuan']->nama_kelas }} ({{ $baris['sumber']->guru->name ?? '-' }})
                    @if($baris['kelas_baru']) <span class="text-xs text-amber-600">(kelas baru)</span> @endif
                </li>
            @endforeach
        </ol>
        @endif

        @if(count($rencana['jadwal']['disalin']) === 0 && count($rencana['jadwal']['sudah_ada']) === 0)
        <p class="text-sm text-slate-400">Tidak ada jadwal di {{ $sumber->nama }} {{ $sumber->semester }}.</p>
        @endif
    </div>

    @php
        $totalDisalin = count($rencana['kelas']['disalin']) + count($rencana['mengajar']['disalin']) + count($rencana['jadwal']['disalin']);
    @endphp
    <div class="flex justify-end gap-2">
        <a href="{{ route('tahun-ajaran.index') }}" class="btn-outline">Batal</a>
        <form method="POST" action="{{ route('tahun-ajaran.duplikasi', $tujuan) }}">
            @csrf
            <input type="hidden" name="dari_tahun_ajaran_id" value="{{ $sumber->id }}">
            <button type="submit" class="btn-primary" {{ $totalDisalin === 0 ? 'disabled' : '' }}>
                Salin Sekarang
            </button>
        </form>
    </div>
</div>
@endsection
