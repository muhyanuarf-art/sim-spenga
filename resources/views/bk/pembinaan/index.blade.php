@extends('layouts.app')
@section('title', 'Pembinaan Siswa')

@section('content')
<div class="space-y-6">

    <x-bk-tab-catatan />
    <div class="flex items-center justify-between flex-wrap gap-3 no-print">
        <p class="text-sm text-slate-400">Riwayat pembinaan yang sudah/sedang dijalankan BK terhadap siswa.</p>
        @if(in_array(auth()->user()->role, ['guru_bk', 'admin']))
            <a href="{{ route('bk.pembinaan.create') }}" class="btn-primary"><i class="fa-solid fa-plus mr-1.5"></i> Catat Pembinaan</a>
        @endif
    </div>

    <div class="card p-5 no-print">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Status</label>
                {{-- Nilainya tetap 'Pembinaan'/'Selesai' (sesuai isi database),
                     hanya labelnya diseragamkan dengan istilah dua-keadaan
                     yang dipakai di seluruh modul BK. --}}
                <select name="status" class="input" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    @foreach(['Pembinaan' => 'Belum Selesai', 'Selesai' => 'Selesai'] as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(request('status') === $nilai)>{{ $label }}</option>
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
        <x-kop-surat />

        <div class="flex items-center justify-between mb-1 flex-wrap gap-2">
            <p class="font-extrabold text-slate-800 text-lg">Rekap Pembinaan Siswa</p>
            <button type="button" onclick="cetakBagian('print-pembinaan')" class="btn-outline no-print"><i class="fa-solid fa-print mr-1.5"></i> Cetak / Export PDF</button>
        </div>
        <p class="text-sm text-slate-400 mb-4">
            @if(request('bulan')) Bulan {{ \Carbon\Carbon::create()->month((int) request('bulan'))->translatedFormat('F') }} @endif
            {{ request('tahun') ?: '' }}
            @if(!request('bulan') && !request('tahun')) Seluruh periode (sesuai filter yang dipilih) @endif
        </p>

        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th class="w-10">No</th><th>Tanggal</th><th>Siswa</th><th>Kelas</th><th>Tahap</th><th>Jenis</th><th>Status</th><th>Petugas</th><th class="th-aksi no-print">Aksi</th></tr></thead>
                <tbody>
                    @forelse($data as $p)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="text-slate-500 whitespace-nowrap">{{ $p->tanggal->translatedFormat('d M Y') }}</td>
                        <td class="font-medium"><a href="{{ route('bk.siswa.show', $p->siswa_id) }}" class="hover:underline">{{ $p->siswa->nama ?? '-' }}</a></td>
                        <td><x-kelas-badge :nama="$p->siswa->kelas->nama_kelas ?? '-'" /></td>
                        <td><span class="badge bg-violet-50 text-violet-700">Tahap {{ $p->tahap }}</span></td>
                        <td class="text-slate-500">{{ $p->jenis_pembinaan }}</td>
                        <td>
                            <span class="badge {{ $p->badgeStatusRingkas() }}">{{ $p->labelStatusRingkas() }}</span>
                        </td>
                        <td class="text-slate-500">{{ $p->petugas->name ?? '-' }}</td>
                        <td class="td-aksi no-print">
                            <div class="action-buttons">
                                {{-- Inilah yang paling sering dicari pengguna: menandai
                                     pembinaan sudah selesai. Sekarang cukup satu klik
                                     dari daftar ini. --}}
                                @if(in_array(auth()->user()->role, ['guru_bk', 'admin']))
                                    <x-bk-tombol-selesai
                                        :action="route('bk.pembinaan.update', $p)"
                                        metode="PUT"
                                        :selesai="$p->isSelesai()"
                                        status-buka="Pembinaan">
                                        {{-- Ikut dikirim supaya hasil pembinaan yang sudah
                                             ditulis tidak terhapus saat status diubah. --}}
                                        <input type="hidden" name="hasil_pembinaan" value="{{ $p->hasil_pembinaan }}">
                                    </x-bk-tombol-selesai>
                                @endif
                                <a href="{{ route('bk.siswa.show', $p->siswa_id) }}" class="btn-chip btn-chip-edit"><i class="fa-solid fa-eye mr-1.5"></i> Detail</a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-slate-400 py-8">Belum ada pembinaan tercatat.</td></tr>
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
