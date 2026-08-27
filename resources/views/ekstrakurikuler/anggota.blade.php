@extends('layouts.app')
@section('title', 'Anggota — ' . $ekstrakurikuler->nama_ekstrakurikuler)

@section('deskripsi', $ekstrakurikuler->anggotas->count() . ' siswa terdaftar · Pembina: ' . $ekstrakurikuler->daftarNamaPembina())

@section('aksi')
    <a href="{{ route('ekstrakurikuler.index') }}" class="btn-outline">&larr; Kembali ke Ekstrakurikuler</a>
@endsection

@section('content')
<div class="space-y-6">
    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-1">Tambah/Edit Anggota per Kelas</p>
        <p class="text-xs text-slate-500 mb-3">Pilih kelas, centang siswa yang ikut kegiatan ini, lalu Simpan. Yang sudah tercentang berarti sudah jadi anggota — hapus centang untuk mengeluarkannya (berguna kalau terlanjur salah simpan).</p>

        <form method="GET" class="mb-4">
            <label class="block text-xs font-semibold text-slate-500 mb-1">Kelas</label>
            <select name="kelas_id" class="input sm:w-64" onchange="this.form.submit()">
                @forelse($kelasList as $k)
                    <option value="{{ $k->id }}" {{ $kelasDipilih && $kelasDipilih->id === $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                @empty
                    <option value="">Belum ada data kelas</option>
                @endforelse
            </select>
        </form>

        @if($kelasDipilih)
            <form method="POST" action="{{ route('ekstrakurikuler.anggota.sync-kelas', $ekstrakurikuler) }}">
                @csrf
                <input type="hidden" name="kelas_id" value="{{ $kelasDipilih->id }}">

                <div class="flex items-center justify-between mb-2">
                    <p class="text-sm font-semibold text-slate-600">Siswa Kelas {{ $kelasDipilih->nama_kelas }} ({{ $siswaKelas->count() }})</p>
                    <button type="button"
                            onclick="document.querySelectorAll('.cb-siswa-kelas').forEach(el => el.checked = true)"
                            class="px-3 py-1.5 rounded-lg bg-brand-50 text-brand-700 font-semibold text-xs hover:bg-brand-100">
                        <i class="fa-solid fa-users mr-1"></i> 1 Kelas Ikut Semua
                    </button>
                </div>

                <div class="border border-slate-200 rounded-lg divide-y divide-slate-100 max-h-96 overflow-y-auto">
                    @forelse($siswaKelas as $siswa)
                        <label class="flex items-center gap-3 px-3 py-2 text-sm hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" name="siswa_id[]" value="{{ $siswa->id }}" class="cb-siswa-kelas"
                                   {{ in_array($siswa->id, $idAnggotaSaatIni->all()) ? 'checked' : '' }}>
                            <span class="font-medium">{{ $siswa->nama }}</span>
                            <span class="text-slate-400 text-xs">{{ $siswa->nis }}</span>
                        </label>
                    @empty
                        <p class="text-xs text-slate-400 px-3 py-3">Tidak ada siswa aktif di kelas ini.</p>
                    @endforelse
                </div>

                <div class="flex gap-2 mt-3">
                    <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                    <a href="{{ route('ekstrakurikuler.anggota.index', ['ekstrakurikuler' => $ekstrakurikuler, 'kelas_id' => $kelasDipilih->id]) }}" class="btn-outline h-[38px]">Batal</a>
                </div>
            </form>
        @else
            <p class="text-xs text-slate-400">Belum ada data kelas untuk dipilih.</p>
        @endif
    </div>

    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-1">Tambah Individual (lintas kelas)</p>
        <p class="text-xs text-slate-500 mb-3">Untuk menambah 1-2 siswa saja tanpa lewat checklist per kelas di atas.</p>
        <form method="GET" class="flex gap-2 mb-3">
            @if($kelasDipilih)<input type="hidden" name="kelas_id" value="{{ $kelasDipilih->id }}">@endif
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
        <p class="font-bold text-slate-800 mb-3">Daftar Anggota Saat Ini</p>
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
