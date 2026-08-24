@extends('layouts.app')
@section('title', 'Jenis Surat')

@section('content')
<div class="space-y-6" x-data="{ showForm: false }">
    <div class="flex justify-between items-center flex-wrap gap-3">
        <p class="text-sm text-slate-500">Master jenis surat, dipakai bersama Kesiswaan &amp; BK saat membuat surat baru.</p>
        <div class="flex gap-2">
            <a href="{{ route('surat.index') }}" class="btn-outline">&larr; Daftar Surat</a>
            <button @click="showForm = !showForm" class="btn-primary">+ Tambah Jenis Surat</button>
        </div>
    </div>

    <div class="card p-5 no-print">
        <p class="font-bold text-slate-800 mb-2 text-sm">Placeholder yang bisa dipakai di template</p>
        <div class="flex flex-wrap gap-2">
            @foreach($placeholder as $kode => $label)
                <span class="px-2 py-1 rounded-lg bg-slate-100 text-slate-600 text-xs font-mono" title="{{ $label }}">{{ $kode }}</span>
            @endforeach
        </div>
        <p class="text-xs text-slate-400 mt-2">Contoh: "Sehubungan dengan ketidakhadiran {nama_siswa} ({kelas}) pada tanggal {tanggal}, kami mohon kehadiran Bapak/Ibu {nama_ortu} di sekolah."</p>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Jenis Surat</p>
        <form method="POST" action="{{ route('jenis-surat.store') }}" class="space-y-3">
            @csrf
            <input type="text" name="nama_jenis" placeholder="Nama Jenis Surat, contoh: Surat Panggilan Orang Tua" required class="input">
            <textarea name="template_isi" rows="5" placeholder="Template isi surat (opsional, bisa juga diisi manual tiap kali buat surat)..." class="input"></textarea>
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="space-y-3">
        @forelse($jenisSurat as $j)
        <div class="card p-5" x-data="{ editing: false }">
            <div x-show="!editing">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-bold text-slate-800">{{ $j->nama_jenis }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $j->surats_count }} surat sudah dibuat dengan jenis ini</p>
                        @if($j->template_isi)
                            <p class="text-sm text-slate-500 mt-2 whitespace-pre-line line-clamp-3">{{ $j->template_isi }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="button" @click="editing = true"
                                class="w-10 h-10 flex items-center justify-center rounded-lg text-brand-600 cursor-pointer hover:bg-brand-50 active:bg-brand-100 active:text-brand-700 transition"
                                title="Edit Jenis Surat">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form method="POST" action="{{ route('jenis-surat.destroy', $j) }}" onsubmit="return confirm('Hapus jenis surat ini?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="w-10 h-10 flex items-center justify-center rounded-lg text-red-500 cursor-pointer hover:bg-red-50 active:bg-red-100 active:text-red-700 transition"
                                    title="Hapus Jenis Surat">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div x-show="editing" x-cloak>
                <form method="POST" action="{{ route('jenis-surat.update', $j) }}" class="space-y-3">
                    @csrf @method('PUT')
                    <input type="text" name="nama_jenis" value="{{ $j->nama_jenis }}" required class="input">
                    <textarea name="template_isi" rows="5" class="input">{{ $j->template_isi }}</textarea>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                        <button type="button" @click="editing = false" class="btn-outline h-[38px]">Batal</button>
                    </div>
                </form>
            </div>
        </div>
        @empty
        <div class="card p-5 text-center text-slate-400 py-8">Belum ada jenis surat.</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $jenisSurat->links() }}</div>
</div>
@endsection
