<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — SIM-SPENGA</title>
    <x-ikon-aplikasi />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-100 text-slate-800 font-sans antialiased" x-data="{ sidebarOpen: false }">

@php
    $user = auth()->user();
    $halaman = \App\Support\Navigasi::halamanAktif($user);
    // Periode yang sedang DILIHAT (default: periode berjalan).
    $periodeAktif = \App\Support\KonteksPeriode::pilihan();
    $alasanBacaSaja = \App\Support\KonteksPeriode::alasanBacaSaja();
@endphp

<div class="min-h-screen flex">
    @include('partials.sidebar')

    <div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak x-transition.opacity
         class="fixed inset-0 bg-slate-900/50 z-30 lg:hidden"></div>

    <div class="flex-1 flex flex-col min-w-0">
        {{-- ===== Top bar ===== --}}
        <header class="h-16 bg-white lg:bg-white/95 lg:backdrop-blur border-b border-slate-200 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-20 gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <button @click="sidebarOpen = !sidebarOpen"
                        class="lg:hidden w-9 h-9 rounded-lg hover:bg-slate-100 text-slate-600 shrink-0">
                    <i class="fa-solid fa-bars"></i>
                </button>

                {{-- Breadcrumb hanya menampilkan JALUR menuju halaman ini
                 (Beranda › Bagian › Grup). Nama halamannya sendiri sengaja
                 TIDAK diulang di sini, karena sudah tampil besar sebagai
                 judul halaman tepat di bawahnya. --}}
                <nav class="flex items-center gap-2 text-sm min-w-0" aria-label="Breadcrumb">
                    <a href="{{ route('dashboard') }}" wire:navigate class="text-slate-400 hover:text-brand-600 shrink-0" title="Beranda">
                        <i class="fa-solid fa-house"></i>
                    </a>
                    @if($halaman)
                        <i class="fa-solid fa-chevron-right text-[9px] text-slate-300"></i>
                        <span class="text-slate-500 truncate">{{ $halaman['seksi'] }}</span>
                        @if($halaman['induk'])
                            <i class="fa-solid fa-chevron-right text-[9px] text-slate-300 hidden sm:block"></i>
                            <span class="text-slate-500 truncate hidden sm:block">{{ $halaman['induk'] }}</span>
                        @endif
                    @endif
                </nav>
            </div>

            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                {{-- Pemilih periode: mengganti Tahun Ajaran + Semester yang
                     DILIHAT pengguna ini (bukan periode aktif sekolah).
                     Lihat App\Support\KonteksPeriode. --}}
                <x-pemilih-periode />

                {{-- Menu pengguna --}}
                <div class="relative" x-data="{ buka: false }" @click.outside="buka = false">
                    <button @click="buka = !buka" class="flex items-center gap-2 rounded-xl hover:bg-slate-100 pl-2 pr-1.5 py-1.5 transition">
                        <div class="text-right hidden sm:block leading-tight">
                            <p class="text-sm font-semibold text-slate-800">{{ $user->name }}</p>
                            <p class="text-[11px] text-slate-400">{{ $user->roleLabel() }}</p>
                        </div>
                        <div class="w-9 h-9 rounded-full bg-brand-600 text-white flex items-center justify-center font-bold text-sm shrink-0">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 hidden sm:block"></i>
                    </button>

                    <div x-show="buka" x-cloak x-transition.origin.top.right
                         class="absolute right-0 mt-2 w-64 bg-white rounded-xl border border-slate-200 shadow-xl overflow-hidden z-30">
                        <div class="px-4 py-3 border-b border-slate-100">
                            <p class="font-semibold text-slate-800 truncate">{{ $user->name }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ $user->email }}</p>
                            <span class="badge bg-brand-50 text-brand-700 mt-2">{{ $user->roleLabel() }}</span>
                        </div>
                        <div class="px-4 py-3 border-b border-slate-100 text-xs text-slate-500 space-y-1">
                            <p><i class="fa-solid fa-calendar-days w-4 text-slate-400"></i> {{ $periodeAktif?->nama ?? 'Belum ada periode aktif' }}</p>
                            @if($user->nip)<p><i class="fa-solid fa-id-card w-4 text-slate-400"></i> NIP {{ $user->nip }}</p>@endif
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="w-full text-left px-4 py-3 text-sm font-semibold text-rose-600 hover:bg-rose-50 transition">
                                <i class="fa-solid fa-right-from-bracket w-4 mr-1"></i> Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 lg:p-8">
            {{-- Spanduk mode lihat-saja: muncul di SETIAP halaman begitu
                 pengguna menengok periode lampau atau periode berjalannya
                 sudah ditutup. Diletakkan di layout (bukan per halaman)
                 supaya tidak ada satu pun halaman yang bisa lupa. --}}
            @if($alasanBacaSaja)
                <div class="no-print mb-5 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                    <i class="fa-solid fa-clock-rotate-left mt-0.5 text-amber-600"></i>
                    <div class="min-w-0 text-sm text-amber-900">
                        <p class="font-bold">Mode lihat saja</p>
                        <p class="mt-0.5 text-amber-800">{{ $alasanBacaSaja }}</p>
                    </div>
                </div>
            @endif

            {{-- ===== Judul halaman: seragam di SEMUA halaman, diambil dari
                 @section('title') + deskripsi dari registry Navigasi.
                 Halaman boleh menambah tombol aksi lewat @section('aksi'). ===== --}}
            @sectionMissing('tanpa-judul')
                <div class="flex items-start justify-between gap-4 flex-wrap mb-5 no-print">
                    <div class="min-w-0">
                        <h1 class="text-xl lg:text-2xl font-extrabold text-slate-800 tracking-tight">@yield('title', 'Dashboard')</h1>
                        @hasSection('deskripsi')
                            <p class="text-sm text-slate-500 mt-1">@yield('deskripsi')</p>
                        @elseif($halaman && $halaman['deskripsi'])
                            <p class="text-sm text-slate-500 mt-1">{{ $halaman['deskripsi'] }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">@yield('aksi')</div>
                </div>
            @endif

            {{-- ===== Notifikasi ===== --}}
            @if(session('success'))
                <div class="alert alert-success no-print" x-data="{ tampil: true }" x-show="tampil" x-cloak>
                    <i class="fa-solid fa-circle-check mt-0.5"></i>
                    <span class="flex-1">{{ session('success') }}</span>
                    <button @click="tampil = false" class="text-emerald-500 hover:text-emerald-700"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger no-print" x-data="{ tampil: true }" x-show="tampil" x-cloak>
                    <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                    <span class="flex-1">{{ session('error') }}</span>
                    <button @click="tampil = false" class="text-rose-500 hover:text-rose-700"><i class="fa-solid fa-xmark"></i></button>
                </div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning no-print">
                    <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                    <span class="flex-1">{{ session('warning') }}</span>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger no-print">
                    <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                    <div class="flex-1">
                        <p class="font-semibold mb-1">Periksa kembali isian berikut:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="px-4 lg:px-8 py-4 text-center text-xs text-slate-400 no-print">
            SIM-SPENGA — {{ $pengaturanSekolahGlobal->nama_sekolah ?? 'Sistem Informasi Manajemen Sekolah' }} &middot; {{ now()->translatedFormat('l, d F Y') }}
        </footer>
    </div>
</div>

@livewireScripts

<script>
    /* =====================================================================
       MENU TETAP TERLIHAT SETELAH DIKLIK

       Pindah halaman kini memakai wire:navigate, jadi isi <body> ditukar
       dan sidebar dirender ulang beserta penanda menu aktifnya. Yang
       tidak ikut terbawa adalah posisi gulir daftar menu: ia kembali ke
       atas, sehingga menu yang baru saja diklik bisa berada di luar
       layar — terasa seperti kehilangan jejak.

       Fungsi di bawah menggeser daftar menu supaya menu aktif berada di
       tengah. Dipanggil saat halaman pertama dibuka DAN setiap kali
       selesai berpindah.
       ===================================================================== */
    function fokuskanMenuAktif() {
        var wadah = document.querySelector('.nav-scroll');
        if (!wadah) return;

        var aktif = wadah.querySelector('.nav-active, .nav-sublink-active');
        if (!aktif) return;

        // Dihitung manual, bukan scrollIntoView(): yang boleh bergeser
        // hanya daftar menunya, sedangkan halaman di sebelahnya harus
        // tetap di tempatnya.
        var geser = aktif.getBoundingClientRect().top
            - wadah.getBoundingClientRect().top
            - (wadah.clientHeight / 2)
            + (aktif.offsetHeight / 2);

        wadah.scrollTop += geser;
    }

    document.addEventListener('livewire:navigated', fokuskanMenuAktif);
</script>

</body>
</html>
