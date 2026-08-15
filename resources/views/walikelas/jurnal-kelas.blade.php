@extends('layouts.app')
@section('title', 'Jurnal Mengajar Kelas')

@section('content')
<div class="space-y-6">
    <div class="card p-5 no-print">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            @if(in_array(auth()->user()->role, ['admin', 'kurikulum', 'kepala_sekolah', 'guru_bk']))
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
            <button type="button" onclick="cetakBagian('print-jurnal-kelas')" class="btn-outline">🖨️ Cetak / Export PDF</button>
        </form>
    </div>

    <div class="card p-5 print-section" id="print-jurnal-kelas">
        <p class="font-extrabold text-slate-800 text-lg mb-1">Jurnal Mengajar Kelas {{ $kelas->nama_kelas }}</p>
        <p class="text-sm text-slate-400 mb-4">Bulan {{ \Carbon\Carbon::create()->month($bulan)->translatedFormat('F') }} {{ $tahun }}</p>

        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead>
                    <tr><th>Tanggal</th><th>Jam</th><th>Mapel</th><th>Guru</th><th>Materi</th><th>H/S/I/A</th></tr>
                </thead>
                <tbody>
                    @forelse($jurnal as $j)
                    <tr>
                        <td class="whitespace-nowrap">{{ $j->tanggal->translatedFormat('d M Y') }}</td>
                        <td>{{ $j->label_sesi }}</td>
                        <td class="font-medium">{{ $j->mapel->nama_mapel }}</td>
                        <td>{{ $j->guru->name }}</td>
                        <td class="text-slate-500">{{ \Illuminate\Support\Str::limit($j->materi, 60) }}</td>
                        <td class="whitespace-nowrap text-xs">
                            <span class="text-emerald-600 font-bold">{{ $j->jumlah_hadir }}</span> /
                            <span class="text-amber-600 font-bold">{{ $j->jumlah_sakit }}</span> /
                            <span class="text-blue-600 font-bold">{{ $j->jumlah_izin }}</span> /
                            <span class="text-red-600 font-bold">{{ $j->jumlah_alfa }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-slate-400 py-8">Belum ada jurnal pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
