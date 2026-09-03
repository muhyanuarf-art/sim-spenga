@extends('layouts.app')
@section('title', 'Pengurangan Poin')

@section('content')
@php $user = auth()->user(); @endphp
<div class="space-y-6">

    <x-bk-tab-catatan />
    <div class="flex items-center justify-between flex-wrap gap-3 no-print">
        <p class="text-sm text-slate-400">Riwayat pengurangan poin — penghargaan atas perubahan perilaku siswa.</p>
        @if(in_array($user->role, ['guru_bk', 'admin']))
            <a href="{{ route('bk.pengurangan.create') }}" class="btn-primary bg-emerald-600 hover:bg-emerald-700">
                <i class="fa-solid fa-plus mr-1.5"></i> Kurangi Poin
            </a>
        @endif
    </div>

    <div class="card p-5">
        <form method="GET" class="flex flex-wrap gap-3 mb-4">
            <select name="status" class="input max-w-[180px]" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                @foreach(['Aktif','Dibatalkan'] as $s)
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
                <thead><tr><th class="w-12 text-center">No</th><th>Tanggal</th><th>Siswa</th><th>Kelas</th><th>Jumlah</th><th>Alasan</th><th>Petugas</th><th>Status</th>@if(in_array($user->role,['guru_bk','admin']))<th class="th-aksi">Aksi</th>@endif</tr></thead>
                <tbody>
                    @forelse($data as $p)
                    <tr class="{{ $p->dibatalkan_at ? 'opacity-40' : '' }}">
                        <td class="text-center text-slate-400">{{ $data->firstItem() + $loop->index }}</td>
                        <td class="text-slate-500 whitespace-nowrap">{{ $p->tanggal->translatedFormat('d M Y') }}</td>
                        <td class="font-medium"><a href="{{ route('bk.siswa.show', $p->siswa_id) }}" class="hover:underline">{{ $p->siswa->nama ?? '-' }}</a></td>
                        <td><x-kelas-badge :nama="$p->siswa->kelas->nama_kelas ?? '-'" /></td>
                        <td class="font-bold text-emerald-600">-{{ $p->jumlah }}</td>
                        <td class="text-slate-500">{{ \Illuminate\Support\Str::limit($p->alasan, 60) }}</td>
                        <td class="text-slate-500">{{ $p->petugas->name ?? '-' }}</td>
                        <td>
                            @if($p->dibatalkan_at)
                                <span class="badge bg-slate-100 text-slate-400">Dibatalkan</span>
                            @else
                                <span class="badge bg-emerald-50 text-emerald-700">Aktif</span>
                            @endif
                        </td>
                        @if(in_array($user->role,['guru_bk','admin']))
                        <td class="td-aksi">
                            @if(!$p->dibatalkan_at)
                            {{-- Dulu memakai confirm() lalu prompt() berturut-turut:
                                 dua kotak kecil bawaan peramban yang muncul
                                 bergantian. Sekarang satu dialog dengan huruf
                                 besar berikut kotak isian alasannya. Alasan yang
                                 dikosongkan tetap membatalkan aksi, sama seperti
                                 perilaku prompt() sebelumnya. --}}
                            <form method="POST" action="{{ route('bk.pengurangan.batalkan', $p) }}"
                                  data-konfirmasi="Batalkan transaksi pengurangan poin ini?"
                                  data-konfirmasi-judul="Batalkan Transaksi"
                                  data-konfirmasi-isian="Tuliskan alasan pembatalannya:"
                                  data-konfirmasi-isian-untuk="alasan_pembatalan">
                                @csrf
                                <input type="hidden" name="alasan_pembatalan" value="">
                                <button class="btn-chip btn-chip-delete"><i class="fa-solid fa-ban mr-1.5"></i> Batalkan</button>
                            </form>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-slate-400 py-8">Belum ada pengurangan poin tercatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $data->links() }}</div>
    </div>
</div>
@endsection
