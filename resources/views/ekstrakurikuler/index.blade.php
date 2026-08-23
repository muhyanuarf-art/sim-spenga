@extends('layouts.app')
@section('title', 'Kegiatan Ekstrakurikuler')

@section('content')
<div class="space-y-6" x-data="{ showForm: false }">
    <div class="flex justify-between items-center flex-wrap gap-3">
        <p class="text-sm text-slate-500">Daftar kegiatan ekstrakurikuler sekolah. Anggota &amp; absensi per kegiatan dikelola di menu terpisah setelah kegiatannya terdaftar di sini.</p>
        <button @click="showForm = !showForm" class="btn-primary">+ Tambah Kegiatan</button>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Kegiatan Ekstrakurikuler</p>
        <form method="POST" action="{{ route('ekstrakurikuler.store') }}" class="grid sm:grid-cols-3 gap-3 items-end">
            @csrf
            <input type="text" name="nama_ekstrakurikuler" placeholder="Nama Kegiatan, contoh: Pramuka" required class="input sm:col-span-1">
            <select name="pembina_id" class="input sm:col-span-1">
                <option value="">— Pembina belum ditentukan —</option>
                @foreach($calonPembina as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
            <input type="text" name="keterangan" placeholder="Keterangan (opsional)" class="input sm:col-span-1">
            <button type="submit" class="btn-primary h-[38px] sm:col-span-3 sm:w-fit">Simpan</button>
        </form>
    </div>

    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Nama Kegiatan</th><th>Pembina</th><th>Keterangan</th><th>Status</th><th class="th-aksi">Aksi</th></tr></thead>
                @forelse($ekstrakurikuler as $e)
                <tbody x-data="{ editing: false }">
                    <tr x-show="!editing">
                        <td class="font-semibold">{{ $e->nama_ekstrakurikuler }}</td>
                        <td>{{ $e->pembina->name ?? '—' }}</td>
                        <td class="text-slate-500">{{ $e->keterangan ?? '—' }}</td>
                        <td>
                            @if($e->is_aktif)
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">Aktif</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-400">Nonaktif</span>
                            @endif
                        </td>
                        <td class="td-aksi">
                            <div class="action-buttons">
                                <button type="button" @click="editing = true" class="btn-chip btn-chip-edit"><i class="fa-solid fa-pen mr-1.5"></i> Edit</button>
                                <form method="POST" action="{{ route('ekstrakurikuler.destroy', $e) }}" onsubmit="return confirm('Hapus kegiatan ekstrakurikuler ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-chip btn-chip-delete"><i class="fa-solid fa-trash mr-1.5"></i> Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr x-show="editing" x-cloak>
                        <td colspan="5" class="bg-brand-50/40">
                            <form method="POST" action="{{ route('ekstrakurikuler.update', $e) }}" class="grid sm:grid-cols-5 gap-3 items-end py-2">
                                @csrf @method('PUT')
                                <input type="text" name="nama_ekstrakurikuler" value="{{ $e->nama_ekstrakurikuler }}" required class="input sm:col-span-1">
                                <select name="pembina_id" class="input sm:col-span-1">
                                    <option value="">— Belum ditentukan —</option>
                                    @foreach($calonPembina as $u)
                                        <option value="{{ $u->id }}" @selected($e->pembina_id === $u->id)>{{ $u->name }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="keterangan" value="{{ $e->keterangan }}" placeholder="Keterangan" class="input sm:col-span-1">
                                <label class="flex items-center gap-2 text-sm sm:col-span-1">
                                    <input type="checkbox" name="is_aktif" value="1" @checked($e->is_aktif)> Aktif
                                </label>
                                <div class="flex gap-2 sm:col-span-1">
                                    <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                                    <button type="button" @click="editing = false" class="btn-outline h-[38px]">Batal</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                </tbody>
                @empty
                <tbody>
                    <tr><td colspan="5" class="text-center text-slate-400 py-8">Belum ada data.</td></tr>
                </tbody>
                @endforelse
            </table>
        </div>
        <div class="mt-4">{{ $ekstrakurikuler->links() }}</div>
    </div>
</div>
@endsection
