{{--
    KOP Surat — kepala laporan resmi, dipasang di bagian PALING ATAS tiap
    halaman yang punya tombol Cetak (di dalam elemen yang sama dengan
    id yang dikirim ke cetakBagian(), supaya ikut kepencet ke PDF/print).
    Datanya otomatis dari Pengaturan Sekolah ($pengaturanSekolahGlobal,
    di-share ke semua view lewat View::composer('*', ...) — lihat
    AppServiceProvider) — TIDAK perlu diisi ulang di tiap halaman.

    (2026-08-24, revisi) — SENGAJA disembunyikan di layar biasa lewat
    class "cetak-saja" (lihat resources/css/app.css) dan HANYA muncul
    saat benar-benar Cetak/Export PDF, supaya tidak mengganggu tampilan
    layar sehari-hari.

    Baris yang datanya belum diisi di Pengaturan Sekolah otomatis TIDAK
    ditampilkan (bukan tampil kosong) — jadi sekolah yang cuma mengisi
    sebagian (mis. cuma Nama Sekolah, tanpa logo) tetap dapat KOP Surat
    yang rapi, bukan baris-baris kosong.
--}}
@php
    $pengaturan = $pengaturanSekolahGlobal;
    $namaSekolah = $pengaturan->nama_sekolah;
@endphp

@if($namaSekolah)
    <div class="cetak-saja pb-3 mb-4 border-b-4 border-double border-slate-800">
        <div class="flex items-center gap-4">
            <div class="w-20 shrink-0 text-center">
                @if($pengaturan->logoKiriUrl())
                    <img src="{{ $pengaturan->logoKiriUrl() }}" class="w-20 h-20 object-contain mx-auto">
                @endif
            </div>
            <div class="flex-1 text-center">
                @if($pengaturan->pemerintah_daerah)
                    <p class="font-extrabold text-sm uppercase tracking-wide text-slate-900">{{ $pengaturan->pemerintah_daerah }}</p>
                @endif
                @if($pengaturan->instansi_induk)
                    <p class="font-extrabold text-sm uppercase tracking-wide text-slate-900">{{ $pengaturan->instansi_induk }}</p>
                @endif
                @if($pengaturan->unit_kerja)
                    <p class="font-extrabold text-sm uppercase tracking-wide text-slate-900">{{ $pengaturan->unit_kerja }}</p>
                @endif
                <p class="font-extrabold text-xl uppercase tracking-wide text-slate-900">{{ $namaSekolah }}</p>
                @if($pengaturan->kecamatan)
                    <p class="font-semibold text-xs uppercase tracking-wide text-slate-700">{{ $pengaturan->kecamatan }}</p>
                @endif
                @if($pengaturan->alamat_sekolah)
                    <p class="text-xs text-slate-600">{{ $pengaturan->alamat_sekolah }}</p>
                @endif
                @if($pengaturan->website_sekolah || $pengaturan->email_sekolah)
                    <p class="text-xs text-slate-600">
                        @if($pengaturan->website_sekolah)
                            Website : {{ $pengaturan->website_sekolah }}
                        @endif
                        @if($pengaturan->website_sekolah && $pengaturan->email_sekolah)
                            &nbsp;
                        @endif
                        @if($pengaturan->email_sekolah)
                            Email : {{ $pengaturan->email_sekolah }}
                        @endif
                    </p>
                @endif
            </div>
            <div class="w-20 shrink-0 text-center">
                @if($pengaturan->logoKananUrl())
                    <img src="{{ $pengaturan->logoKananUrl() }}" class="w-20 h-20 object-contain mx-auto">
                @endif
            </div>
        </div>
    </div>
@else
    <p class="no-print text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-4">
        <i class="fa-solid fa-triangle-exclamation mr-1"></i>
        @if(in_array(auth()->user()->role, ['admin', 'kurikulum']))
            Nama Sekolah belum diisi di <a href="{{ route('pengaturan-sekolah.edit') }}" class="underline font-semibold">Pengaturan Sekolah</a> — KOP Surat belum bisa ditampilkan di hasil cetak.
        @else
            Nama Sekolah belum diisi di Pengaturan Sekolah — KOP Surat belum bisa ditampilkan di hasil cetak. Minta Admin/Kurikulum untuk melengkapinya.
        @endif
    </p>
@endif
