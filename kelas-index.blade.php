@extends('layouts.app')
@section('title', 'Data Kelas')

@section('content')
<div class="space-y-6" x-data="{ showForm: false }">
    <div class="flex justify-end gap-2">
        <a href="{{ route('kelas.import.form') }}" class="btn-outline">📥 Import Excel</a>
        <button @click="showForm = !showForm" class="btn-primary">+ Tambah Kelas</button>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Kelas</p>
        <form method="POST" action="{{ route('kelas.store') }}" class="grid sm:grid-cols-4 gap-3 items-end">
            @csrf
            <input type="text" name="nama_kelas" placeholder="Contoh: 7A" required class="input">
            <select name="tingkat" required class="input">
                <option value="7">Tingkat 7</option>
                <option value="8">Tingkat 8</option>
                <option value="9">Tingkat 9</option>
            </select>
            <select name="wali_kelas_id" class="input">
                <option value="">Wali Kelas (opsional)</option>
                @foreach($guruList as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
            </select>
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Kelas</th><th>Tingkat</th><th>Wali Kelas</th><th>Jumlah Siswa</th><th class="th-aksi">Aksi</th></tr></thead>
                @forelse($kelas as $k)
                <tbody x-data="{ editing: false }">
                    <tr x-show="!editing">
                        <td class="font-semibold">{{ $k->nama_kelas }}</td>
                        <td>{{ $k->tingkat }}</td>
                        <td>{{ $k->waliKelas->name ?? '-' }}</td>
                        <td>{{ $k->siswas_count }}</td>
                        <td class="td-aksi">
                            <div class="action-buttons">
                                <button type="button" @click="editing = true" class="btn-chip btn-chip-edit">✏️ Edit</button>
                                <form method="POST" action="{{ route('kelas.destroy', $k) }}" onsubmit="return confirm('Hapus kelas ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-chip btn-chip-delete">🗑️ Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr x-show="editing" x-cloak>
                        <td colspan="5" class="bg-brand-50/40">
                            <form method="POST" action="{{ route('kelas.update', $k) }}" class="grid sm:grid-cols-5 gap-3 items-end py-2">
                                @csrf @method('PUT')
                                <input type="text" name="nama_kelas" value="{{ $k->nama_kelas }}" required class="input">
                                <select name="tingkat" required class="input">
                                    @foreach([7,8,9] as $t)
                                        <option value="{{ $t }}" {{ $k->tingkat == $t ? 'selected' : '' }}>Tingkat {{ $t }}</option>
                                    @endforeach
                                </select>
                                <select name="wali_kelas_id" class="input">
                                    <option value="">Wali Kelas (opsional)</option>
                                    @foreach($guruList as $g)
                                        <option value="{{ $g->id }}" {{ $k->wali_kelas_id == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                                <button type="button" @click="editing = false" class="btn-outline h-[38px]">Batal</button>
                            </form>
                        </td>
                    </tr>
                </tbody>
                @empty
                <tbody>
                    <tr><td colspan="5" class="text-center text-slate-400 py-8">Belum ada data kelas.</td></tr>
                </tbody>
                @endforelse
            </table>
        </div>
        <div class="mt-4">{{ $kelas->links() }}</div>
    </div>
</div>
@endsection
