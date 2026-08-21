@extends('layouts.app')
@section('title', 'Jadwal Pelajaran')

@section('content')
<div class="space-y-6" x-data="{ showForm: false }">
    @php
        // STEP 6 Bagian 19/20 — sama seperti Mapping Guru Mengajar: tombol
        // tambah/edit/hapus HANYA muncul kalau yang sedang DILIHAT adalah
        // periode AKTIF dan belum terkunci.
        $bisaEdit = $periodeDilihat && $periodeAktif && $periodeDilihat->id === $periodeAktif->id && ! $periodeDilihat->isTerkunci();
    @endphp

    <div class="card p-5">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Periode</label>
                <select name="tahun_ajaran_id" class="input" onchange="this.form.submit()">
                    @foreach($tahunAjaranList as $t)
                        <option value="{{ $t->id }}" {{ $periodeDilihat && $periodeDilihat->id === $t->id ? 'selected' : '' }}>
                            {{ $t->labelPeriode() }}{{ $periodeAktif && $periodeAktif->id === $t->id ? ' (Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Pilih Kelas</label>
                <select name="kelas_id" class="input" onchange="this.form.submit()">
                    @forelse($kelasListPeriode as $k)
                        <option value="{{ $k->id }}" {{ $kelas && $kelas->id === $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @empty
                        <option value="">Belum ada kelas</option>
                    @endforelse
                </select>
            </div>
            <a href="{{ route('jadwal.import.form') }}" class="btn-outline">📥 Import Excel</a>
            @if($bisaEdit)
            <button type="button" @click="showForm = !showForm" class="btn-primary">+ Tambah Jadwal</button>
            @endif
        </form>
    </div>

    @if(!$periodeAktif)
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ Aktifkan Tahun Ajaran terlebih dahulu.
        </div>
    @elseif(!$periodeDilihat)
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ Periode tidak ditemukan.
        </div>
    @elseif($periodeDilihat->id !== $periodeAktif->id)
        <div class="rounded-xl bg-slate-100 border border-slate-200 text-slate-600 px-4 py-3 text-sm">
            📖 Anda sedang melihat histori periode {{ $periodeDilihat->labelPeriode() }} (bukan periode aktif). Jadwal ini hanya dapat dilihat, tidak dapat ditambah/diubah/dihapus dari sini.
        </div>
    @elseif($periodeDilihat->isTerkunci())
        <div class="rounded-xl bg-slate-100 border border-slate-200 text-slate-600 px-4 py-3 text-sm">
            🔒 Periode {{ $periodeDilihat->labelPeriode() }} sudah ditutup dan terkunci. Jadwal pada periode ini hanya dapat dilihat, tidak dapat ditambah/diubah/dihapus.
        </div>
    @endif

    @if($bisaEdit && $kelas && $mapelList->isEmpty())
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ Belum ada data Mapping Guru Mengajar untuk kelas {{ $kelas->nama_kelas }}. Silakan lengkapi dulu di menu <strong>Mapping Guru Mengajar</strong> sebelum menyusun jadwal.
        </div>
    @endif

    <div class="card p-5" x-show="showForm" x-cloak x-transition x-data="{
            hari: '{{ $hariList[0] }}',
            jamPerHari: @js($jamPerHari),
            mengajar: @js($mengajarMap),
            mapelId: '',
            guruId: '',
            guruOptions(mapelId) { return this.mengajar.filter(m => m.mapel_id == mapelId) }
        }">
        <p class="font-bold text-slate-800 mb-1">Tambah Jadwal - Kelas {{ $kelas->nama_kelas ?? '-' }}</p>
        <p class="text-xs text-slate-400 mb-4">Pilihan Jam Ke menyesuaikan Hari, dan pilihan Mapel &amp; Guru menyesuaikan data Mapping Guru Mengajar kelas ini.</p>
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
                <select name="mata_pelajaran_id" x-model="mapelId" @change="guruId = ''" required class="input">
                    <option value="">-- Pilih Mapel --</option>
                    @foreach($mapelList as $m)<option value="{{ $m->id }}">{{ $m->nama_mapel }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Guru</label>
                <select name="guru_id" x-model="guruId" required class="input" :disabled="!mapelId" x-show="guruOptions(mapelId).length">
                    <option value="">-- Pilih Guru --</option>
                    <template x-for="g in guruOptions(mapelId)" :key="g.guru_id">
                        <option :value="g.guru_id" x-text="g.guru_nama"></option>
                    </template>
                </select>
                <p class="text-xs text-red-500 mt-1" x-show="mapelId && !guruOptions(mapelId).length">Belum ada guru yang di-mapping mengajar mapel ini di kelas ini.</p>
                <p class="text-xs text-slate-400 mt-1" x-show="!mapelId">Pilih mapel terlebih dahulu.</p>
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
                @php
                    // Fallback: pastikan mapel & guru yang sedang terpakai di jadwal ini selalu muncul
                    // di pilihan, walau mapping-nya sudah berubah/dihapus belakangan.
                    $editMengajarMap = $mengajarMap->contains(fn ($m) => $m['guru_id'] == $j->guru_id && $m['mapel_id'] == $j->mata_pelajaran_id)
                        ? $mengajarMap
                        : $mengajarMap->concat([[
                            'mapel_id' => $j->mata_pelajaran_id,
                            'guru_id' => $j->guru_id,
                            'guru_nama' => $j->guru->name,
                        ]]);
                @endphp
                <div x-data="{
                        editing: false,
                        hari: '{{ $j->hari }}',
                        jamPerHari: @js($jamPerHari),
                        mengajar: @js($editMengajarMap),
                        mapelId: {{ $j->mata_pelajaran_id }},
                        guruId: {{ $j->guru_id }},
                        guruOptions(mapelId) { return this.mengajar.filter(m => m.mapel_id == mapelId) }
                    }">
                    <div x-show="!editing" class="flex items-center justify-between bg-slate-50 rounded-lg px-3 py-2">
                        <div>
                            <p class="text-xs font-bold text-brand-600">{{ $j->jamPelajaran->label }}</p>
                            <p class="text-sm font-semibold text-slate-700">{{ $j->mapel->nama_mapel }}</p>
                            <p class="text-xs text-slate-400">{{ $j->guru->name }}</p>
                        </div>
                        <div class="action-buttons">
                            @if(!$bisaEdit)
                                <span class="text-xs text-slate-400" title="Tidak dapat diubah">🔒</span>
                            @else
                            <button type="button" @click="editing = true" class="btn-chip btn-chip-edit btn-chip-icon" title="Edit">✏️</button>
                            <form method="POST" action="{{ route('jadwal.destroy', $j) }}" onsubmit="return confirm('Hapus jadwal ini?')">
                                @csrf @method('DELETE')
                                <button class="btn-chip btn-chip-delete btn-chip-icon" title="Hapus">🗑️</button>
                            </form>
                            @endif
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
                            <select name="mata_pelajaran_id" x-model="mapelId" @change="guruId = ''" required class="input text-xs">
                                @foreach($mapelList as $m)
                                    <option value="{{ $m->id }}">{{ $m->nama_mapel }}</option>
                                @endforeach
                                @if(!$mapelList->contains('id', $j->mata_pelajaran_id))
                                    <option value="{{ $j->mata_pelajaran_id }}">{{ $j->mapel->nama_mapel }} (tidak lagi di-mapping)</option>
                                @endif
                            </select>
                            <select name="guru_id" x-model="guruId" required class="input text-xs" :disabled="!mapelId" x-show="guruOptions(mapelId).length">
                                <template x-for="g in guruOptions(mapelId)" :key="g.guru_id">
                                    <option :value="g.guru_id" x-text="g.guru_nama"></option>
                                </template>
                            </select>
                            <p class="text-xs text-red-500" x-show="!guruOptions(mapelId).length">Belum ada guru yang di-mapping mengajar mapel ini di kelas ini.</p>
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
