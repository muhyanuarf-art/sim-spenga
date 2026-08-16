@extends('layouts.app')
@section('title', 'Tahun Ajaran')

@section('content')
<div class="space-y-6" x-data="{ showForm: false }">
    <div class="flex justify-end">
        <button @click="showForm = !showForm" class="btn-primary">+ Tambah Tahun Ajaran</button>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Tahun Ajaran</p>
        <form method="POST" action="{{ route('tahun-ajaran.store') }}" class="grid sm:grid-cols-3 gap-3 items-end">
            @csrf
            <input type="text" name="nama" placeholder="Contoh: 2026/2027" required class="input">
            <select name="semester" required class="input">
                <option value="Ganjil">Ganjil</option>
                <option value="Genap">Genap</option>
            </select>
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Tahun Ajaran</th><th>Semester</th><th>Status</th><th>Kunci</th><th class="th-aksi">Aksi</th></tr></thead>
                @forelse($tahunAjaran as $t)
                <tbody x-data="{ editing: false }">
                    <tr x-show="!editing">
                        <td class="font-semibold">{{ $t->nama }}</td>
                        <td>{{ $t->semester }}</td>
                        <td>
                            @if($t->is_active)<span class="badge bg-emerald-50 text-emerald-700">Aktif</span>
                            @else<span class="badge bg-slate-100 text-slate-500">Nonaktif</span>@endif
                        </td>
                        <td>
                            @if($t->isTerkunci())
                                <span class="badge bg-red-50 text-red-700" title="Dikunci {{ optional($t->terkunci_at)->translatedFormat('d M Y H:i') }} oleh {{ $t->terkunciOleh->name ?? '-' }}">🔒 Terkunci</span>
                            @else
                                <span class="badge bg-slate-100 text-slate-500">🔓 Terbuka</span>
                            @endif
                        </td>
                        <td class="td-aksi">
                            <div class="action-buttons">
                                <button type="button" @click="editing = true" class="btn-chip btn-chip-edit">✏️ Edit</button>
                                @unless($t->is_active)
                                <form method="POST" action="{{ route('tahun-ajaran.aktifkan', $t) }}">
                                    @csrf
                                    <button class="btn-chip btn-chip-success">✅ Aktifkan</button>
                                </form>
                                @endunless
                                @if($t->isTerkunci())
                                    @if(auth()->user()->isAdmin())
                                    <form method="POST" action="{{ route('tahun-ajaran.buka-kunci', $t) }}" onsubmit="return confirm('Buka kunci periode ini?')">
                                        @csrf
                                        <button class="btn-chip btn-chip-success">🔓 Buka Kunci</button>
                                    </form>
                                    @endif
                                @else
                                <form method="POST" action="{{ route('tahun-ajaran.kunci', $t) }}" onsubmit="return confirm('Kunci periode ini? Data pada modul yang dilindungi tidak akan bisa diubah selama periode ini masih aktif dan terkunci.')">
                                    @csrf
                                    <button class="btn-chip btn-chip-cancel">🔒 Kunci</button>
                                </form>
                                @endif
                                <form method="POST" action="{{ route('tahun-ajaran.destroy', $t) }}" onsubmit="return confirm('Hapus tahun ajaran ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-chip btn-chip-delete">🗑️ Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr x-show="editing" x-cloak>
                        <td colspan="5" class="bg-brand-50/40">
                            <form method="POST" action="{{ route('tahun-ajaran.update', $t) }}" class="grid sm:grid-cols-3 gap-3 items-end py-2">
                                @csrf @method('PUT')
                                <input type="text" name="nama" value="{{ $t->nama }}" required class="input">
                                <select name="semester" required class="input">
                                    <option value="Ganjil" {{ $t->semester === 'Ganjil' ? 'selected' : '' }}>Ganjil</option>
                                    <option value="Genap" {{ $t->semester === 'Genap' ? 'selected' : '' }}>Genap</option>
                                </select>
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
                    <tr><td colspan="5" class="text-center text-slate-400 py-8">Belum ada data.</td></tr>
                </tbody>
                @endforelse
            </table>
        </div>
    </div>
</div>
@endsection
