@extends('layouts.app')
@section('title', 'Status WhatsApp Orang Tua')

@section('content')
<div class="space-y-6">
    <div class="card p-5">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            @if(in_array(auth()->user()->role, ['admin', 'kurikulum', 'kepala_sekolah']))
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Kelas</label>
                <select name="kelas_id" class="input" onchange="this.form.submit()">
                    @foreach($daftarKelas as $k)
                        <option value="{{ $k->id }}" {{ $k->id === $kelas->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal</label>
                <input type="date" name="tanggal" value="{{ $tanggal }}" class="input" onchange="this.form.submit()">
            </div>
        </form>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="card p-4 text-center">
            <p class="text-2xl font-extrabold text-slate-600">{{ $ringkasan['menunggu'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Menunggu</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-2xl font-extrabold text-blue-600">{{ $ringkasan['terkirim'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Terkirim</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-2xl font-extrabold text-indigo-600">{{ $ringkasan['diterima'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Diterima</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-2xl font-extrabold text-emerald-600">{{ $ringkasan['dibaca'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Telah Dibaca</p>
        </div>
        <div class="card p-4 text-center">
            <p class="text-2xl font-extrabold text-red-600">{{ $ringkasan['gagal'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Gagal</p>
        </div>
    </div>

    <div class="card p-5">
        <p class="font-extrabold text-slate-800 text-lg mb-1">Status WhatsApp Kelas {{ $kelas->nama_kelas }}</p>
        <p class="text-sm text-slate-400 mb-4">{{ \Carbon\Carbon::parse($tanggal)->translatedFormat('l, d F Y') }} — notifikasi ke orang tua siswa yang Alfa hari ini</p>

        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        <th>No. WA Tujuan</th>
                        <th>Status</th>
                        <th>Percobaan</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notifikasi as $n)
                    <tr>
                        <td class="font-medium">{{ $n->siswa->nama }}</td>
                        <td>{{ $n->no_hp_tujuan ?? '—' }}</td>
                        <td><span class="badge {{ $n->statusBadgeClass() }}">{{ $n->statusLabel() }}</span></td>
                        <td>{{ $n->percobaan_ke }}/{{ \App\Models\NotifikasiWa::MAKS_PERCOBAAN }}</td>
                        <td class="text-slate-500 text-sm">{{ $n->keterangan_gagal ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-slate-400 py-6">Tidak ada siswa Alfa pada tanggal ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
