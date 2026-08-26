@extends('layouts.app')
@section('title', 'Profil Poin Siswa')

@section('content')
<div class="space-y-6">
    <div class="card p-5">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-semibold text-slate-500 mb-1">Cari Nama Siswa</label>
                <input type="text" name="cari" value="{{ request('cari') }}" class="input" placeholder="Ketik nama...">
            </div>
            @if($kelasList->isNotEmpty())
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Kelas</label>
                <select name="kelas_id" class="input">
                    <option value="">Semua Kelas</option>
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <button type="submit" class="btn-primary h-[38px]">Cari</button>
        </form>
    </div>

    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-1">Siswa dengan Riwayat Kasus</p>
        <p class="text-xs text-slate-400 mb-4">Diurutkan dari poin aktif tertinggi. Siswa tanpa riwayat kasus tidak ditampilkan di sini.</p>
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Nama</th><th>Kelas</th><th>Poin Aktif</th><th>Tahap</th><th>Status</th><th>Kasus</th></tr></thead>
                <tbody>
                    @forelse($siswas as $r)
                    <tr class="cursor-pointer hover:bg-slate-50" onclick="location.href='{{ route('bk.siswa.show', $r['siswa']) }}'">
                        <td class="font-medium">
                            <div class="flex items-center gap-2">
                                <x-initial-avatar :nama="$r['siswa']->nama" /> {{ $r['siswa']->nama }}
                            </div>
                        </td>
                        <td><x-kelas-badge :nama="$r['siswa']->kelas->nama_kelas ?? '-'" /></td>
                        <td class="font-bold {{ $r['poin_aktif'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">{{ $r['poin_aktif'] }}</td>
                        <td>{{ $r['tahap_saat_ini'] ? 'Tahap '.$r['tahap_saat_ini'] : '-' }}</td>
                        <td>
                            @php
                                $statusBadge = match ($r['status']) {
                                    'Normal' => 'bg-emerald-50 text-emerald-700',
                                    'Dalam Pembinaan' => 'bg-amber-50 text-amber-700',
                                    'Selesai' => 'bg-teal-50 text-teal-700',
                                    default => 'bg-sky-50 text-sky-700', // Menunggu Pembinaan
                                };
                            @endphp
                            <span class="badge {{ $statusBadge }}">
                                {{ $r['status'] }}
                            </span>
                        </td>
                        <td class="text-slate-500">{{ $r['jumlah_kasus'] }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-slate-400 py-8">Belum ada siswa dengan riwayat kasus.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
