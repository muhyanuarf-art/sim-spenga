@extends('layouts.app')
@section('title', 'Pembinaan Siswa')

@section('content')
<div class="space-y-6">
    <p class="text-sm text-slate-400 no-print">Riwayat pembinaan yang sudah/sedang dijalankan BK terhadap siswa.</p>

    <div class="card p-5 no-print">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Status</label>
                <select name="status" class="input" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    @foreach(['Pembinaan','Selesai'] as $s)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            @if($kelasList->isNotEmpty())
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Kelas</label>
                <select name="kelas_id" class="input" onchange="this.form.submit()">
                    <option value="">Semua Kelas</option>
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Bulan</label>
                <select name="bulan" class="input" onchange="this.form.submit()">
                    <option value="">Semua Bulan</option>
                    @foreach(range(1,12) as $b)
                        <option value="{{ $b }}" {{ request('bulan') == $b ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($b)->translatedFormat('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tahun</label>
                <select name="tahun" class="input" onchange="this.form.submit()">
                    <option value="">Semua Tahun</option>
                    @foreach(range(now()->year - 1, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ request('tahun') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="card p-5 print-section" id="print-pembinaan">
        <div class="flex items-center justify-between mb-1 flex-wrap gap-2">
            <p class="font-extrabold text-slate-800 text-lg">Rekap Pembinaan Siswa</p>
            <button type="button" onclick="cetakBagian('print-pembinaan')" class="btn-outline no-print">🖨️ Cetak / Export PDF</button>
        </div>
        <p class="text-sm text-slate-400 mb-4">
            @if(request('bulan')) Bulan {{ \Carbon\Carbon::create()->month((int) request('bulan'))->translatedFormat('F') }} @endif
            {{ request('tahun') ?: '' }}
            @if(!request('bulan') && !request('tahun')) Seluruh periode (sesuai filter yang dipilih) @endif
        </p>

        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Tanggal</th><th>Siswa</th><th>Kelas</th><th>Tahap</th><th>Jenis</th><th>Status</th><th>Petugas</th><th class="th-aksi no-print">Aksi</th></tr></thead>
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
                        <td class="td-aksi no-print"><a href="{{ route('bk.siswa.show', $p->siswa_id) }}" class="btn-chip">Detail</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-slate-400 py-8">Belum ada pembinaan tercatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-blok-tanda-tangan-dua
            jabatan-kanan="Guru BK"
            :nama-kanan="$guruBk->name ?? null"
            :nip-kanan="$guruBk->nip ?? null"
        />
    </div>
</div>
@endsection
