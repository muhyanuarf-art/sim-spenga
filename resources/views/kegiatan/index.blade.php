@extends('layouts.app')
@section('title', 'Kegiatan Sekolah')

@section('aksi')
    @if($bolehKelola)
        <button type="button" onclick="document.getElementById('form-tambah').classList.toggle('hidden')" class="btn-primary">
            <i class="fa-solid fa-plus"></i> Jadwalkan Kegiatan
        </button>
    @endif
@endsection

@section('content')
@php
    $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
@endphp

<div class="space-y-6">

    <div class="alert alert-info">
        <i class="fa-solid fa-circle-info mt-0.5"></i>
        <span class="flex-1">
            Kegiatan di luar jam KBM (lomba, tryout &amp; asesmen, classmeeting, pesantren Ramadan, dsb) dijadwalkan di sini.
            Pada hari kegiatan, absensi siswa <b>hanya diisi oleh Wali Kelas</b> lewat menu
            <b>Absensi Kegiatan Sekolah</b> — dan notifikasi WhatsApp untuk siswa Alfa tetap berjalan otomatis.
        </span>
    </div>

    @if(!$tahunAjaran)
        <div class="alert alert-warning">
            <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
            <span class="flex-1">Belum ada Tahun Ajaran aktif. Aktifkan periode lebih dulu sebelum menjadwalkan kegiatan.</span>
        </div>
    @endif

    {{-- ===== Form tambah ===== --}}
    @if($bolehKelola)
        <div id="form-tambah" class="{{ $errors->any() && old('nama') ? '' : 'hidden' }}">
            <x-panel judul="Jadwalkan Kegiatan Baru" ikon="fa-calendar-plus">
                <form method="POST" action="{{ route('kegiatan.store') }}" x-data="{ cakupan: '{{ old('cakupan', 'semua') }}' }">
                    @csrf
                    @include('kegiatan.partials.form-kegiatan', [
                        'kegiatan' => null, 'kelasList' => $kelasList, 'tingkatList' => $tingkatList, 'hariList' => $hariList,
                    ])
                    <div class="flex justify-end gap-2 mt-5">
                        <button type="button" onclick="document.getElementById('form-tambah').classList.add('hidden')" class="btn-outline">Batal</button>
                        <button class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan Kegiatan</button>
                    </div>
                </form>
            </x-panel>
        </div>
    @endif

    {{-- ===== Filter ===== --}}
    <div class="flex items-center gap-2 flex-wrap">
        @foreach(['berjalan' => 'Berjalan & akan datang', 'selesai' => 'Sudah selesai', 'semua' => 'Semua'] as $key => $label)
            <a href="{{ route('kegiatan.index', ['status' => $key]) }}"
               class="btn-chip {{ $filter === $key ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- ===== Daftar kegiatan ===== --}}
    @if($kegiatanList->isEmpty())
        <div class="card p-10 text-center">
            <div class="text-3xl text-slate-300 mb-2"><i class="fa-solid fa-calendar-day"></i></div>
            <p class="font-semibold text-slate-700">Belum ada kegiatan pada filter ini</p>
            <p class="text-sm text-slate-400 mt-1">Gunakan tombol "Jadwalkan Kegiatan" untuk menambahkan.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($kegiatanList as $k)
                <div class="card p-5" x-data="{ edit: false, cakupan: '{{ $k->cakupan }}' }">
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <span class="badge {{ $k->statusBadgeClass() }}">{{ $k->statusLabel() }}</span>
                                <span class="badge bg-slate-100 text-slate-600">{{ $k->jenisLabel() }}</span>
                                @unless($k->kirim_wa_alfa)
                                    <span class="badge bg-amber-50 text-amber-700"><i class="fa-solid fa-comment-slash mr-1"></i> Tanpa WA</span>
                                @endunless
                            </div>
                            <p class="text-lg font-bold text-slate-800">{{ $k->nama }}</p>
                            <p class="text-sm text-slate-500">
                                <i class="fa-solid fa-calendar-days w-4 text-slate-400"></i> {{ $k->rentangLabel() }}
                                @if($k->hari_aktif)
                                    &middot; hanya {{ implode(', ', $k->hari_aktif) }}
                                @endif
                            </p>
                            <p class="text-sm text-slate-500">
                                <i class="fa-solid fa-school w-4 text-slate-400"></i> {{ $k->cakupanLabel() }}
                            </p>
                            @if($k->keterangan)
                                <p class="text-xs text-slate-400 mt-1">{{ $k->keterangan }}</p>
                            @endif
                        </div>

                        <div class="flex flex-col items-end gap-2">
                            <div class="text-right">
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pengisian Absensi</p>
                                <p class="text-xl font-extrabold text-slate-800">{{ $k->progres_persen }}%</p>
                                <p class="text-xs text-slate-400">{{ $k->progres_terisi }} dari {{ $k->progres_target }} (kelas × hari)</p>
                            </div>
                            <div class="w-40 h-1.5 rounded-full bg-slate-200 overflow-hidden">
                                <div class="h-full rounded-full {{ $k->progres_persen >= 100 ? 'bg-emerald-500' : ($k->progres_persen > 0 ? 'bg-amber-500' : 'bg-slate-300') }}"
                                     style="width: {{ max($k->progres_persen, 2) }}%"></div>
                            </div>
                            <div class="action-buttons">
                                <a href="{{ route('kegiatan.show', $k) }}" class="btn-chip btn-chip-edit"><i class="fa-solid fa-eye"></i> Detail</a>
                                @if($bolehKelola)
                                    <button type="button" @click="edit = !edit" class="btn-chip btn-chip-cancel"><i class="fa-solid fa-pen"></i> Ubah</button>
                                    <form method="POST" action="{{ route('kegiatan.destroy', $k) }}"
                                          data-konfirmasi="Hapus kegiatan {{ $k->nama }}? Tindakan ini tidak dapat dibatalkan.">
                                        @csrf @method('DELETE')
                                        <button class="btn-chip btn-chip-delete"><i class="fa-solid fa-trash"></i> Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($bolehKelola)
                        <div x-show="edit" x-cloak x-collapse class="mt-5 pt-5 border-t border-slate-100">
                            <form method="POST" action="{{ route('kegiatan.update', $k) }}">
                                @csrf @method('PUT')
                                @include('kegiatan.partials.form-kegiatan', [
                                    'kegiatan' => $k, 'kelasList' => $kelasList, 'tingkatList' => $tingkatList, 'hariList' => $hariList,
                                ])
                                <div class="flex justify-end gap-2 mt-5">
                                    <button type="button" @click="edit = false" class="btn-outline">Batal</button>
                                    <button class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
