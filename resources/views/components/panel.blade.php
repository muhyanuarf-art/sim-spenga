{{--
    Kartu berjudul yang dipakai berulang di dashboard & halaman laporan.
    Menyeragamkan jarak, ukuran judul, dan posisi tombol aksi di kanan atas.

    Contoh:
      <x-panel judul="Jurnal Hari Ini" deskripsi="10 terbaru">
          <x-slot:aksi><a href="#" class="btn-outline">Lihat semua</a></x-slot:aksi>
          ...isi...
      </x-panel>
--}}
@props(['judul' => null, 'deskripsi' => null, 'ikon' => null, 'rapat' => false, 'aksi' => null])

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if($judul)
        <div class="flex items-start justify-between gap-3 flex-wrap px-5 py-4 border-b border-slate-100">
            <div class="min-w-0 flex items-start gap-3">
                @if($ikon)
                    <div class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                        <i class="fa-solid {{ $ikon }}"></i>
                    </div>
                @endif
                <div class="min-w-0">
                    <p class="section-title">{{ $judul }}</p>
                    @if($deskripsi)<p class="text-xs text-slate-400 mt-0.5">{{ $deskripsi }}</p>@endif
                </div>
            </div>
            @if($aksi)<div class="flex items-center gap-2 flex-wrap">{{ $aksi }}</div>@endif
        </div>
    @endif
    <div class="{{ $rapat ? '' : 'p-5' }}">{{ $slot }}</div>
</div>
