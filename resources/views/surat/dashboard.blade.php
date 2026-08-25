@extends('layouts.app')
@section('title', 'Dashboard Manajemen Surat')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-start flex-wrap gap-3">
        <div>
            <p class="text-xl font-extrabold text-slate-800">Dashboard Manajemen Surat</p>
            <p class="text-sm text-slate-500">Ringkasan aktivitas surat{{ $pengaturanSekolahGlobal->nama_sekolah ? ' di ' . $pengaturanSekolahGlobal->nama_sekolah : '' }}.</p>
        </div>
        <a href="{{ route('surat.create') }}" class="btn-primary">+ Buat Surat Baru</a>
    </div>

    {{-- 5 kartu ringkasan --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="card p-5">
            <div class="w-11 h-11 rounded-full bg-blue-50 flex items-center justify-center mb-3">
                <i class="fa-solid fa-inbox text-blue-600"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">Surat Masuk</p>
            <p class="text-3xl font-extrabold text-slate-800 mt-1">{{ $ringkasan['masuk'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Menunggu disposisi: <span class="font-semibold text-slate-600">{{ $ringkasan['masuk_menunggu_disposisi'] }}</span></p>
        </div>
        <div class="card p-5">
            <div class="w-11 h-11 rounded-full bg-emerald-50 flex items-center justify-center mb-3">
                <i class="fa-solid fa-paper-plane text-emerald-600"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">Surat Keluar</p>
            <p class="text-3xl font-extrabold text-slate-800 mt-1">{{ $ringkasan['keluar'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Bulan ini: <span class="font-semibold text-slate-600">{{ $ringkasan['keluar_bulan_ini'] }}</span></p>
        </div>
        <div class="card p-5">
            <div class="w-11 h-11 rounded-full bg-amber-50 flex items-center justify-center mb-3">
                <i class="fa-solid fa-hourglass-half text-amber-600"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">Disposisi Aktif</p>
            <p class="text-3xl font-extrabold text-slate-800 mt-1">{{ $ringkasan['disposisi_aktif'] }}</p>
            <p class="text-xs text-amber-600 mt-1">Menunggu tindak lanjut</p>
        </div>
        <div class="card p-5">
            <div class="w-11 h-11 rounded-full bg-violet-50 flex items-center justify-center mb-3">
                <i class="fa-solid fa-circle-check text-violet-600"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">Surat Selesai</p>
            <p class="text-3xl font-extrabold text-slate-800 mt-1">{{ $ringkasan['selesai'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Bulan ini: <span class="font-semibold text-slate-600">{{ $ringkasan['selesai_bulan_ini'] }}</span></p>
        </div>
        <div class="card p-5">
            <div class="w-11 h-11 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                <i class="fa-solid fa-box-archive text-slate-500"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">Surat Diarsipkan</p>
            <p class="text-3xl font-extrabold text-slate-800 mt-1">{{ $ringkasan['diarsipkan'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Total arsip</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-4">
        {{-- Grafik statistik 6 bulan — SVG polos tanpa library JS --}}
        <div class="card p-5 lg:col-span-2">
            <p class="font-bold text-slate-800 mb-4 text-sm">Statistik Surat (6 Bulan Terakhir)</p>
            <div class="flex items-center gap-4 mb-3 text-xs">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Surat Masuk</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Surat Keluar</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Disposisi Aktif</span>
            </div>
            @php
                $maxNilai = max(1, $statistik->flatMap(fn ($b) => [$b['masuk'], $b['keluar'], $b['disposisi_aktif']])->max());
                $lebar = 600; $tinggi = 180; $n = $statistik->count();
                $jarakX = $n > 1 ? $lebar / ($n - 1) : $lebar;
                $titik = function (string $kunci) use ($statistik, $maxNilai, $tinggi, $jarakX) {
                    return $statistik->values()->map(fn ($b, $i) => round($i * $jarakX, 1) . ',' . round($tinggi - ($b[$kunci] / $maxNilai) * $tinggi, 1))->implode(' ');
                };
            @endphp
            <svg viewBox="0 0 {{ $lebar }} {{ $tinggi + 24 }}" class="w-full" preserveAspectRatio="none" style="height:200px">
                <polyline points="{{ $titik('masuk') }}" fill="none" stroke="#3b82f6" stroke-width="2.5" />
                <polyline points="{{ $titik('keluar') }}" fill="none" stroke="#10b981" stroke-width="2.5" />
                <polyline points="{{ $titik('disposisi_aktif') }}" fill="none" stroke="#f59e0b" stroke-width="2.5" />
                @foreach($statistik->values() as $i => $b)
                    <circle cx="{{ round($i * $jarakX, 1) }}" cy="{{ round($tinggi - ($b['masuk'] / $maxNilai) * $tinggi, 1) }}" r="3" fill="#3b82f6" />
                    <circle cx="{{ round($i * $jarakX, 1) }}" cy="{{ round($tinggi - ($b['keluar'] / $maxNilai) * $tinggi, 1) }}" r="3" fill="#10b981" />
                    <circle cx="{{ round($i * $jarakX, 1) }}" cy="{{ round($tinggi - ($b['disposisi_aktif'] / $maxNilai) * $tinggi, 1) }}" r="3" fill="#f59e0b" />
                @endforeach
            </svg>
            <div class="flex justify-between text-xs text-slate-400 mt-1">
                @foreach($statistik as $b)<span>{{ $b['label'] }}</span>@endforeach
            </div>
        </div>

        {{-- Disposisi Terbaru --}}
        <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
                <p class="font-bold text-slate-800 text-sm">Disposisi Terbaru</p>
                <a href="{{ route('disposisi.index') }}" class="text-xs text-brand-600 font-semibold">Lihat Semua</a>
            </div>
            <div class="space-y-3">
                @forelse($disposisiTerbaru as $d)
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-file-lines text-slate-500 text-sm"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-semibold text-slate-700 truncate">Disposisi dari {{ $d->dariUser->name ?? '-' }}</p>
                                <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-semibold
                                    @if($d->status === 'menunggu') bg-slate-100 text-slate-500
                                    @elseif($d->status === 'dibaca') bg-blue-50 text-blue-700
                                    @elseif($d->status === 'diproses') bg-amber-50 text-amber-700
                                    @elseif($d->status === 'selesai') bg-emerald-50 text-emerald-700
                                    @else bg-red-50 text-red-700
                                    @endif">{{ ucfirst($d->status) }}</span>
                            </div>
                            <p class="text-xs text-slate-500 truncate">{{ $d->surat->jenisSurat->nama_jenis ?? '-' }}</p>
                            <p class="text-xs text-slate-400">Kepada: {{ $d->kepadaUser->name ?? '-' }} &middot; {{ $d->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400">Belum ada disposisi.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-4">
        {{-- Surat Masuk Terbaru --}}
        <div class="card p-5 lg:col-span-2">
            <div class="flex items-center justify-between mb-3">
                <p class="font-bold text-slate-800 text-sm">Surat Masuk Terbaru</p>
                <a href="{{ route('surat.index', ['arah' => 'masuk']) }}" class="text-xs text-brand-600 font-semibold">Lihat Semua</a>
            </div>
            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead><tr><th>No. Surat</th><th>Asal Surat</th><th>Perihal</th><th>Tanggal</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($suratMasukTerbaru as $s)
                        @php $dTerbaru = $s->disposisiTerbaru; @endphp
                        <tr class="cursor-pointer hover:bg-slate-50" onclick="location.href='{{ route('surat.show', $s) }}'">
                            <td class="font-medium whitespace-nowrap">{{ $s->nomor_surat ?: '-' }}</td>
                            <td>{{ $s->asal_surat ?: '-' }}</td>
                            <td>{{ $s->jenisSurat->nama_jenis ?? '-' }}</td>
                            <td class="text-slate-500 whitespace-nowrap">{{ $s->tanggal->translatedFormat('d M Y') }}</td>
                            <td>
                                @if($dTerbaru)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap
                                        @if($dTerbaru->status === 'menunggu') bg-amber-50 text-amber-700
                                        @elseif($dTerbaru->status === 'dibaca') bg-blue-50 text-blue-700
                                        @elseif($dTerbaru->status === 'diproses') bg-amber-50 text-amber-700
                                        @elseif($dTerbaru->status === 'selesai') bg-emerald-50 text-emerald-700
                                        @else bg-red-50 text-red-700
                                        @endif">{{ $dTerbaru->status === 'menunggu' ? 'Menunggu Disposisi' : ucfirst($dTerbaru->status) }}</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 whitespace-nowrap">Menunggu Disposisi</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-slate-400 py-8">Belum ada surat masuk.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pengingat --}}
        <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
                <p class="font-bold text-slate-800 text-sm">Pengingat</p>
                <a href="{{ route('disposisi.index') }}" class="text-xs text-brand-600 font-semibold">Lihat Semua</a>
            </div>
            <div class="space-y-2">
                <a href="{{ route('disposisi.index') }}" class="flex items-center justify-between border border-slate-200 rounded-lg px-3 py-2.5 hover:bg-slate-50">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-calendar-day text-red-500 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Disposisi mendekati deadline</p>
                            <p class="text-xs text-slate-400">{{ $pengingat['deadline'] }} disposisi akan melewati batas waktu</p>
                        </div>
                    </div>
                    <span class="w-6 h-6 rounded-full bg-red-500 text-white text-xs font-bold flex items-center justify-center shrink-0">{{ $pengingat['deadline'] }}</span>
                </a>
                <a href="{{ route('surat.index', ['status' => 'selesai']) }}" class="flex items-center justify-between border border-slate-200 rounded-lg px-3 py-2.5 hover:bg-slate-50">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-box-archive text-amber-500 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Surat belum diarsipkan</p>
                            <p class="text-xs text-slate-400">{{ $pengingat['belum_diarsipkan'] }} surat perlu diarsipkan</p>
                        </div>
                    </div>
                    <span class="w-6 h-6 rounded-full bg-amber-500 text-white text-xs font-bold flex items-center justify-center shrink-0">{{ $pengingat['belum_diarsipkan'] }}</span>
                </a>
                <a href="{{ route('surat.index', ['status' => 'draft']) }}" class="flex items-center justify-between border border-slate-200 rounded-lg px-3 py-2.5 hover:bg-slate-50">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-file-pen text-blue-500 text-xs"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Draft tersimpan</p>
                            <p class="text-xs text-slate-400">{{ $pengingat['draft'] }} draft surat tersimpan</p>
                        </div>
                    </div>
                    <span class="w-6 h-6 rounded-full bg-blue-500 text-white text-xs font-bold flex items-center justify-center shrink-0">{{ $pengingat['draft'] }}</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
