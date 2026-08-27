@extends('layouts.app')
@section('title', 'Isi Absensi Kegiatan')
@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="card p-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <span class="badge bg-brand-50 text-brand-700 mb-2">{{ $kegiatan->jenisLabel() }}</span>
            <p class="text-lg font-extrabold text-slate-800">{{ $kegiatan->nama }}</p>
            <p class="text-sm text-slate-500">
                Kelas {{ $kelas->nama_kelas }} &middot; {{ \Illuminate\Support\Carbon::parse($tanggal)->translatedFormat('l, d F Y') }}
            </p>
            @if($kegiatan->keterangan)
                <p class="text-xs text-slate-400 mt-1">{{ $kegiatan->keterangan }}</p>
            @endif
        </div>
        <a href="{{ route('kegiatan.absensi.pilih', ['tanggal' => $tanggal]) }}" class="btn-outline">&larr; Kembali</a>
    </div>

    @if($kegiatan->kirim_wa_alfa)
        <div class="alert alert-info">
            <i class="fa-solid fa-comment-sms mt-0.5"></i>
            <span class="flex-1">
                Siswa yang ditandai <b>Alfa</b> akan otomatis dikirimi notifikasi WhatsApp ke orang tua,
                sama seperti absensi pada hari KBM biasa.
                @if(! \Illuminate\Support\Carbon::parse($tanggal)->isToday())
                    <b>Kecuali untuk pengisian ini</b> — tanggalnya sudah lewat, jadi WhatsApp sengaja tidak dikirim
                    (kejadian Alfa tetap tercatat).
                @endif
            </span>
        </div>
    @else
        <div class="alert alert-warning">
            <i class="fa-solid fa-comment-slash mt-0.5"></i>
            <span class="flex-1">Kegiatan ini diatur <b>tanpa notifikasi WhatsApp</b>. Status Alfa tetap tercatat di rekap absensi.</span>
        </div>
    @endif

    @if($absensiKegiatan)
        <div class="alert alert-success">
            <i class="fa-solid fa-clock-rotate-left mt-0.5"></i>
            <span class="flex-1">
                Absensi ini sudah pernah diisi
                @if($absensiKegiatan->diisiOleh) oleh {{ $absensiKegiatan->diisiOleh->name }} @endif
                pada {{ $absensiKegiatan->updated_at->translatedFormat('d M Y H:i') }}. Menyimpan lagi akan memperbaruinya.
            </span>
        </div>
    @endif

    <form method="POST" action="{{ route('kegiatan.absensi.store', ['kegiatan' => $kegiatan, 'kelas' => $kelas]) }}"
          x-data="kegiatanAbsensiForm()">
        @csrf
        <input type="hidden" name="tanggal" value="{{ $tanggal }}">

        <div class="card p-5">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <p class="font-bold text-slate-800">Absensi Siswa ({{ $siswas->count() }} siswa)</p>
                <button type="button" @click="setAll('Hadir')"
                        class="px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-semibold hover:bg-emerald-100">
                    Tandai Semua Hadir
                </button>
            </div>

            @if($siswas->isEmpty())
                <p class="empty-state">Belum ada siswa aktif di kelas ini pada tanggal tersebut.</p>
            @else
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
                                    <div class="flex justify-end gap-1.5 absen-row"
                                         x-data="{ status: '{{ old('absensi.' . $siswa->id, $absensiTersimpan[$siswa->id] ?? 'Hadir') }}' }">
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
            @endif

            <div class="mt-5">
                <label class="label">Catatan Kegiatan (opsional)</label>
                <textarea name="catatan" rows="2" class="input"
                          placeholder="Contoh: Lomba kebersihan kelas, siswa berkumpul di lapangan pukul 07.00.">{{ old('catatan', $absensiKegiatan->catatan ?? '') }}</textarea>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-6">
            <a href="{{ route('kegiatan.absensi.pilih', ['tanggal' => $tanggal]) }}" class="btn-outline">Batal</a>
            <button type="submit" class="btn-primary" @if($siswas->isEmpty()) disabled @endif>
                <i class="fa-solid fa-floppy-disk"></i> Simpan Absensi Kegiatan
            </button>
        </div>
    </form>
</div>

<script>
    function kegiatanAbsensiForm() {
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
