@extends('layouts.app')
@section('title', 'Kalender Libur')

@section('content')
<div class="space-y-6" x-data="{ showForm: false }">

    <div class="flex justify-between items-start flex-wrap gap-3">
        <p class="text-sm text-slate-500 max-w-2xl">
            Tanggal-tanggal yang tidak ada kegiatan belajar mengajar. Pengingat WhatsApp tidak dikirim pada hari itu,
            dan Rekapitulasi Kepatuhan tidak menghitungnya sebagai jurnal yang belum diisi.
        </p>
        <div class="flex gap-2 items-center">
            <form method="GET" action="{{ route('hari-libur.index') }}">
                <select name="tahun" onchange="this.form.submit()" class="input h-[38px] py-0">
                    @foreach($daftarTahun as $t)
                        <option value="{{ $t }}" @selected($t == $tahun)>Tahun {{ $t }}</option>
                    @endforeach
                </select>
            </form>
            <button @click="showForm = !showForm" class="btn-primary">+ Tambah Libur</button>
        </div>
    </div>

    <div class="card p-5 no-print bg-amber-50 border-amber-200">
        <div class="flex gap-3">
            <i class="fa-solid fa-circle-info text-amber-600 mt-0.5"></i>
            <div class="text-sm text-amber-900 space-y-1">
                <p class="font-bold">Hari Minggu tidak perlu dicatat di sini.</p>
                <p>Hari Minggu sudah dilewati sejak awal, begitu pula hari yang memang tidak punya jadwal pelajaran
                   sama sekali. Yang perlu dicatat adalah libur yang jatuh pada <strong>hari mengajar</strong> —
                   tanggal merah, cuti bersama, libur semester, atau libur mendadak dari sekolah.</p>
            </div>
        </div>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Libur</p>
        <form method="POST" action="{{ route('hari-libur.store') }}" class="space-y-3">
            @csrf
            <div class="grid md:grid-cols-2 gap-3">
                <div>
                    <label class="label">Tanggal mulai</label>
                    <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai') }}" required class="input">
                </div>
                <div>
                    <label class="label">Tanggal selesai</label>
                    <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}" required class="input">
                    <p class="text-xs text-slate-400 mt-1">Untuk libur sehari, isi sama dengan tanggal mulai.</p>
                </div>
            </div>
            <div>
                <label class="label">Keterangan</label>
                <input type="text" name="keterangan" value="{{ old('keterangan') }}" required maxlength="150"
                       placeholder="Contoh: Libur Idul Fitri, Cuti Bersama, Libur Semester Ganjil" class="input">
            </div>
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="space-y-3">
        @forelse($libur as $l)
        <div class="card p-5" x-data="{ editing: false }">

            <div x-show="!editing">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-bold text-slate-800">{{ $l->keterangan }}</p>
                            @if($l->sedangBerlangsung())
                                <span class="badge bg-emerald-50 text-emerald-700">Sedang berlangsung</span>
                            @elseif($l->sudahLewat())
                                <span class="badge bg-slate-100 text-slate-400">Sudah lewat</span>
                            @else
                                <span class="badge bg-blue-50 text-blue-700">Akan datang</span>
                            @endif
                            @unless($l->sehari())
                                <span class="badge bg-violet-50 text-violet-700">{{ $l->jumlahHari() }} hari</span>
                            @endunless
                        </div>
                        <p class="text-sm text-slate-500 mt-1">{{ $l->label() }}</p>
                        @if($l->pembuat)
                            <p class="text-xs text-slate-400 mt-0.5">Dicatat oleh {{ $l->pembuat->name }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="button" @click="editing = true"
                                class="w-10 h-10 flex items-center justify-center rounded-lg text-brand-600 cursor-pointer hover:bg-brand-50 active:bg-brand-100 active:text-brand-700 transition"
                                title="Ubah">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form method="POST" action="{{ route('hari-libur.destroy', $l) }}"
                              data-konfirmasi="Hapus libur {{ $l->keterangan }}?{{ "\n\n" }}Setelah dihapus, tanggal itu kembali dianggap hari mengajar: pengingat WhatsApp berjalan lagi dan rekap kepatuhan menghitungnya.">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-10 h-10 flex items-center justify-center rounded-lg text-rose-600 cursor-pointer hover:bg-rose-50 active:bg-rose-100 transition"
                                    title="Hapus">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div x-show="editing" x-cloak>
                <form method="POST" action="{{ route('hari-libur.update', $l) }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <div class="grid md:grid-cols-2 gap-3">
                        <div>
                            <label class="label">Tanggal mulai</label>
                            <input type="date" name="tanggal_mulai" value="{{ $l->tanggal_mulai->toDateString() }}" required class="input">
                        </div>
                        <div>
                            <label class="label">Tanggal selesai</label>
                            <input type="date" name="tanggal_selesai" value="{{ $l->tanggal_selesai->toDateString() }}" required class="input">
                        </div>
                    </div>
                    <div>
                        <label class="label">Keterangan</label>
                        <input type="text" name="keterangan" value="{{ $l->keterangan }}" required maxlength="150" class="input">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                        <button type="button" @click="editing = false" class="btn-outline h-[38px]">Batal</button>
                    </div>
                </form>
            </div>

        </div>
        @empty
        <div class="card p-10 text-center">
            <i class="fa-solid fa-calendar-xmark text-3xl text-slate-300"></i>
            <p class="font-bold text-slate-700 mt-3">Belum ada libur yang dicatat untuk tahun {{ $tahun }}</p>
            <p class="text-sm text-slate-500 mt-1 max-w-lg mx-auto">
                Selama kosong, setiap hari mengajar dianggap hari efektif, termasuk tanggal merah.
                Tambahkan liburnya sebelum tanggal itu tiba agar pengingat tidak terkirim kepada guru.
            </p>
        </div>
        @endforelse
    </div>

</div>
@endsection
