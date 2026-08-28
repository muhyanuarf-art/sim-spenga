{{--
    TAUTAN IKON APLIKASI untuk bagian <head>.

    Dipakai bersama oleh layouts/app, halaman login guru, dan halaman login
    orang tua supaya ketiganya tidak mungkin berbeda.

    Susunannya mengikuti anjuran baku generator favicon:
      - favicon-96x96.png  : ikon tab browser pada layar biasa & retina
      - favicon.svg        : dipakai peramban modern, tajam di ukuran apa pun
      - favicon.ico        : cadangan untuk peramban lama (berisi 16/32/48)
      - apple-touch-icon   : ikon saat halaman disimpan ke layar utama iPhone
      - site.webmanifest   : nama & ikon 192/512 untuk Android

    Seluruh berkasnya dibuat otomatis dari Logo Aplikasi yang diunggah di
    menu Pengaturan Sekolah (lihat App\Support\IkonAplikasi) — jadi sekolah
    tidak perlu menyiapkan sendiri satu per satu.

    Kalau sekolah belum mengunggah logo, tidak ada tautan yang dikeluarkan
    sama sekali dan peramban memakai ikon bawaannya seperti sebelumnya.
--}}
@php
    $ico = \App\Support\IkonAplikasi::url('favicon.ico');
    $svg = \App\Support\IkonAplikasi::url('favicon.svg');
    $png96 = \App\Support\IkonAplikasi::url('favicon-96x96.png');
    $apple = \App\Support\IkonAplikasi::url('apple-touch-icon.png');
    $namaSekolah = $pengaturanSekolahGlobal->nama_sekolah ?: 'SIM-SPENGA';
@endphp

@if($png96)
    <link rel="icon" type="image/png" href="{{ $png96 }}" sizes="96x96" />
@endif
@if($svg)
    <link rel="icon" type="image/svg+xml" href="{{ $svg }}" />
@endif
@if($ico)
    <link rel="shortcut icon" href="{{ $ico }}" />
@endif
@if($apple)
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $apple }}" />
@endif
@if($ico || $svg)
    <meta name="apple-mobile-web-app-title" content="{{ $namaSekolah }}" />
    <link rel="manifest" href="{{ route('site.webmanifest') }}" />
    <meta name="theme-color" content="#1c68f2" />
@endif
