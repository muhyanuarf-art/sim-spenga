@extends('layouts.app')
@section('title', 'Mata Pelajaran')

@section('content')
<div class="space-y-6" x-data="{ showForm: false }">
    <div class="flex justify-end gap-2">
        <a href="{{ route('mapel.import.form') }}" class="btn-outline"><i class="fa-solid fa-file-import mr-1.5"></i> Import Excel</a>
        <button @click="showForm = !showForm" class="btn-primary">+ Tambah Mapel</button>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Mata Pelajaran</p>
        <form method="POST" action="{{ route('mapel.store') }}" class="grid sm:grid-cols-3 gap-3 items-end">
            @csrf
            <input type="text" name="kode" placeholder="Kode, contoh: MTK" required class="input">
            <input type="text" name="nama_mapel" placeholder="Nama Mata Pelajaran" required class="input">
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Kode</th><th>Nama Mata Pelajaran</th><th class="th-aksi">Aksi</th></tr></thead>
                @forelse($mapel as $m)
                <tbody x-data="{ editing: false }">
                    <tr x-show="!editing">
                        <td class="font-semibold">{{ $m->kode }}</td>
                        <td>{{ $m->nama_mapel }}</td>
                        <td class="td-aksi">
                            <div class="action-buttons">
                                <button type="button" @click="editing = true" class="btn-chip btn-chip-edit"><i class="fa-solid fa-pen mr-1.5"></i> Edit</button>
                                <form method="POST" action="{{ route('mapel.destroy', $m) }}" onsubmit="return confirm('Hapus mata pelajaran ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-chip btn-chip-delete"><i class="fa-solid fa-trash mr-1.5"></i> Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr x-show="editing" x-cloak>
                        <td colspan="3" class="bg-brand-50/40">
                            <form method="POST" action="{{ route('mapel.update', $m) }}" class="grid sm:grid-cols-3 gap-3 items-end py-2">
                                @csrf @method('PUT')
                                <input type="text" name="kode" value="{{ $m->kode }}" required class="input">
                                <input type="text" name="nama_mapel" value="{{ $m->nama_mapel }}" required class="input">
                                <div class="flex gap-2">
                                    <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                                    <button type="button" @click="editing = false" class="btn-outline h-[38px]">Batal</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                </tbody>
                @empty
                <tbody>
                    <tr><td colspan="3" class="text-center text-slate-400 py-8">Belum ada data.</td></tr>
                </tbody>
                @endforelse
            </table>
        </div>
        <div class="mt-4">{{ $mapel->links() }}</div>
    </div>
</div>
@endsection
