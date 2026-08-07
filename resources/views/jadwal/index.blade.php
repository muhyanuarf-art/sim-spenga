@extends('layouts.app')
@section('title', 'Jadwal Pelajaran')

@section('content')
<div class="space-y-6" x-data="{ showForm: false }">

    <div class="card p-5">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Pilih Kelas</label>
                <select name="kelas_id" class="input" onchange="this.form.submit()">
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id }}" {{ $kelas && $kelas->id === $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('jadwal.import.form') }}" class="btn-outline">📥 Import Excel</a>
            <button type="button" @click="showForm = !showForm" class="btn-primary">+ Tambah Jadwal</button>
        </form>
    </div>

    @if(!$tahunAjaran)
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ Aktifkan Tahun Ajaran terlebih dahulu.
        </div>
    @endif

    <div class="card p-5" x-show="showForm" x-cloak x-transition x-data="{ hari: '{{ $hariList[0] }}', jamPerHari: @js($jamPerHari) }">
        <p class="font-bold text-slate-800 mb-1">Tambah Jadwal - Kelas {{ $kelas->nama_kelas ?? '-' }}</p>
        <p class="text-xs text-slate-400 mb-4">Pilihan Jam Ke akan otomatis menyesuaikan dengan Hari yang dipilih, karena jam pelajaran sekarang diatur per hari.</p>
        <form method="POST" action="{{ route('jadwal.store') }}" class="grid sm:grid-cols-5 gap-3 items-end">
            @csrf
            <input type="hidden" name="kelas_id" value="{{ $kelas->id ?? '' }}">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Hari</label>
                <select name="hari" x-model="hari" required class="input">
                    @foreach($hariList as $h)<option value="{{ $h }}">{{ $h }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Jam Ke</label>
                <select name="jam_pelajaran_id" required class="input" x-show="(jamPerHari[hari] || []).length">
                    <template x-for="j in (jamPerHari[hari] || [])" :key="j.id">
                        <option :value="j.id" x-text="j.label"></option>
                    </template>
                </select>
                <p class="text-xs text-red-500 mt-1" x-show="!(jamPerHari[hari] || []).length">Belum ada jam pelajaran untuk hari ini.</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Mapel</label>
                <select name="mata_pelajaran_id" required class="input">
                    @foreach($mapelList as $m)<option value="{{ $m->id }}">{{ $m->nama_mapel }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Guru</label>
                <select name="guru_id" required class="input">
                    @foreach($guruList as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($hariList as $h)
        <div class="card p-5">
            <p class="font-bold text-slate-800 mb-3">{{ $h }}</p>
            <div class="space-y-2">
                @forelse(($jadwal[$h] ?? collect())->sortBy('jamPelajaran.jam_ke') as $j)
                <div x-data="{ editing: false, hari: '{{ $j->hari }}', jamPerHari: @js($jamPerHari) }">
                    <div x-show="!editing" class="flex items-center justify-between bg-slate-50 rounded-lg px-3 py-2">
                        <div>
                            <p class="text-xs font-bold text-brand-600">{{ $j->jamPelajaran->label }}</p>
                            <p class="text-sm font-semibold text-slate-700">{{ $j->mapel->nama_mapel }}</p>
                            <p class="text-xs text-slate-400">{{ $j->guru->name }}</p>
                        </div>
                        <div class="action-buttons">
                            <button type="button" @click="editing = true" class="btn-chip btn-chip-edit btn-chip-icon" title="Edit">✏️</button>
                            <form method="POST" action="{{ route('jadwal.destroy', $j) }}" onsubmit="return confirm('Hapus jadwal ini?')">
                                @csrf @method('DELETE')
                                <button class="btn-chip btn-chip-delete btn-chip-icon" title="Hapus">🗑️</button>
                            </form>
                        </div>
                    </div>

                    <div x-show="editing" x-cloak class="bg-brand-50/40 rounded-lg p-3 border border-brand-100">
                        <form method="POST" action="{{ route('jadwal.update', $j) }}" class="space-y-2">
                            @csrf @method('PUT')
                            <select name="hari" x-model="hari" required class="input text-xs">
                                @foreach($hariList as $hh)<option value="{{ $hh }}">{{ $hh }}</option>@endforeach
                            </select>
                            <select name="jam_pelajaran_id" required class="input text-xs" x-show="(jamPerHari[hari] || []).length">
                                <template x-for="jm in (jamPerHari[hari] || [])" :key="jm.id">
                                    <option :value="jm.id" x-text="jm.label" :selected="jm.id === {{ $j->jam_pelajaran_id }}"></option>
                                </template>
                            </select>
                            <p class="text-xs text-red-500" x-show="!(jamPerHari[hari] || []).length">Belum ada jam pelajaran untuk hari ini.</p>
                            <select name="mata_pelajaran_id" required class="input text-xs">
                                @foreach($mapelList as $m)
                                    <option value="{{ $m->id }}" {{ $j->mata_pelajaran_id == $m->id ? 'selected' : '' }}>{{ $m->nama_mapel }}</option>
                                @endforeach
                            </select>
                            <select name="guru_id" required class="input text-xs">
                                @foreach($guruList as $g)
                                    <option value="{{ $g->id }}" {{ $j->guru_id == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                                @endforeach
                            </select>
                            <div class="flex gap-2">
                                <button type="submit" class="btn-primary h-[32px] text-xs flex-1">Simpan</button>
                                <button type="button" @click="editing = false" class="btn-outline h-[32px] text-xs flex-1">Batal</button>
                            </div>
                        </form>
                    </div>
                </div>
                @empty
                <p class="text-xs text-slate-400 py-2">Tidak ada jadwal.</p>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection
