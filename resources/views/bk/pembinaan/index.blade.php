@extends('layouts.app')
@section('title', 'Pembinaan Siswa')

@section('content')
<div class="space-y-6">
    <p class="text-sm text-slate-400">Riwayat pembinaan yang sudah/sedang dijalankan BK terhadap siswa.</p>

    <div class="card p-5">
        <form method="GET" class="flex flex-wrap gap-3 mb-4">
            <select name="status" class="input max-w-[200px]" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                @foreach(['Pembinaan','Selesai'] as $s)
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
        </form>

        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Tanggal</th><th>Siswa</th><th>Kelas</th><th>Tahap</th><th>Jenis</th><th>Status</th><th>Petugas</th><th class="th-aksi">Aksi</th></tr></thead>
                <tbody>
                    @forelse($data as $p)
                    <tr>
                        <td class="text-slate-500 whitespace-nowrap">{{ $p->tanggal->translatedFormat('d M Y') }}</td>
                        <td class="font-medium"><a href="{{ route('bk.siswa.show', $p->siswa_id) }}" class="hover:underline">{{ $p->siswa->nama ?? '-' }}</a></td>
                        <td><x-kelas-badge :nama="$p->siswa->kelas->nama_kelas ?? '-'" /></td>
                        <td><span class="badge bg-violet-50 text-violet-700">Tahap {{ $p->tahap }}</span></td>
                        <td class="text-slate-500">{{ $p->jenis_pembinaan }}</td>
                        <td>
                            <span class="badge {{ $p->status === 'Selesai' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                {{ $p->status }}
                            </span>
                        </td>
                        <td class="text-slate-500">{{ $p->petugas->name ?? '-' }}</td>
                        <td class="td-aksi"><a href="{{ route('bk.siswa.show', $p->siswa_id) }}" class="btn-chip">Detail</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-slate-400 py-8">Belum ada pembinaan tercatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $data->links() }}</div>
    </div>
</div>
@endsection
