@extends('layouts.app')
@section('title', 'Anggota — ' . $ekstrakurikuler->nama_ekstrakurikuler)

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <a href="{{ route('ekstrakurikuler.index') }}" class="text-xs text-brand-600 font-semibold">&larr; Kembali ke Kegiatan Ekstrakurikuler</a>
            <p class="text-lg font-extrabold text-slate-800 mt-1">Anggota — {{ $ekstrakurikuler->nama_ekstrakurikuler }}</p>
            <p class="text-sm text-slate-500">{{ $ekstrakurikuler->anggotas->count() }} siswa terdaftar. Pembina: {{ $ekstrakurikuler->daftarNamaPembina() }}</p>
        </div>
    </div>

    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-3">Tambah Anggota</p>
        <form method="GET" class="flex gap-2 mb-3">
            <input type="text" name="cari" value="{{ request('cari') }}" placeholder="Cari nama / NIS siswa..." class="input flex-1">
            <button type="submit" class="btn-outline">Cari</button>
        </form>

        @if(request()->filled('cari'))
            <div class="border border-slate-200 rounded-lg divide-y divide-slate-100">
                @forelse($hasilCari as $siswa)
                    <div class="flex items-center justify-between px-3 py-2">
                        <div>
                            <p class="font-semibold text-sm">{{ $siswa->nama }}</p>
                            <p class="text-xs text-slate-400">{{ $siswa->nis }} &middot; {{ $siswa->kelas->nama_kelas ?? '—' }}</p>
                        </div>
                        <form method="POST" action="{{ route('ekstrakurikuler.anggota.store', $ekstrakurikuler) }}">
                            @csrf
                            <input type="hidden" name="siswa_id" value="{{ $siswa->id }}">
                            <button class="btn-chip btn-chip-edit"><i class="fa-solid fa-plus mr-1.5"></i> Tambah</button>
                        </form>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 px-3 py-3">Tidak ada siswa aktif yang cocok (atau semua hasil sudah jadi anggota).</p>
                @endforelse
            </div>
        @endif
    </div>

    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-3">Daftar Anggota</p>
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Tanggal Gabung</th><th class="th-aksi">Aksi</th></tr></thead>
                <tbody>
                @forelse($ekstrakurikuler->anggotas->sortBy('siswa.nama') as $a)
                    <tr>
                        <td>{{ $a->siswa->nis }}</td>
                        <td class="font-medium">{{ $a->siswa->nama }}</td>
                        <td>{{ $a->siswa->kelas->nama_kelas ?? '—' }}</td>
                        <td>{{ $a->tanggal_gabung?->translatedFormat('d M Y') ?? '—' }}</td>
                        <td class="td-aksi">
                            <form method="POST" action="{{ route('ekstrakurikuler.anggota.destroy', [$ekstrakurikuler, $a]) }}" onsubmit="return confirm('Keluarkan {{ $a->siswa->nama }} dari kegiatan ini?')">
                                @csrf @method('DELETE')
                                <button class="btn-chip btn-chip-delete"><i class="fa-solid fa-user-minus mr-1.5"></i> Keluarkan</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-slate-400 py-8">Belum ada anggota.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
