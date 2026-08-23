@extends('layouts.app')
@section('title', 'Isi Absensi Ekstrakurikuler')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="card p-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-xs font-bold text-brand-600">Absensi Ekstrakurikuler</p>
            <p class="text-lg font-extrabold text-slate-800">{{ $ekstrakurikuler->nama_ekstrakurikuler }}</p>
        </div>
        @php $kembaliKe = in_array(auth()->user()->role, ['kesiswaan', 'admin']) ? route('ekstrakurikuler.index') : route('ekstrakurikuler.absensi.pilih'); @endphp
        <a href="{{ $kembaliKe }}" class="btn-outline">&larr; Kembali</a>
    </div>

    <form method="POST" action="{{ route('ekstrakurikuler.absensi.store', $ekstrakurikuler) }}" x-data="ekskulAbsensiForm()">
        @csrf
        <div class="card p-5 mb-6">
            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1">Tanggal</label>
                    <input type="date" name="tanggal" value="{{ old('tanggal', $tanggal) }}" required class="input"
                           onchange="location.href = '{{ route('ekstrakurikuler.absensi.form', $ekstrakurikuler) }}?tanggal=' + this.value">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1">Kegiatan (opsional)</label>
                    <input type="text" name="kegiatan" value="{{ old('kegiatan', $sesi->kegiatan ?? '') }}" placeholder="Contoh: Latihan baris-berbaris" class="input">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-1">Keterangan (opsional)</label>
                <input type="text" name="keterangan" value="{{ old('keterangan', $sesi->keterangan ?? '') }}" class="input">
            </div>
        </div>

        <div class="card p-5 mb-6">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <p class="font-bold text-slate-800">Absensi Pembina ({{ $pembinaList->count() }} orang)</p>
                <button type="button" @click="setAll('pembina', 'Hadir')" class="px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 font-semibold text-xs hover:bg-emerald-100">Tandai Semua Hadir</button>
            </div>
            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead><tr><th class="w-10">No</th><th>Nama</th><th>Jenis</th><th class="text-right">Status Kehadiran</th></tr></thead>
                    <tbody>
                    @forelse($pembinaList as $i => $p)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="font-medium">{{ $p->namaTampil() }}</td>
                            <td>
                                @if($p->isEksternal())
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700">Luar sekolah</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-brand-50 text-brand-700">Sekolah</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex justify-end gap-1.5 absen-row-pembina" x-data="{ status: '{{ old('pembina.' . $p->id, $statusPembina[$p->id] ?? 'Hadir') }}' }">
                                    <template x-for="opt in ['Hadir','Sakit','Izin','Alfa']" :key="opt">
                                        <label :class="status === opt ? statusClass(opt) : 'bg-slate-100 text-slate-400 hover:bg-slate-200'"
                                               class="cursor-pointer px-2.5 py-1 rounded-lg text-xs font-bold transition select-none">
                                            <input type="radio" class="hidden" :value="opt" x-model="status" :name="'pembina[{{ $p->id }}]'">
                                            <span x-text="opt"></span>
                                        </label>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-slate-400 py-6">Belum ada pembina tercatat untuk kegiatan ini.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-5">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <p class="font-bold text-slate-800">Absensi Siswa ({{ $siswaList->count() }} siswa)</p>
                <button type="button" @click="setAll('siswa', 'Hadir')" class="px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 font-semibold text-xs hover:bg-emerald-100">Tandai Semua Hadir</button>
            </div>
            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead><tr><th class="w-10">No</th><th>NIS</th><th>Nama Siswa</th><th class="text-right">Status Kehadiran</th></tr></thead>
                    <tbody>
                    @forelse($siswaList as $i => $siswa)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $siswa->nis }}</td>
                            <td class="font-medium">{{ $siswa->nama }}</td>
                            <td>
                                <div class="flex justify-end gap-1.5 absen-row-siswa" x-data="{ status: '{{ old('siswa.' . $siswa->id, $statusSiswa[$siswa->id] ?? 'Hadir') }}' }">
                                    <template x-for="opt in ['Hadir','Sakit','Izin','Alfa']" :key="opt">
                                        <label :class="status === opt ? statusClass(opt) : 'bg-slate-100 text-slate-400 hover:bg-slate-200'"
                                               class="cursor-pointer px-2.5 py-1 rounded-lg text-xs font-bold transition select-none">
                                            <input type="radio" class="hidden" :value="opt" x-model="status" :name="'siswa[{{ $siswa->id }}]'">
                                            <span x-text="opt"></span>
                                        </label>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-slate-400 py-6">Belum ada anggota siswa untuk kegiatan ini. <a href="{{ route('ekstrakurikuler.anggota.index', $ekstrakurikuler) }}" class="text-brand-600 font-semibold">Tambah anggota</a>.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-6">
            <a href="{{ $kembaliKe }}" class="btn-outline">Batal</a>
            <button type="submit" class="btn-primary">Simpan Absensi</button>
        </div>
    </form>
</div>

<script>
    function ekskulAbsensiForm() {
        return {
            setAll(jenis, status) {
                document.querySelectorAll('.absen-row-' + jenis).forEach(row => {
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
