@extends('layouts.app')
@section('title', 'Kasus / Pelanggaran')

@section('content')
@php $user = auth()->user(); @endphp
<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3 no-print">
        <p class="text-sm text-slate-400">Riwayat kasus/pelanggaran siswa. Riwayat tidak pernah dihapus — hanya bisa dibatalkan (tetap tercatat).</p>
        @if(in_array($user->role, ['guru','guru_bk','admin']))
            <a href="{{ route('bk.kasus.create') }}" class="btn-primary bg-rose-600 hover:bg-rose-700">+ Catat Kasus Baru</a>
        @endif
    </div>

    <div class="card p-5 no-print">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Status</label>
                <select name="status" class="input" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    @foreach(['Baru','Diproses','Dalam Pembinaan','Selesai'] as $s)
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

    {{-- 1 tabel ini dipakai untuk tampilan layar SEKALIGUS bagian yang
         dicetak/PDF-kan — tidak ada tabel duplikat. --}}
    <div class="card p-5 print-section" id="print-kasus">
        <x-kop-surat />

        <div class="flex items-center justify-between mb-1 flex-wrap gap-2">
            <p class="font-extrabold text-slate-800 text-lg">Rekap Kasus / Pelanggaran Siswa</p>
            <button type="button" onclick="cetakBagian('print-kasus')" class="btn-outline no-print"><i class="fa-solid fa-print mr-1.5"></i> Cetak / Export PDF</button>
        </div>
        <p class="text-sm text-slate-400 mb-4">
            @if(request('bulan')) Bulan {{ \Carbon\Carbon::create()->month((int) request('bulan'))->translatedFormat('F') }} @endif
            {{ request('tahun') ?: '' }}
            @if(!request('bulan') && !request('tahun')) Seluruh periode (sesuai filter yang dipilih) @endif
        </p>

        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>No</th><th>Siswa</th><th>Kelas</th><th>Tanggal</th><th>Pelanggaran</th><th>Kategori</th><th>Poin</th><th>Status</th><th>Pelapor</th><th class="th-aksi no-print">Aksi</th></tr></thead>
                <tbody>
                    @forelse($data as $i => $k)
                    <tr class="{{ $k->dibatalkan_at ? 'opacity-40' : '' }}">
                        <td>{{ $i + 1 }}</td>
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
                        <td class="td-aksi no-print">
                            <a href="{{ route('bk.siswa.show', $k->siswa_id) }}" class="btn-chip btn-chip-edit"><i class="fa-solid fa-eye mr-1.5"></i> Detail</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-slate-400 py-8">Belum ada kasus tercatat.</td></tr>
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
