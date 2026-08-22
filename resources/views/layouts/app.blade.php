<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - SIM-SPENGA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Plugin x-collapse WAJIB dimuat sebelum Alpine core agar animasi buka/tutup submenu berjalan mulus --}}
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.13.5/dist/cdn.min.js"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.5/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
                    colors: {
                        brand: {
                            50: '#eef7ff', 100: '#d9ecff', 200: '#bcdfff', 300: '#8ecbff',
                            400: '#59acff', 500: '#3388fd', 600: '#1c68f2', 700: '#1553de',
                            800: '#1844b3', 900: '#193c8c',
                        },
                    },
                    boxShadow: { soft: '0 2px 10px 0 rgba(20, 30, 60, 0.06)' },
                },
            },
        };
    </script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
        body { -webkit-font-smoothing: antialiased; }
    </style>
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

<style>
    .nav-link { display:flex; align-items:center; gap:.75rem; padding:.7rem .85rem; border-radius:.7rem; color:#c7d2ef; font-weight:600; font-size:1rem; transition:.15s; }
    .nav-link:hover { background:rgba(255,255,255,.08); color:#ffffff; }
    .nav-active { background:#2554e0; color:#ffffff; box-shadow:0 2px 8px 0 rgba(0,0,0,.25); }
    .nav-section { font-size:.78rem; text-transform:uppercase; letter-spacing:.06em; color:#7e93d6; font-weight:800; padding:1.1rem .85rem .35rem; }

    /* ===== Menu icon (Font Awesome) ===== */
    .nav-icon { display:inline-flex; align-items:center; justify-content:center; width:1.5rem; flex-shrink:0; font-size:1rem; }

    /* ===== Menu induk yang bisa dibuka/ditutup (accordion: Menu -> Sub Menu) ===== */
    .nav-group-btn { display:flex; align-items:center; gap:.75rem; width:100%; padding:.7rem .85rem; border-radius:.7rem; color:#c7d2ef; font-weight:600; font-size:1rem; background:transparent; border:0; cursor:pointer; transition:.15s; text-align:left; }
    .nav-group-btn:hover { background:rgba(255,255,255,.08); color:#ffffff; }
    .nav-group-btn-active { color:#ffffff; }
    .nav-chevron { font-size:.7rem; color:#7e93d6; transition:transform .2s ease; flex-shrink:0; }
    .nav-sub { margin-top:.15rem; margin-left:1.05rem; padding-left:.85rem; border-left:1px solid rgba(255,255,255,.1); display:flex; flex-direction:column; gap:.15rem; }
    .nav-sublink { display:flex; align-items:center; gap:.65rem; padding:.55rem .75rem; border-radius:.6rem; color:#a8b6e3; font-weight:500; font-size:.9rem; transition:.15s; }
    .nav-sublink:hover { background:rgba(255,255,255,.08); color:#ffffff; }
    .nav-sublink-active { background:#2554e0; color:#ffffff; font-weight:600; box-shadow:0 2px 8px 0 rgba(0,0,0,.25); }
    .nav-dot { width:.35rem; height:.35rem; border-radius:999px; background:currentColor; opacity:.6; flex-shrink:0; }
    .card { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; box-shadow:0 2px 10px 0 rgba(20,30,60,.05); }
    .btn-primary { background:#1c68f2; color:#fff; padding:.55rem 1.1rem; border-radius:.65rem; font-weight:600; font-size:.875rem; transition:.15s; }
    .btn-primary:hover { background:#1553de; }
    .btn-outline { border:1px solid #e2e8f0; color:#475569; padding:.55rem 1.1rem; border-radius:.65rem; font-weight:600; font-size:.875rem; transition:.15s; }
    .btn-outline:hover { background:#f8fafc; }
    .input { border:1px solid #e2e8f0; border-radius:.6rem; padding:.5rem .75rem; font-size:.875rem; width:100%; }
    .input:focus { outline:2px solid #93c5fd; border-color:#3388fd; }
    .table-clean th { text-align:left; font-size:.72rem; text-transform:uppercase; letter-spacing:.03em; color:#94a3b8; font-weight:700; padding:.6rem .9rem; border-bottom:1px solid #e2e8f0; }
    .table-clean td { padding:.65rem .9rem; border-bottom:1px solid #f1f5f9; font-size:.875rem; }
    .badge { display:inline-flex; align-items:center; padding:.15rem .6rem; border-radius:999px; font-size:.72rem; font-weight:700; }

    .th-aksi, .td-aksi { text-align:center !important; }
    .action-buttons { display:flex; align-items:center; justify-content:center; gap:.5rem; flex-wrap:wrap; }
    .btn-chip { display:inline-flex; align-items:center; gap:.3rem; padding:.4rem .85rem; border-radius:.55rem; font-weight:700; font-size:.78rem; line-height:1; transition:.15s; border:1px solid transparent; cursor:pointer; white-space:nowrap; }
    .btn-chip-edit { background:#eaf1ff; color:#1c56d6; }
    .btn-chip-edit:hover { background:#1c68f2; color:#fff; box-shadow:0 2px 6px 0 rgba(28,104,242,.35); }
    .btn-chip-delete { background:#fdecec; color:#d3352c; }
    .btn-chip-delete:hover { background:#e0392f; color:#fff; box-shadow:0 2px 6px 0 rgba(224,57,47,.35); }
    .btn-chip-success { background:#e6f8f0; color:#0f9d63; }
    .btn-chip-success:hover { background:#0fa968; color:#fff; box-shadow:0 2px 6px 0 rgba(15,169,104,.35); }
    .btn-chip-cancel { background:#f1f5f9; color:#64748b; }
    .btn-chip-cancel:hover { background:#e2e8f0; color:#334155; }
    .btn-chip-icon { padding:.4rem; width:2rem; height:2rem; justify-content:center; }

    /* ===== Cetak / Print =====
       Prinsip: yang tercetak HANYA bagian yang ditandai class "print-section".
       Sidebar, header, form filter, tombol, dan elemen ber-class "no-print"
       selalu disembunyikan saat print. Kalau sebuah halaman punya lebih dari
       1 "print-section" (mis. halaman Rekapitulasi: Rekap Guru & Rekap
       Kelas), yang benar-benar tercetak hanya section yang dipilih lewat
       tombol Cetak-nya masing-masing (lihat fungsi cetakBagian() di bawah).
    */
    @media print {
        aside, header, .no-print { display: none !important; }
        body { background: #fff !important; }
        main { padding: 0 !important; }
        .card { box-shadow: none !important; border: 1px solid #cbd5e1 !important; }

        /* Kalau halaman ini pakai cetakBagian() (body dapat class
           "print-target-active"), sembunyikan semua print-section KECUALI
           yang sedang dipilih. Kalau tidak (halaman dengan 1 section saja,
           tombolnya masih window.print() biasa), semua print-section yang
           ada otomatis tampil apa adanya. */
        body.print-target-active .print-section { display: none !important; }
        body.print-target-active .print-section.print-target-selected { display: block !important; }
    }
</style>
<script>
    /**
     * Cetak HANYA 1 bagian tertentu di halaman (dipakai kalau halaman punya
     * lebih dari 1 "print-section", mis. Rekapitulasi punya Rekap Guru &
     * Rekap Kelas terpisah). Elemen lain yang juga ber-class "print-section"
     * otomatis disembunyikan sementara selama proses cetak.
     */
    function cetakBagian(idElemen) {
        document.querySelectorAll('.print-section').forEach(function (el) {
            el.classList.remove('print-target-selected');
        });
        var target = document.getElementById(idElemen);
        if (target) {
            target.classList.add('print-target-selected');
        }
        document.body.classList.add('print-target-active');
        window.print();
    }
    window.addEventListener('afterprint', function () {
        document.body.classList.remove('print-target-active');
    });
</script>
</body>
</html>
