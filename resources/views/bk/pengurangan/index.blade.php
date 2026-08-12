@extends('layouts.app')
@section('title', 'Pengurangan Poin')

@section('content')
@php $user = auth()->user(); @endphp
<div class="space-y-6">
    <p class="text-sm text-slate-400">Riwayat pengurangan poin (penghargaan atas perubahan perilaku). Untuk mencatat pengurangan baru, buka profil siswa terkait.</p>

    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Tanggal</th><th>Siswa</th><th>Kelas</th><th>Jumlah</th><th>Alasan</th><th>Petugas</th><th>Status</th>@if(in_array($user->role,['guru_bk','admin']))<th class="th-aksi">Aksi</th>@endif</tr></thead>
                <tbody>
                    @forelse($data as $p)
                    <tr class="{{ $p->dibatalkan_at ? 'opacity-40' : '' }}">
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
                            <form method="POST" action="{{ route('bk.pengurangan.batalkan', $p) }}" onsubmit="return confirm('Batalkan transaksi ini?') && (this.querySelector('[name=alasan_pembatalan]').value = prompt('Alasan pembatalan:') || false)">
                                @csrf
                                <input type="hidden" name="alasan_pembatalan" value="">
                                <button class="btn-chip btn-chip-delete">Batalkan</button>
                            </form>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-slate-400 py-8">Belum ada pengurangan poin tercatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $data->links() }}</div>
    </div>
</div>
@endsection
