@extends('layouts.app')
@section('title', 'Kenaikan Kelas')

@section('content')
<div class="space-y-6" x-data="{ showPreview: false }">

    {{-- Langkah 1: pilih kelas asal --}}
    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-4">1. Pilih Kelas Asal</p>
        <form method="GET" action="{{ route('kenaikan-kelas.index') }}" class="flex flex-wrap gap-3 items-end">
            <div class="min-w-[220px]">
                <label class="block text-xs font-semibold text-slate-500 mb-1">Kelas Asal</label>
                <select name="kelas_asal_id" required class="input" onchange="this.form.submit()">
                    <option value="">Pilih kelas...</option>
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id }}" {{ $kelasAsal && $kelasAsal->id === $k->id ? 'selected' : '' }}>
                            {{ $k->nama_kelas }} (Tingkat {{ $k->tingkat }})
                        </option>
                    @endforeach
                </select>
            </div>
            <noscript><button class="btn-outline">Tampilkan</button></noscript>
        </form>
    </div>

    @if($kelasAsal)
    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-1">2. Proses Kenaikan Kelas — {{ $kelasAsal->nama_kelas }}</p>
        <p class="text-sm text-slate-500 mb-4">
            Centang siswa yang akan dinaikkan. Siswa yang tidak dicentang dianggap tinggal di kelas ini
            (tidak dipindahkan dan tidak dicatat pada riwayat).
        </p>

        <form method="POST" action="{{ route('kenaikan-kelas.store') }}"
              x-data="{ checkedCount: {{ $siswas->count() }}, confirmed: false }"
              @submit="if (! confirmed) { $event.preventDefault(); showPreview = true }">
            @csrf
            <input type="hidden" name="kelas_asal_id" value="{{ $kelasAsal->id }}">

            <div class="grid sm:grid-cols-3 gap-3 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Kelas Tujuan</label>
                    <select name="kelas_tujuan_id" required class="input">
                        <option value="">Pilih kelas tujuan...</option>
                        @foreach($kelasList as $k)
                            @if($k->id !== $kelasAsal->id)
                                <option value="{{ $k->id }}">{{ $k->nama_kelas }} (Tingkat {{ $k->tingkat }})</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Tahun Ajaran Tujuan</label>
                    <select name="tahun_ajaran_id" required class="input">
                        <option value="">Pilih tahun ajaran...</option>
                        @foreach($tahunAjaranList as $t)
                            <option value="{{ $t->id }}">{{ $t->labelPeriode() }}{{ $t->is_active ? ' (Aktif)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Keterangan (opsional)</label>
                    <input type="text" name="keterangan" placeholder="Contoh: Kenaikan kelas TA 2026/2027" class="input">
                </div>
            </div>

            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead>
                        <tr>
                            <th class="w-10">
                                <input type="checkbox" checked
                                       @change="document.querySelectorAll('.chk-siswa').forEach(c => { c.checked = $event.target.checked }); checkedCount = $event.target.checked ? {{ $siswas->count() }} : 0">
                            </th>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>L/P</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($siswas as $s)
                        <tr>
                            <td>
                                <input type="checkbox" class="chk-siswa" name="siswa_ids[]" value="{{ $s->id }}" checked
                                       @change="checkedCount += $event.target.checked ? 1 : -1">
                            </td>
                            <td>{{ $s->nis }}</td>
                            <td class="font-medium">{{ $s->nama }}</td>
                            <td>{{ $s->jenis_kelamin }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-slate-400 py-8">Tidak ada siswa aktif di kelas ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($siswas->isNotEmpty())
            <div class="mt-4 flex justify-end">
                <button type="submit" class="btn-primary" :disabled="checkedCount < 1">
                    Proses Kenaikan Kelas (<span x-text="checkedCount"></span> siswa)
                </button>
            </div>
            @endif

            {{-- Modal preview WAJIB sebelum submit sungguhan --}}
            <div x-show="showPreview" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                 @keydown.escape.window="showPreview = false">
                <div class="card w-full max-w-md p-6" @click.outside="showPreview = false">
                    <p class="font-bold text-slate-800 text-lg mb-2">Konfirmasi Kenaikan Kelas</p>
                    <p class="text-sm text-slate-600 mb-4">
                        Anda akan memindahkan <span class="font-bold" x-text="checkedCount"></span> siswa
                        dari kelas <span class="font-bold">{{ $kelasAsal->nama_kelas }}</span> ke kelas tujuan
                        yang dipilih. Siswa yang tidak dicentang akan tetap tinggal di kelas ini.
                        Aksi ini akan tercatat di Riwayat Kelas masing-masing siswa dan tidak dapat dibatalkan
                        lewat aplikasi.
                    </p>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn-outline" @click="showPreview = false">Batal</button>
                        <button type="submit" class="btn-primary" @click="confirmed = true; showPreview = false">Ya, Proses Sekarang</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection
