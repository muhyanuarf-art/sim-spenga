@extends('layouts.app')
@section('title', 'Pemanggilan Orang Tua')

@section('content')
<div class="space-y-6">
    <p class="text-sm text-slate-400">Riwayat pemanggilan orang tua/wali. Untuk mencatat pemanggilan baru, buka profil siswa terkait.</p>

    <div class="card p-5">
        <form method="GET" class="flex flex-wrap gap-3 mb-4">
            <select name="status" class="input max-w-[180px]" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                @foreach(['Hadir','Tidak Hadir'] as $s)
                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
            @if($kelasList->isNotEmpty())
            <select name="kelas_id" class="input max-w-[180px]" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                @foreach($kelasList as $k)
                    <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                @endforeach
            </select>
            @endif
            <select name="bulan" class="input max-w-[160px]" onchange="this.form.submit()">
                <option value="">Semua Bulan</option>
                @foreach(range(1,12) as $b)
                    <option value="{{ $b }}" {{ request('bulan') == $b ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($b)->translatedFormat('F') }}</option>
                @endforeach
            </select>
            <select name="tahun" class="input max-w-[130px]" onchange="this.form.submit()">
                <option value="">Semua Tahun</option>
                @foreach(range(now()->year - 1, now()->year + 1) as $y)
                    <option value="{{ $y }}" {{ request('tahun') == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>

        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Tanggal</th><th>Siswa</th><th>Kelas</th><th>Alasan</th><th>Kehadiran</th><th>Petugas</th></tr></thead>
                <tbody>
                    @forelse($data as $p)
                    <tr>
                        <td class="text-slate-500 whitespace-nowrap">{{ $p->tanggal->translatedFormat('d M Y') }}</td>
                        <td class="font-medium"><a href="{{ route('bk.siswa.show', $p->siswa_id) }}" class="hover:underline">{{ $p->siswa->nama ?? '-' }}</a></td>
                        <td><x-kelas-badge :nama="$p->siswa->kelas->nama_kelas ?? '-'" /></td>
                        <td class="text-slate-500">{{ \Illuminate\Support\Str::limit($p->alasan, 60) }}</td>
                        <td>
                            <span class="badge {{ $p->ortu_hadir ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                {{ $p->ortu_hadir ? 'Hadir' : 'Tidak Hadir' }}
                            </span>
                        </td>
                        <td class="text-slate-500">{{ $p->petugas->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-slate-400 py-8">Belum ada pemanggilan orang tua tercatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $data->links() }}</div>
    </div>
</div>
@endsection
