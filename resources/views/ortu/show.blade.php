@extends('layouts.app')
@section('title', 'Profil Anak — ' . $siswa->nama)

@section('content')
@php
    $namaBulan = \Carbon\Carbon::create($tahun, $bulan, 1)->translatedFormat('F Y');
    $statusBoxClass = match ($ringkasan['status']) {
        'Normal' => 'bg-emerald-50 text-emerald-700 border border-emerald-100',
        'Dalam Pembinaan' => 'bg-amber-50 text-amber-700 border border-amber-100',
        'Selesai' => 'bg-teal-50 text-teal-700 border border-teal-100',
        default => 'bg-sky-50 text-sky-700 border border-sky-100',
    };
@endphp
<div class="space-y-6" x-data="{ tab: 'absensi' }">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <x-initial-avatar :nama="$siswa->nama" size="w-12 h-12 text-lg" />
            <div>
                <p class="text-xl font-extrabold text-slate-800">{{ $siswa->nama }}</p>
                <div class="flex items-center gap-2 text-sm text-slate-400">
                    <span>{{ $siswa->nis }}</span> &middot; <x-kelas-badge :nama="$siswa->kelas->nama_kelas ?? '-'" />
                </div>
            </div>
        </div>
        @if($anakList->count() > 1)
        <select onchange="window.location = this.value" class="input max-w-[220px]">
            @foreach($anakList as $anak)
                <option value="{{ route('ortu.show', $anak) }}" {{ $anak->id === $siswa->id ? 'selected' : '' }}>{{ $anak->nama }}</option>
            @endforeach
        </select>
        @endif
    </div>

    {{-- Ringkasan poin pelanggaran --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card color="rose" icon="⚖️" label="Poin Pelanggaran Aktif" :value="$ringkasan['poin_aktif']" />
        <x-stat-card color="sky" icon="📁" label="Jumlah Kasus" :value="$ringkasan['jumlah_kasus']" />
        <x-stat-card color="amber" icon="🌡️" label="Alfa Bulan Ini" :value="$alfa" />
        <x-stat-card color="emerald" icon="✅" label="Hadir Bulan Ini" :value="$hadir" />
    </div>

    <div class="rounded-xl px-4 py-3 text-sm {{ $statusBoxClass }}">
        Status pembinaan: <b>{{ $ringkasan['status'] }}</b>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-2 border-b border-slate-200">
        <button @click="tab = 'absensi'" :class="tab === 'absensi' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-400'" class="px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px">📅 Absensi</button>
        <button @click="tab = 'pelanggaran'" :class="tab === 'pelanggaran' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-400'" class="px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px">⚖️ Pelanggaran & Pembinaan</button>
    </div>

    {{-- ===== TAB ABSENSI ===== --}}
    <div x-show="tab === 'absensi'" class="space-y-4">
        <div class="card p-5">
            <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                <p class="font-bold text-slate-800">Rekap Absensi — {{ $namaBulan }}</p>
                <form method="GET" class="flex gap-2">
                    <select name="bulan" onchange="this.form.submit()" class="input">
                        @foreach(range(1,12) as $b)
                            <option value="{{ $b }}" {{ $b == $bulan ? 'selected' : '' }}>{{ \Carbon\Carbon::create(2000, $b, 1)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                    <select name="tahun" onchange="this.form.submit()" class="input">
                        @foreach(range(now()->year - 1, now()->year) as $t)
                            <option value="{{ $t }}" {{ $t == $tahun ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="grid grid-cols-3 gap-3 mb-5">
                <div class="rounded-xl bg-amber-50 border border-amber-100 py-3 text-center">
                    <p class="text-2xl font-extrabold text-amber-700">{{ $sakit }}</p><p class="text-xs text-amber-600 font-semibold">Sakit</p>
                </div>
                <div class="rounded-xl bg-sky-50 border border-sky-100 py-3 text-center">
                    <p class="text-2xl font-extrabold text-sky-700">{{ $izin }}</p><p class="text-xs text-sky-600 font-semibold">Izin</p>
                </div>
                <div class="rounded-xl bg-rose-50 border border-rose-100 py-3 text-center">
                    <p class="text-2xl font-extrabold text-rose-700">{{ $alfa }}</p><p class="text-xs text-rose-600 font-semibold">Alfa (tanpa keterangan)</p>
                </div>
            </div>

            @php $adaCatatan = collect($harian)->filter()->isNotEmpty(); @endphp
            @if(!$adaCatatan)
                <p class="text-sm text-slate-400 py-6 text-center">Tidak ada catatan Sakit/Izin/Alfa pada bulan ini — semua hari hadir.</p>
            @else
            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead><tr><th>Tanggal</th><th>Status</th><th>Mapel</th><th>Keterangan</th></tr></thead>
                    <tbody>
                        @foreach($harian as $tgl => $data)
                            @continue(!$data)
                            <tr>
                                <td class="font-medium">{{ $tgl }} {{ $namaBulan }}</td>
                                <td>
                                    @php
                                        $badgeClass = match($data['status']) {
                                            'Sakit' => 'bg-amber-50 text-amber-700',
                                            'Izin' => 'bg-sky-50 text-sky-700',
                                            'Alfa' => 'bg-rose-50 text-rose-700',
                                            default => 'bg-slate-100 text-slate-500',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $data['status'] }}</span>
                                </td>
                                <td>{{ $data['mapel'] }}</td>
                                <td class="text-slate-500">{{ $data['keterangan'] ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- ===== TAB PELANGGARAN ===== --}}
    <div x-show="tab === 'pelanggaran'" x-cloak class="card p-5">
        <p class="font-bold text-slate-800 mb-1">Riwayat Pelanggaran & Pembinaan</p>
        <p class="text-xs text-slate-400 mb-4">Diurutkan dari catatan paling baru.</p>

        @if($timeline->isEmpty())
            <p class="text-sm text-slate-400 py-8 text-center">Belum ada catatan pelanggaran. 🎉</p>
        @else
        <div class="space-y-3">
            @foreach($timeline as $item)
            @php $d = $item['data']; @endphp
            <div class="flex gap-3 border-l-2 pl-4 pb-3
                {{ $item['jenis'] === 'kasus' ? 'border-rose-200' : ($item['jenis'] === 'pembinaan' ? 'border-violet-200' : 'border-sky-200') }}">
                <div class="flex-1">
                    <p class="text-xs text-slate-400">{{ $item['tanggal']->translatedFormat('d F Y') }}</p>

                    @if($item['jenis'] === 'kasus')
                        <p class="font-semibold text-slate-800">
                            {{ $d->nama_pelanggaran }}
                            <span class="badge bg-rose-50 text-rose-700 ml-1">+{{ $d->poin }} poin</span>
                        </p>
                        <p class="text-sm text-slate-500">Kategori {{ $d->kategori }} &middot; Status: {{ $d->status }}</p>
                        @if($d->kronologi)<p class="text-xs text-slate-400 mt-0.5">{{ $d->kronologi }}</p>@endif

                    @elseif($item['jenis'] === 'pembinaan')
                        <p class="font-semibold text-slate-800">
                            {{ $d->jenis_pembinaan }}
                            <span class="badge bg-violet-50 text-violet-700 ml-1">Tahap {{ $d->tahap }}</span>
                        </p>
                        @if($d->hasil_pembinaan)<p class="text-sm text-slate-500 italic">Hasil: {{ $d->hasil_pembinaan }}</p>@endif
                        <p class="text-xs text-slate-400 mt-1">Petugas: {{ $d->petugas->name ?? '-' }} &middot; Status: {{ $d->status }}</p>

                    @else {{-- pemanggilan --}}
                        <p class="font-semibold text-slate-800">Pemanggilan Orang Tua</p>
                        <p class="text-sm text-slate-500">{{ $d->alasan }}</p>
                        <p class="text-xs text-slate-400 mt-1">
                            {{ $d->ortu_hadir ? '✅ Sudah dihadiri' : '⏳ Belum dihadiri' }}
                            @if($d->hasil_pertemuan) &middot; Hasil: {{ $d->hasil_pertemuan }} @endif
                        </p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
