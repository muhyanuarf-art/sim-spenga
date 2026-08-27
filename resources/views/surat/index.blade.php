@extends('layouts.app')
@section('title', $judul)

@section('deskripsi', 'Surat resmi BK — dibuat oleh Guru BK, dapat dilihat & dicetak oleh Kesiswaan, Kurikulum, dan Kepala Sekolah.')

@section('aksi')
    @if(in_array(auth()->user()->role, ['guru_bk', 'admin']))
        <a href="{{ route('surat.create') }}" class="btn-primary"><i class="fa-solid fa-plus"></i> Buat Surat</a>
    @endif
@endsection

@section('content')
<div class="space-y-6">
    <div class="card p-5">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <input type="text" name="cari" value="{{ request('cari') }}" placeholder="Cari nama / NIS siswa..." class="input flex-1 min-w-[200px]">
            <select name="jenis_surat_id" class="input" onchange="this.form.submit()">
                <option value="">Semua Jenis Surat</option>
                @foreach($jenisSuratList as $j)
                    <option value="{{ $j->id }}" {{ (string) request('jenis_surat_id') === (string) $j->id ? 'selected' : '' }}>{{ $j->nama_jenis }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-outline">Cari</button>
        </form>
    </div>

    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th class="w-10">No</th><th>Tanggal</th><th>Jenis Surat</th><th>Siswa</th><th>Kelas</th><th>Nomor Surat</th><th>Dibuat Oleh</th><th class="th-aksi">Aksi</th></tr></thead>
                <tbody>
                @forelse($surat as $i => $s)
                    <tr>
                        <td>{{ $surat->firstItem() + $i }}</td>
                        <td class="text-slate-500 whitespace-nowrap">{{ $s->tanggal->translatedFormat('d M Y') }}</td>
                        <td class="font-medium">{{ $s->jenisSurat->nama_jenis ?? '-' }}</td>
                        <td>{{ $s->siswa->nama ?? '-' }}</td>
                        <td>{{ $s->siswa->kelas->nama_kelas ?? '-' }}</td>
                        <td class="text-slate-500">{{ $s->nomor_surat ?: '-' }}</td>
                        <td class="text-slate-500">{{ $s->dibuatOleh->name ?? '-' }}</td>
                        <td class="td-aksi">
                            <div class="action-buttons">
                                <a href="{{ route('surat.show', $s) }}" class="btn-chip btn-chip-edit"><i class="fa-solid fa-eye mr-1.5"></i> Lihat/Cetak</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-slate-400 py-8">Belum ada surat tercatat.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $surat->links() }}</div>
    </div>
</div>
@endsection
