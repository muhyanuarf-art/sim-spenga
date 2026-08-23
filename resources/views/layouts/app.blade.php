<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - SIM-SPENGA</title>
    {{-- Dulu Tailwind/Alpine/Font Awesome/Google Fonts dimuat dari CDN eksternal
         (Tailwind CDN bahkan meng-compile CSS di browser tiap request — lambat).
         Sekarang semua di-build & di-self-host lewat Vite, jadi cuma 1 file CSS
         dan 1 file JS yang sudah ter-minify + bisa di-cache browser. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- JARING PENGAMAN ikon (permintaan admin: "icon masih tidak muncul").
         KHUSUS Font Awesome dari CDN sebagai cadangan — BUKAN Tailwind CDN
         (itu yang sengaja dihapus di atas karena lambat; CSS Font Awesome
         statis begini TIDAK dikompilasi di browser, jadi tidak menimbulkan
         masalah performa yang sama). Kalau versi self-hosted di atas
         berhasil dimuat, browser cukup memakai definisi @font-face yang
         datang duluan/sama — tidak dobel-render, tidak menambah lag. Kalau
         versi self-hosted GAGAL dimuat (mis. public/build belum sempat
         di-build ulang di server tertentu), ikon tetap tampil dari sini
         sebagai cadangan. Hapus blok ini kapan pun setelah dipastikan
         `npm run build` di server selalu berjalan otomatis saat deploy. --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
          integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
          crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased" x-data="{ sidebarOpen: false }">

<div class="min-h-screen flex">
    {{-- Sidebar --}}
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed z-40 inset-y-0 left-0 w-72 bg-[#0b1f4d] border-r border-[#132a5e] transform transition-transform duration-200 lg:translate-x-0 lg:static lg:flex lg:flex-col">
        <div class="h-16 flex items-center gap-3 px-5 border-b border-white/10 shrink-0">
            <div class="w-10 h-10 rounded-xl bg-white text-[#0b1f4d] flex items-center justify-center font-extrabold text-base shadow-soft">SP</div>
            <div>
                <p class="font-bold text-white leading-tight text-base">SIM-SPENGA</p>
                <p class="text-xs text-blue-200/80 leading-tight">Sistem Info Sekolah</p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1.5 text-base">
            @php $user = auth()->user(); @endphp

            <x-nav-link :href="route('dashboard')" icon="fa-house" :active="request()->routeIs('dashboard')">
                Dashboard
            </x-nav-link>

            @if($user->role === 'guru' || $user->role === 'admin')
                <x-nav-group icon="fa-chalkboard-user" label="Guru Mapel" :active="request()->routeIs('mengajar.*')">
                    <x-nav-sublink :href="route('mengajar.index')" :active="request()->routeIs('mengajar.*')">
                        Absensi & Jurnal Mengajar
                    </x-nav-sublink>
                </x-nav-group>
            @endif

            {{-- (2026-08-23) — pembina ekskul dari SEKOLAH (guru/guru_bk)
                 masuk lewat sini untuk isi absensi kegiatan yang mereka
                 bina; halaman ini hanya menampilkan kegiatan yang mereka
                 bina (dicek di EkskulAbsensiController). Kesiswaan/Admin
                 TIDAK perlu link ini — mereka sudah punya tombol
                 "Absensi"/"Rekap" langsung di menu Ekstrakurikuler/Kesiswaan. --}}
            @if($user->role === 'guru' || $user->role === 'guru_bk')
                <x-nav-link :href="route('ekstrakurikuler.absensi.pilih')" icon="fa-people-group" :active="request()->routeIs('ekstrakurikuler.absensi.*') || request()->routeIs('ekstrakurikuler.rekap')">
                    Absensi Ekskul
                </x-nav-link>
            @endif

            @if(in_array($user->role, ['guru', 'guru_bk', 'kurikulum', 'kepala_sekolah', 'admin']))
                <x-nav-group icon="fa-triangle-exclamation" label="Pelanggaran" :active="request()->routeIs('bk.*')">
                    @if(in_array($user->role, ['guru_bk', 'kurikulum', 'kepala_sekolah', 'admin']))
                    <x-nav-sublink :href="route('bk.dashboard')" :active="request()->routeIs('bk.dashboard')">
                        Pantau Pelanggaran
                    </x-nav-sublink>
                    @endif
                    <x-nav-sublink :href="route('bk.kasus.index')" :active="request()->routeIs('bk.kasus.*')">
                        Kasus/Pelanggaran
                    </x-nav-sublink>
                    @if(in_array($user->role, ['guru_bk', 'kurikulum', 'kepala_sekolah', 'admin']))
                    <x-nav-sublink :href="route('bk.pembinaan.index')" :active="request()->routeIs('bk.pembinaan.*')">
                        Pembinaan
                    </x-nav-sublink>
                    <x-nav-sublink :href="route('bk.pengurangan.index')" :active="request()->routeIs('bk.pengurangan.*')">
                        Pengurangan Poin
                    </x-nav-sublink>
                    <x-nav-sublink :href="route('bk.pemanggilan.index')" :active="request()->routeIs('bk.pemanggilan.*')">
                        Pemanggilan Orang Tua
                    </x-nav-sublink>
                    <x-nav-sublink :href="route('bk.siswa.index')" :active="request()->routeIs('bk.siswa.*')">
                        Monitoring Siswa
                    </x-nav-sublink>
                    @endif
                    @if(in_array($user->role, ['guru_bk', 'admin']))
                    <x-nav-sublink :href="route('bk.jenis-pelanggaran.index')" :active="request()->routeIs('bk.jenis-pelanggaran.*')">
                        Data Pelanggaran (Master)
                    </x-nav-sublink>
                    @endif
                </x-nav-group>
            @endif

            @if($user->role === 'kesiswaan')
                <x-nav-group icon="fa-triangle-exclamation" label="Pelanggaran" :active="request()->routeIs('bk.dashboard')">
                    <x-nav-sublink :href="route('bk.dashboard')" :active="request()->routeIs('bk.dashboard')">
                        Pantau Pelanggaran
                    </x-nav-sublink>
                </x-nav-group>
            @endif

            @if($user->role === 'kesiswaan')
                <x-nav-link :href="route('ekstrakurikuler.index')" icon="fa-people-group" :active="request()->routeIs('ekstrakurikuler.*')">
                    Ekstrakurikuler
                </x-nav-link>
            @endif

            {{-- (2026-08-23) — untuk Admin (yang sidebarnya sudah padat
                 banyak grup), "Ekstrakurikuler" dikelompokkan dalam grup
                 "Kesiswaan" tersendiri (pola sama seperti grup "Kurikulum"
                 di bawah), supaya rapi & gampang ditambah menu kesiswaan
                 lain nanti. Untuk role Kesiswaan sendiri TIDAK dikelompokkan
                 seperti ini — tetap flat seperti di atas, karena sidebar-nya
                 sudah ringkas dan ini yang diminta dipertahankan. --}}
            @if($user->role === 'admin')
                <x-nav-group icon="fa-people-group" label="Kesiswaan" :active="request()->routeIs('ekstrakurikuler.*')">
                    <x-nav-sublink :href="route('ekstrakurikuler.index')" :active="request()->routeIs('ekstrakurikuler.*')">
                        Ekstrakurikuler
                    </x-nav-sublink>
                </x-nav-group>
            @endif

            @if($user->role === 'admin' || $user->role === 'kurikulum' || $user->role === 'kepala_sekolah' || $user->role === 'guru_bk' || $user->role === 'kesiswaan' || ($user->role === 'guru' && $user->isWaliKelas()))
                <x-nav-group icon="fa-chalkboard" label="Monitoring Kelas" :active="request()->routeIs('walikelas.*')">
                    <x-nav-sublink :href="route('walikelas.absensi-bulanan')" :active="request()->routeIs('walikelas.absensi-bulanan')">
                        Rekap Absensi Bulanan
                    </x-nav-sublink>
                    @if($user->role !== 'kesiswaan')
                    <x-nav-sublink :href="route('walikelas.jurnal-kelas')" :active="request()->routeIs('walikelas.jurnal-kelas')">
                        Jurnal Mengajar Kelas
                    </x-nav-sublink>
                    @endif
                </x-nav-group>
            @endif

            @if(in_array($user->role, ['guru', 'guru_bk', 'kurikulum', 'kepala_sekolah', 'admin']))
                <x-nav-group icon="fa-file-lines" label="Laporan" :active="request()->routeIs('laporan.*') || request()->routeIs('notifikasi-wa.*')">
                    @if($user->role !== 'guru_bk')
                    <x-nav-sublink :href="route('laporan.jurnal-guru')" :active="request()->routeIs('laporan.jurnal-guru')">
                        Jurnal Mengajar Guru Tiap Mapel
                    </x-nav-sublink>
                    <x-nav-sublink :href="route('laporan.absensi-guru')" :active="request()->routeIs('laporan.absensi-guru')">
                        Absensi Guru Tiap Mapel
                    </x-nav-sublink>
                    @endif
                    <x-nav-sublink :href="route('notifikasi-wa.index')" :active="request()->routeIs('notifikasi-wa.index')">
                        Status WhatsApp Ortu
                    </x-nav-sublink>
                </x-nav-group>
            @endif

            @if(in_array($user->role, ['admin', 'kurikulum', 'kepala_sekolah']))
                <x-nav-link :href="route('rekap.index')" icon="fa-chart-line" :active="request()->routeIs('rekap.index')">
                    Rekapitulasi
                </x-nav-link>
            @endif

            @if($user->role === 'kurikulum' || $user->role === 'admin')
                <x-nav-group icon="fa-graduation-cap" label="Kurikulum" :active="
                    request()->routeIs('kurikulum.*') || request()->routeIs('jadwal.*') || request()->routeIs('siswa.*') ||
                    request()->routeIs('orangtua-akun.*') || request()->routeIs('kelas.*') || request()->routeIs('mapel.*') ||
                    request()->routeIs('tahun-ajaran.*') || request()->routeIs('pengaturan-sekolah.*')
                ">
                    <x-nav-sublink :href="route('kurikulum.guru-mengajar.index')" :active="request()->routeIs('kurikulum.guru-mengajar.*')">
                        Mapping Guru Mengajar
                    </x-nav-sublink>
                    <x-nav-sublink :href="route('kurikulum.guru-bk.index')" :active="request()->routeIs('kurikulum.guru-bk.*')">
                        Mapping Guru BK
                    </x-nav-sublink>
                    <x-nav-sublink :href="route('jadwal.index')" :active="request()->routeIs('jadwal.*')">
                        Jadwal Pelajaran
                    </x-nav-sublink>
                    <x-nav-sublink :href="route('siswa.index')" :active="request()->routeIs('siswa.*')">
                        Data Siswa
                    </x-nav-sublink>
                    <x-nav-sublink :href="route('orangtua-akun.index')" :active="request()->routeIs('orangtua-akun.*')">
                        Data Orang Tua
                    </x-nav-sublink>
                    <x-nav-sublink :href="route('kelas.index')" :active="request()->routeIs('kelas.*')">
                        Data Kelas
                    </x-nav-sublink>
                    <x-nav-sublink :href="route('mapel.index')" :active="request()->routeIs('mapel.*')">
                        Mata Pelajaran
                    </x-nav-sublink>
                    <x-nav-sublink :href="route('tahun-ajaran.index')" :active="request()->routeIs('tahun-ajaran.*')">
                        Tahun Ajaran
                    </x-nav-sublink>
                    <x-nav-sublink :href="route('pengaturan-sekolah.edit')" :active="request()->routeIs('pengaturan-sekolah.*')">
                        Pengaturan Sekolah
                    </x-nav-sublink>
                </x-nav-group>
            @endif

            @if($user->role === 'admin')
                <x-nav-group icon="fa-user-shield" label="Administrator" :active="request()->routeIs('jam-pelajaran.*') || request()->routeIs('users.*')">
                    <x-nav-sublink :href="route('jam-pelajaran.index')" :active="request()->routeIs('jam-pelajaran.*')">
                        Jam Pelajaran
                    </x-nav-sublink>
                    <x-nav-sublink :href="route('users.index')" :active="request()->routeIs('users.*')">
                        Kelola Pengguna
                    </x-nav-sublink>
                </x-nav-group>
            @endif
        </nav>

        <div class="p-3 border-t border-white/10">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg text-base font-semibold text-red-300 hover:bg-red-500/10 hover:text-red-200 transition">
                    <span class="nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span> Keluar
                </button>
            </form>
        </div>
    </aside>

    <div x-show="sidebarOpen" @click="sidebarOpen=false" x-cloak class="fixed inset-0 bg-black/30 z-30 lg:hidden"></div>

    {{-- Main --}}
    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-20 gap-2">
            <div class="flex items-center gap-3 min-w-0">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-lg hover:bg-slate-100 shrink-0 text-slate-600">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <h1 class="font-semibold text-slate-800 text-base lg:text-lg truncate">@yield('title', 'Dashboard')</h1>
            </div>
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                {{-- STEP 8 Bagian 3/22 — badge periode aktif SELALU tampil, termasuk
                     di layar kecil (sebelumnya hidden di mobile — admin/guru yang
                     paling sering pakai HP justru tidak pernah melihat info ini). --}}
                @php $periodeAktif = \App\Models\TahunAjaran::aktif(); @endphp
                @if($periodeAktif)
                    <span class="badge {{ $periodeAktif->isTerkunci() ? 'bg-red-50 text-red-700' : 'bg-brand-50 text-brand-700' }} inline-flex">
                        <i class="fa-solid fa-calendar-days mr-1.5"></i> <span class="hidden sm:inline">{{ $periodeAktif->labelSingkat() }}</span><span class="sm:hidden">{{ $periodeAktif->nama }}</span>
                        @if($periodeAktif->isTerkunci()) &nbsp;<i class="fa-solid fa-lock mr-1.5"></i> @endif
                    </span>
                @else
                    <span class="badge bg-amber-50 text-amber-700 inline-flex"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i> <span class="hidden sm:inline">Belum ada periode aktif</span></span>
                @endif
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-semibold text-slate-800 leading-tight">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-400 leading-tight">{{ auth()->user()->roleLabel() }}</p>
                </div>
                <div class="w-9 h-9 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-bold text-sm shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 lg:p-8">
            @if(session('success'))
                <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm font-medium">
                    <i class="fa-solid fa-circle-check mr-1.5"></i> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm font-medium">
                    <i class="fa-solid fa-triangle-exclamation mr-1.5"></i> {{ session('error') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                    <p class="font-medium mb-1"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Terjadi kesalahan:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

</body>
</html>
