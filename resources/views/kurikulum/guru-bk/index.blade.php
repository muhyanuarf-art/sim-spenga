@extends('layouts.app')
@section('title', 'Mapping Guru BK')

@section('content')
<div class="space-y-6" x-data="{ showForm: false }">

    @if(!$tahunAjaran)
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ Aktifkan Tahun Ajaran terlebih dahulu sebelum menambah mapping.
        </div>
    @endif

    @if($guruBkList->isEmpty())
        <div class="rounded-xl bg-sky-50 border border-sky-200 text-sky-700 px-4 py-3 text-sm">
            ℹ️ Belum ada pengguna dengan role <b>Guru BK</b>. Tambahkan dulu di menu
            <a href="{{ route('users.index') }}" class="underline font-semibold">Kelola Pengguna</a>
            (pilih role "Guru BK"), baru bisa di-mapping ke kelas di sini.
        </div>
    @endif

    <div class="flex items-center justify-between flex-wrap gap-3">
        <p class="text-sm text-slate-400">Tentukan kelas mana saja yang dipantau tiap Guru BK.</p>
        <button @click="showForm = !showForm" class="btn-primary">+ Tambah Mapping</button>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Mapping Guru BK</p>
        <form method="POST" action="{{ route('kurikulum.guru-bk.store') }}" class="grid sm:grid-cols-3 gap-3 items-end">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Guru BK</label>
                <select name="guru_id" required class="input">
                    <option value="">Pilih Guru BK</option>
                    @foreach($guruBkList as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Kelas</label>
                <select name="kelas_id" required class="input">
                    <option value="">Pilih Kelas</option>
                    @foreach($kelasList as $k)<option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>@endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="card p-5">
        <form method="GET" class="flex flex-wrap gap-3 mb-4">
            <select name="guru_id" class="input max-w-[220px]" onchange="this.form.submit()">
                <option value="">Semua Guru BK</option>
                @foreach($guruBkList as $g)<option value="{{ $g->id }}" {{ request('guru_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>@endforeach
            </select>
        </form>

        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Guru BK</th><th>Kelas</th><th class="th-aksi">Aksi</th></tr></thead>
                <tbody>
                    @forelse($data as $d)
                    <tr>
                        <td class="font-medium">
                            <div class="flex items-center gap-2">
                                <x-initial-avatar :nama="$d->guru->name ?? '-'" />
                                {{ $d->guru->name ?? '-' }}
                            </div>
                        </td>
                        <td><x-kelas-badge :nama="$d->kelas->nama_kelas ?? '-'" /></td>
                        <td class="td-aksi">
                            <form method="POST" action="{{ route('kurikulum.guru-bk.destroy', $d) }}" onsubmit="return confirm('Hapus mapping ini?')">
                                @csrf @method('DELETE')
                                <button class="btn-chip btn-chip-delete">🗑️ Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center text-slate-400 py-8">Belum ada mapping Guru BK.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $data->links() }}</div>
    </div>
</div>
@endsection
