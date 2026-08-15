@extends('layouts.app')
@section('title', 'Kasus / Pelanggaran')

@section('content')
@php $user = auth()->user(); @endphp
<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <p class="text-sm text-slate-400">Riwayat kasus/pelanggaran siswa. Riwayat tidak pernah dihapus — hanya bisa dibatalkan (tetap tercatat).</p>
        @if(in_array($user->role, ['guru','guru_bk','admin']))
            <a href="{{ route('bk.kasus.create') }}" class="btn-primary bg-rose-600 hover:bg-rose-700">+ Catat Kasus Baru</a>
        @endif
    </div>

    <div class="card p-5">
        <form method="GET" class="flex flex-wrap gap-3 mb-4">
            <select name="status" class="input max-w-[180px]" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                @foreach(['Baru','Diproses','Dalam Pembinaan','Selesai'] as $s)
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
                <thead><tr><th>No</th><th>Siswa</th><th>Kelas</th><th>Tanggal</th><th>Pelanggaran</th><th>Kategori</th><th>Poin</th><th>Status</th><th>Pelapor</th><th class="th-aksi">Aksi</th></tr></thead>
                <tbody>
                    @forelse($data as $i => $k)
                    <tr class="{{ $k->dibatalkan_at ? 'opacity-40' : '' }}">
                        <td>{{ $data->firstItem() + $i }}</td>
                        <td class="font-medium"><a href="{{ route('bk.siswa.show', $k->siswa_id) }}" class="hover:underline">{{ $k->siswa->nama ?? '-' }}</a></td>
                        <td><x-kelas-badge :nama="$k->kelas->nama_kelas ?? '-'" /></td>
                        <td class="text-slate-500 whitespace-nowrap">{{ $k->tanggal_kejadian->translatedFormat('d M Y') }}</td>
                        <td>{{ $k->nama_pelanggaran }}</td>
                        <td><span class="badge bg-slate-100 text-slate-600">{{ $k->kategori }}</span></td>
                        <td class="font-bold text-rose-600">+{{ $k->poin }}</td>
                        <td>
                            @if($k->dibatalkan_at)
                                <span class="badge bg-slate-100 text-slate-400">Dibatalkan</span>
                            @else
                                <span class="badge bg-sky-50 text-sky-700">{{ $k->status }}</span>
                            @endif
                        </td>
                        <td class="text-slate-500">{{ $k->guruPelapor->name ?? '-' }}</td>
                        <td class="td-aksi">
                            <a href="{{ route('bk.siswa.show', $k->siswa_id) }}" class="btn-chip">Detail</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-slate-400 py-8">Belum ada kasus tercatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $data->links() }}</div>
    </div>
</div>
@endsection
