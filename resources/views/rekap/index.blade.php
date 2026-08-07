@extends('layouts.app')
@section('title', 'Rekapitulasi')

@section('content')
<div class="space-y-6">
    <div class="card p-5">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Bulan</label>
                <select name="bulan" class="input" onchange="this.form.submit()">
                    @foreach(range(1,12) as $b)
                        <option value="{{ $b }}" {{ $b === $bulan ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($b)->translatedFormat('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tahun</label>
                <select name="tahun" class="input" onchange="this.form.submit()">
                    @foreach(range(now()->year - 1, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ $y === $tahun ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="card p-5">
            <p class="font-bold text-slate-800 mb-4">Kepatuhan Pengisian Jurnal - Guru</p>
            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead><tr><th>Guru</th><th class="text-right">Jumlah Jurnal</th></tr></thead>
                    <tbody>
                        @foreach($rekapGuru as $g)
                        <tr>
                            <td class="font-medium">{{ $g->name }}</td>
                            <td class="text-right font-bold {{ $g->jurnal_bulan_ini == 0 ? 'text-red-500' : 'text-emerald-600' }}">{{ $g->jurnal_bulan_ini }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-5">
            <p class="font-bold text-slate-800 mb-4">Rekap Per Kelas</p>
            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead><tr><th>Kelas</th><th>Siswa</th><th>Jurnal</th><th>Total Alfa</th></tr></thead>
                    <tbody>
                        @foreach($rekapKelas as $r)
                        <tr>
                            <td class="font-semibold">{{ $r['kelas']->nama_kelas }}</td>
                            <td>{{ $r['kelas']->siswas_count }}</td>
                            <td>{{ $r['jumlah_jurnal'] }}</td>
                            <td class="font-bold {{ $r['total_alfa'] > 0 ? 'text-red-500' : 'text-slate-400' }}">{{ $r['total_alfa'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
