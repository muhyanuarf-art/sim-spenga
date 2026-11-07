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
        {{-- Kepala kartu kini memakai class ".card-head" di app.css, bukan
             deretan utility di sini. Jaraknya jadi satu-satunya yang
             berlaku: semua kartu berjudul di seluruh aplikasi berganti
             bersama-sama kalau suatu saat diubah lagi. --}}
        <div class="card-head">
            <div class="min-w-0 flex items-start gap-3">
                {{-- Ikon ATAU batang oranye, tidak pernah keduanya. Dua
                     penanda untuk satu judul yang sama hanya membuat
                     pembacanya bertanya apa bedanya. Kartu yang sudah
                     memilih ikon sudah punya penandanya sendiri. --}}
                @if($ikon)
                    <div class="w-9 h-9 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                        <i class="fa-solid {{ $ikon }}"></i>
                    </div>
                @else
                    <span class="aksen-batang" aria-hidden="true"></span>
                @endif
                <div class="min-w-0">
                    <p class="section-title tanpa-aksen">{{ $judul }}</p>
                    @if($deskripsi)<p class="text-xs text-slate-400 mt-0.5">{{ $deskripsi }}</p>@endif
                </div>
            </div>
            @if($aksi)<div class="flex items-center gap-2 flex-wrap">{{ $aksi }}</div>@endif
        </div>
    @endif
    <div class="{{ $rapat ? '' : 'p-5' }}">{{ $slot }}</div>
</div>
