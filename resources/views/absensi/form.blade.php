@extends('layouts.app')
@section('title', 'Isi Absensi & Jurnal')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="card p-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-xs font-bold text-brand-600">{{ $jadwal->jamPelajaran->label }} &middot; {{ $jadwal->hari }}</p>
            <p class="text-lg font-extrabold text-slate-800">Kelas {{ $jadwal->kelas->nama_kelas }} - {{ $jadwal->mapel->nama_mapel }}</p>
        </div>
        <a href="{{ route('mengajar.index') }}" class="btn-outline">&larr; Kembali</a>
    </div>

    <form method="POST" action="{{ route('mengajar.store', $jadwal) }}" x-data="mengajarForm()">
        @csrf
        <div class="card p-5 mb-6">
            <p class="font-bold text-slate-800 mb-4">Jurnal Mengajar</p>
            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1">Tanggal</label>
                    <input type="date" name="tanggal" value="{{ old('tanggal', $tanggal) }}" required class="input">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-600 mb-1">Materi yang Diajarkan</label>
                <textarea name="materi" rows="2" required class="input" placeholder="Contoh: Persamaan Linear Satu Variabel">{{ old('materi', $jurnal->materi ?? '') }}</textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-600 mb-1">Kegiatan Pembelajaran</label>
                <textarea name="kegiatan" rows="2" class="input" placeholder="Contoh: Diskusi kelompok & latihan soal">{{ old('kegiatan', $jurnal->kegiatan ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-1">Keterangan (opsional)</label>
                <input type="text" name="keterangan" value="{{ old('keterangan', $jurnal->keterangan ?? '') }}" class="input" placeholder="Catatan tambahan">
            </div>
        </div>

        <div class="card p-5">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <p class="font-bold text-slate-800">Absensi Siswa ({{ $siswas->count() }} siswa)</p>
                <div class="flex gap-2 text-xs">
                    <button type="button" @click="setAll('Hadir')" class="px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 font-semibold hover:bg-emerald-100">Tandai Semua Hadir</button>
                </div>
            </div>

            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead>
                        <tr><th class="w-10">No</th><th>NIS</th><th>Nama Siswa</th><th class="text-right">Status Kehadiran</th></tr>
                    </thead>
                    <tbody>
                        @foreach($siswas as $i => $siswa)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $siswa->nis }}</td>
                            <td class="font-medium">{{ $siswa->nama }}</td>
                            <td>
                                <div class="flex justify-end gap-1.5 absen-row" x-data="{ status: '{{ old('absensi.' . $siswa->id, $absensiTersimpan[$siswa->id] ?? 'Hadir') }}' }">
                                    <template x-for="opt in ['Hadir','Sakit','Izin','Alfa']" :key="opt">
                                        <label :class="status === opt ? statusClass(opt) : 'bg-slate-100 text-slate-400 hover:bg-slate-200'"
                                               class="cursor-pointer px-2.5 py-1 rounded-lg text-xs font-bold transition select-none">
                                            <input type="radio" class="hidden" :value="opt" x-model="status" :name="'absensi[{{ $siswa->id }}]'">
                                            <span x-text="opt"></span>
                                        </label>
                                    </template>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-6">
            <a href="{{ route('mengajar.index') }}" class="btn-outline">Batal</a>
            <button type="submit" class="btn-primary">Simpan Absensi & Jurnal</button>
        </div>
    </form>
</div>

<script>
    function mengajarForm() {
        return {
            setAll(status) {
                document.querySelectorAll('.absen-row').forEach(row => {
                    Alpine.$data(row).status = status;
                });
            },
            statusClass(opt) {
                return {
                    Hadir: 'bg-emerald-500 text-white',
                    Sakit: 'bg-amber-500 text-white',
                    Izin: 'bg-blue-500 text-white',
                    Alfa: 'bg-red-500 text-white',
                }[opt];
            }
        }
    }
</script>
@endsection
