{{--
    CHECKLIST PERSIAPAN TAHUN AJARAN.

    Dulu sebelas baris penuh, masing-masing setinggi 42px — lebih dari
    450px hanya untuk menyampaikan bahwa sepuluh di antaranya sudah
    beres. Yang perlu dilihat sebenarnya cuma dua hal: sudah berapa, dan
    mana yang belum.

    Sekarang disusun sebagai deretan tanda. Yang selesai menepi jadi
    hijau tenang, yang belum menonjol biru dan tetap bisa diklik. Tinggi
    seluruhnya kurang dari seperempat yang lama, dan yang belum selesai
    justru lebih mudah ditemukan.

    Isi datanya sama persis seperti sebelumnya — tidak ada perubahan
    pada OnboardingChecklistService maupun controller.
--}}
@php
    $selesaiSemua = $checklistOnboarding['selesai_semua'];
    $sisa = $checklistOnboarding['jumlah_total'] - $checklistOnboarding['jumlah_selesai'];
@endphp

<div class="card p-5 {{ $selesaiSemua ? 'border-emerald-200' : '' }}">
    <div class="flex items-start justify-between gap-4 flex-wrap mb-4">
        <div>
            <p class="font-bold text-slate-800">Persiapan Tahun Ajaran</p>
            <p class="text-sm text-slate-400 mt-0.5">
                @if($selesaiSemua)
                    Semua langkah sudah selesai. Aplikasi siap dipakai sehari-hari.
                @else
                    Dari Pengaturan Sekolah sampai Akun Portal Orang Tua.
                @endif
            </p>
        </div>
        <span class="text-sm font-bold shrink-0 {{ $selesaiSemua ? 'text-emerald-600' : 'text-slate-500' }}">
            {{ $checklistOnboarding['jumlah_selesai'] }} dari {{ $checklistOnboarding['jumlah_total'] }} selesai
        </span>
    </div>

    <div class="flex flex-wrap gap-2">
        @foreach($checklistOnboarding['items'] as $item)
            @php
                $gaya = $item['selesai']
                    ? 'bg-emerald-50 text-emerald-700'
                    : 'bg-brand-50 text-brand-800 ring-1 ring-inset ring-brand-200';
                $tanda = $item['selesai'] ? 'fa-check' : 'fa-circle-notch';
            @endphp

            @if($item['route'])
                <a href="{{ $item['route'] }}"
                   class="inline-flex items-center gap-2 text-[13px] font-semibold px-3 py-1.5 rounded-lg transition hover:brightness-95 {{ $gaya }}">
                    <i class="fa-solid {{ $tanda }} text-[10px]"></i> {{ $item['label'] }}
                </a>
            @else
                <span class="inline-flex items-center gap-2 text-[13px] font-semibold px-3 py-1.5 rounded-lg {{ $gaya }}">
                    <i class="fa-solid {{ $tanda }} text-[10px]"></i> {{ $item['label'] }}
                </span>
            @endif
        @endforeach
    </div>

    @unless($selesaiSemua)
        <p class="text-xs text-slate-400 mt-3">
            {{ $sisa }} langkah lagi. Yang bertanda biru belum selesai — klik untuk membukanya.
        </p>
    @endunless
</div>
