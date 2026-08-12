@extends('layouts.app')
@section('title', 'Data Pelanggaran (Master)')

@section('content')
<div class="space-y-6" x-data="{ showForm: false }">
    <p class="text-sm text-slate-400">Master jenis pelanggaran — supaya BK tidak perlu mengetik manual tiap kali lapor kasus. Poin harus sesuai rentang kategori.</p>

    <div class="flex justify-end">
        <button @click="showForm = !showForm" class="btn-primary">+ Tambah Jenis Pelanggaran</button>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Jenis Pelanggaran</p>
        <form method="POST" action="{{ route('bk.jenis-pelanggaran.store') }}" class="grid sm:grid-cols-5 gap-3 items-end">
            @csrf
            <input type="text" name="kode" placeholder="Kode (mis. R001)" required class="input">
            <input type="text" name="nama" placeholder="Nama Pelanggaran" required class="input sm:col-span-2">
            <select name="kategori" required class="input">
                @foreach($rentangKategori as $kat => [$min,$max])
                    <option value="{{ $kat }}">{{ $kat }} ({{ $min }}-{{ $max }})</option>
                @endforeach
            </select>
            <input type="number" name="poin_default" placeholder="Poin" required min="1" max="100" class="input">
            <button type="submit" class="btn-primary h-[38px] sm:col-span-5 sm:w-fit">Simpan</button>
        </form>
        @error('poin_default')<p class="text-xs text-rose-600 mt-2">{{ $message }}</p>@enderror
    </div>

    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Kode</th><th>Nama</th><th>Kategori</th><th>Poin Default</th><th>Status</th><th class="th-aksi">Aksi</th></tr></thead>
                <tbody>
                    @forelse($data as $j)
                    <tbody x-data="{ editing: false }">
                        <tr x-show="!editing">
                            <td class="font-mono text-xs">{{ $j->kode }}</td>
                            <td class="font-medium">{{ $j->nama }}</td>
                            <td><span class="badge bg-slate-100 text-slate-600">{{ $j->kategori }}</span></td>
                            <td>{{ $j->poin_default }}</td>
                            <td>
                                @if($j->is_active)
                                    <span class="badge bg-emerald-50 text-emerald-700">Aktif</span>
                                @else
                                    <span class="badge bg-slate-100 text-slate-400">Nonaktif</span>
                                @endif
                            </td>
                            <td class="td-aksi"><button @click="editing = true" class="btn-chip">Edit</button></td>
                        </tr>
                        <tr x-show="editing" x-cloak>
                            <td colspan="6" class="bg-brand-50/40">
                                <form method="POST" action="{{ route('bk.jenis-pelanggaran.update', $j) }}" class="grid sm:grid-cols-6 gap-3 items-end py-2">
                                    @csrf @method('PUT')
                                    <input type="text" name="kode" value="{{ $j->kode }}" required class="input">
                                    <input type="text" name="nama" value="{{ $j->nama }}" required class="input sm:col-span-2">
                                    <select name="kategori" required class="input">
                                        @foreach($rentangKategori as $kat => [$min,$max])
                                            <option value="{{ $kat }}" {{ $j->kategori === $kat ? 'selected' : '' }}>{{ $kat }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" name="poin_default" value="{{ $j->poin_default }}" required min="1" max="100" class="input">
                                    <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" {{ $j->is_active ? 'checked' : '' }} class="rounded"> Aktif
                                    </label>
                                    <div class="flex gap-2 sm:col-span-6">
                                        <button type="submit" class="btn-primary h-[36px]">Simpan</button>
                                        <button type="button" @click="editing=false" class="btn-outline h-[36px]">Batal</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    </tbody>
                    @empty
                    <tbody>
                    <tr><td colspan="6" class="text-center text-slate-400 py-8">Belum ada master jenis pelanggaran.</td></tr>
                    </tbody>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
