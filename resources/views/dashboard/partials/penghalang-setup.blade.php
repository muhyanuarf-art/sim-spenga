{{--
    KARTU "SATU HAL YANG MENGHALANGI".

    =====================================================================
    KENAPA ADA
    =====================================================================
    Selama Jadwal Pelajaran belum disusun, aplikasi tidak punya satu pun
    sesi mengajar untuk dihitung. Akibatnya seluruh panel harian di
    dashboard menampilkan nol: kehadiran 0, kepatuhan 0%, grafik tren
    kosong, tiga belas baris progres 0%.

    Deretan nol itu TIDAK SALAH, tetapi menyesatkan. Orang yang membuka
    pertama kali membacanya sebagai "aplikasinya bermasalah", bukan
    "ada satu pekerjaan yang belum selesai".

    Kartu ini menggantikan panel-panel itu selama penyebabnya masih ada,
    lalu hilang sendiri begitu jadwalnya tersusun.

    =====================================================================
    TIDAK MENGUBAH APA PUN SELAIN TAMPILAN
    =====================================================================
    Datanya diambil dari $checklistOnboarding yang MEMANG SUDAH dikirim
    controller — tidak ada query baru, tidak ada kolom baru, tidak ada
    perubahan alur. Warnanya memakai kelas yang sudah dipakai di tempat
    lain (btn-primary, card, badge), jadi tombolnya tetap biru seperti
    semula.
--}}
@php
    $langkahJadwal = collect($checklistOnboarding['items'])->firstWhere('key', 'jadwal');

    /* Yang menunggu jadwal. Ditulis apa adanya — kelimanya memang
       benar-benar berhenti tanpa sesi mengajar. */
    $menunggu = [
        'Jurnal mengajar harian',
        'Absensi siswa per jam pelajaran',
        'Rekapitulasi kepatuhan',
        'Pengingat WhatsApp ke guru',
        'Notifikasi alfa ke orang tua',
    ];
@endphp

<div class="card overflow-hidden">
    <div class="grid lg:grid-cols-[minmax(0,1fr)_300px]">

        <div class="p-6 sm:p-7">
            <p class="text-lg sm:text-xl font-extrabold text-slate-800 tracking-tight">
                Jadwal Pelajaran belum disusun
            </p>

            <p class="text-sm text-slate-500 mt-2 leading-relaxed max-w-prose">
                Jadwal menentukan sesi mengajar tiap hari. Selama belum ada, aplikasi tidak punya
                apa pun untuk dihitung — itulah sebabnya seluruh angka harian masih nol.
            </p>

            <p class="text-sm text-slate-500 mt-2 leading-relaxed max-w-prose">
                Guru, kelas, mata pelajaran, dan jam pelajaran sudah lengkap, jadi penyusunannya
                tinggal menempatkan.
            </p>

            @if($langkahJadwal && $langkahJadwal['route'])
                <a href="{{ $langkahJadwal['route'] }}" class="btn-primary mt-5">
                    <i class="fa-solid fa-calendar-days"></i> Susun Jadwal Pelajaran
                </a>
            @endif
        </div>

        <div class="bg-slate-50 border-t lg:border-t-0 lg:border-l border-slate-100 p-6">
            <p class="text-xs font-bold text-slate-500 mb-3">Yang menunggu jadwal</p>
            <ul class="space-y-2">
                @foreach($menunggu as $m)
                    <li class="flex gap-2.5 text-[13px] text-slate-500 leading-snug">
                        <span class="w-2 h-px bg-slate-300 mt-2 shrink-0"></span>
                        <span>{{ $m }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

    </div>
</div>
