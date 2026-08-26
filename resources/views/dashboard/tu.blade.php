@extends('layouts.app')
@section('title', 'Dashboard')
@section('deskripsi', 'Ruang kerja Tata Usaha: pengelolaan master Jenis Surat.')

@section('content')
<div class="space-y-6">

    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
        <x-stat-card color="brand" icon="fa-tags" label="Jenis Surat" :value="$totalJenis"
                     hint="Seluruh jenis yang terdaftar" :href="route('jenis-surat.index')" />
        <x-stat-card color="emerald" icon="fa-circle-check" label="Jenis Aktif" :value="$totalJenisAktif"
                     :suffix="'/ '.$totalJenis" hint="Bisa dipilih saat membuat surat" />
        <x-stat-card :color="$totalJenis - $totalJenisAktif > 0 ? 'slate' : 'emerald'" icon="fa-box-archive"
                     label="Jenis Nonaktif" :value="$totalJenis - $totalJenisAktif" />
    </div>

    <x-panel judul="Jenis Surat Terbaru" ikon="fa-tags"
             deskripsi="Master jenis surat beserta kode penomoran dan jumlah pemakaiannya." rapat>
        <x-slot:aksi>
            <a href="{{ route('jenis-surat.index') }}" class="btn-primary"><i class="fa-solid fa-pen"></i> Kelola Jenis Surat</a>
        </x-slot:aksi>

        <div class="overflow-x-auto">
            <table class="table-clean">
                <thead>
                    <tr><th>Nama Jenis</th><th>Kode</th><th>Kategori</th><th class="text-center">Dipakai</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse($jenisTerbaru as $j)
                    <tr>
                        <td class="font-medium text-slate-800">{{ $j->nama_jenis }}</td>
                        <td><span class="badge bg-slate-100 text-slate-600">{{ $j->kode_jenis ?: '—' }}</span></td>
                        <td class="text-slate-500">{{ $j->kategori ?: '—' }}</td>
                        <td class="text-center text-slate-600 font-semibold">{{ $pemakaian[$j->id] ?? 0 }}</td>
                        <td>
                            @if($j->is_aktif)
                                <span class="badge bg-emerald-50 text-emerald-700">Aktif</span>
                            @else
                                <span class="badge bg-slate-100 text-slate-500">Nonaktif</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="empty-state">
                            Belum ada jenis surat.
                            <a href="{{ route('jenis-surat.index') }}" class="text-brand-600 font-semibold">Tambahkan sekarang</a>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-panel>

    <x-panel judul="Catatan Hak Akses" ikon="fa-circle-info">
        <p class="text-sm text-slate-600 leading-relaxed">
            Sesuai pembagian tugas di aplikasi ini, Tata Usaha mengelola <b>master Jenis Surat</b>
            (nama, kode penomoran, kategori, dan format formulirnya). Pembuatan serta pengarsipan
            surat siswa dilakukan oleh <b>Guru BK</b>, sehingga isi surat per siswa tidak ditampilkan di sini.
        </p>
    </x-panel>
</div>
@endsection
