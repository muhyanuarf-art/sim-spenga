<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - SIM-SPENGA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.5/cdn.min.js" defer></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-base">
            @php $user = auth()->user(); @endphp

            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-active' : '' }}">
                <span class="text-lg">🏠</span> Dashboard
            </a>

            @if($user->role === 'guru' || $user->role === 'admin')
                <p class="nav-section">Guru Mapel</p>
                <a href="{{ route('mengajar.index') }}" class="nav-link {{ request()->routeIs('mengajar.*') ? 'nav-active' : '' }}">
                    <span class="text-lg">📝</span> Absensi & Jurnal Mengajar
                </a>
            @endif

            @if(in_array($user->role, ['guru', 'guru_bk', 'kurikulum', 'kepala_sekolah', 'admin']))
                <p class="nav-section">BK</p>
                @if(in_array($user->role, ['guru_bk', 'kurikulum', 'kepala_sekolah', 'admin']))
                <a href="{{ route('bk.dashboard') }}" class="nav-link {{ request()->routeIs('bk.dashboard') ? 'nav-active' : '' }}">
                    <span class="text-lg">🧭</span> Dashboard BK
                </a>
                @endif
                <a href="{{ route('bk.kasus.index') }}" class="nav-link {{ request()->routeIs('bk.kasus.*') ? 'nav-active' : '' }}">
                    <span class="text-lg">📁</span> Kasus/Pelanggaran
                </a>
                @if(in_array($user->role, ['guru_bk', 'kurikulum', 'kepala_sekolah', 'admin']))
                <a href="{{ route('bk.pembinaan.index') }}" class="nav-link {{ request()->routeIs('bk.pembinaan.*') ? 'nav-active' : '' }}">
                    <span class="text-lg">🤝</span> Pembinaan
                </a>
                <a href="{{ route('bk.pengurangan.index') }}" class="nav-link {{ request()->routeIs('bk.pengurangan.*') ? 'nav-active' : '' }}">
                    <span class="text-lg">➖</span> Pengurangan Poin
                </a>
                <a href="{{ route('bk.pemanggilan.index') }}" class="nav-link {{ request()->routeIs('bk.pemanggilan.*') ? 'nav-active' : '' }}">
                    <span class="text-lg">📞</span> Pemanggilan Orang Tua
                </a>
                <a href="{{ route('bk.siswa.index') }}" class="nav-link {{ request()->routeIs('bk.siswa.*') ? 'nav-active' : '' }}">
                    <span class="text-lg">🔍</span> Monitoring Siswa
                </a>
                @endif
                @if(in_array($user->role, ['guru_bk', 'admin']))
                <a href="{{ route('bk.jenis-pelanggaran.index') }}" class="nav-link {{ request()->routeIs('bk.jenis-pelanggaran.*') ? 'nav-active' : '' }}">
                    <span class="text-lg">🗂️</span> Data Pelanggaran (Master)
                </a>
                @endif
            @endif

            @if($user->role === 'admin' || $user->role === 'kurikulum' || $user->role === 'kepala_sekolah' || $user->role === 'guru_bk' || ($user->role === 'guru' && $user->isWaliKelas()))
                <p class="nav-section">Monitoring Kelas</p>
                <a href="{{ route('walikelas.absensi-bulanan') }}" class="nav-link {{ request()->routeIs('walikelas.absensi-bulanan') ? 'nav-active' : '' }}">
                    <span class="text-lg">📊</span> Rekap Absensi Bulanan
                </a>
                <a href="{{ route('walikelas.jurnal-kelas') }}" class="nav-link {{ request()->routeIs('walikelas.jurnal-kelas') ? 'nav-active' : '' }}">
                    <span class="text-lg">📔</span> Jurnal Mengajar Kelas
                </a>
            @endif

            @if(in_array($user->role, ['guru', 'guru_bk', 'kurikulum', 'kepala_sekolah', 'admin']))
                <p class="nav-section">Laporan</p>
                @if($user->role !== 'guru_bk')
                <a href="{{ route('laporan.jurnal-guru') }}" class="nav-link {{ request()->routeIs('laporan.jurnal-guru') ? 'nav-active' : '' }}">
                    <span class="text-lg">📘</span> Jurnal Mengajar Guru Tiap Mapel
                </a>
                <a href="{{ route('laporan.absensi-guru') }}" class="nav-link {{ request()->routeIs('laporan.absensi-guru') ? 'nav-active' : '' }}">
                    <span class="text-lg">🗒️</span> Absensi Guru Tiap Mapel
                </a>
                @endif
                <a href="{{ route('notifikasi-wa.index') }}" class="nav-link {{ request()->routeIs('notifikasi-wa.index') ? 'nav-active' : '' }}">
                    <span class="text-lg">📲</span> Status WhatsApp Ortu
                </a>
            @endif

            @if(in_array($user->role, ['admin', 'kurikulum', 'kepala_sekolah']))
                <a href="{{ route('rekap.index') }}" class="nav-link {{ request()->routeIs('rekap.index') ? 'nav-active' : '' }}">
                    <span class="text-lg">📈</span> Rekapitulasi
                </a>
            @endif

            @if($user->role === 'kurikulum' || $user->role === 'admin')
                <p class="nav-section">Kurikulum</p>
                <a href="{{ route('kurikulum.guru-mengajar.index') }}" class="nav-link {{ request()->routeIs('kurikulum.guru-mengajar.*') ? 'nav-active' : '' }}">
                    <span class="text-lg">👨‍🏫</span> Mapping Guru Mengajar
                </a>
                <a href="{{ route('kurikulum.guru-bk.index') }}" class="nav-link {{ request()->routeIs('kurikulum.guru-bk.*') ? 'nav-active' : '' }}">
                    <span class="text-lg">🧭</span> Mapping Guru BK
                </a>
                <a href="{{ route('jadwal.index') }}" class="nav-link {{ request()->routeIs('jadwal.*') ? 'nav-active' : '' }}">
                    <span class="text-lg">🗓️</span> Jadwal Pelajaran
                </a>
                <a href="{{ route('siswa.index') }}" class="nav-link {{ request()->routeIs('siswa.*') ? 'nav-active' : '' }}">
                    <span class="text-lg">🧑‍🎓</span> Data Siswa
                </a>
                <a href="{{ route('kelas.index') }}" class="nav-link {{ request()->routeIs('kelas.*') ? 'nav-active' : '' }}">
                    <span class="text-lg">🏫</span> Data Kelas
                </a>
                <a href="{{ route('mapel.index') }}" class="nav-link {{ request()->routeIs('mapel.*') ? 'nav-active' : '' }}">
                    <span class="text-lg">📚</span> Mata Pelajaran
                </a>
                <a href="{{ route('tahun-ajaran.index') }}" class="nav-link {{ request()->routeIs('tahun-ajaran.*') ? 'nav-active' : '' }}">
                    <span class="text-lg">📅</span> Tahun Ajaran
                </a>
            @endif

            @if($user->role === 'admin')
                <p class="nav-section">Administrator</p>
                <a href="{{ route('jam-pelajaran.index') }}" class="nav-link {{ request()->routeIs('jam-pelajaran.*') ? 'nav-active' : '' }}">
                    <span class="text-lg">⏰</span> Jam Pelajaran
                </a>
                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'nav-active' : '' }}">
                    <span class="text-lg">👤</span> Kelola Pengguna
                </a>
            @endif
        </nav>

        <div class="p-3 border-t border-white/10">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full flex items-center gap-2 px-3 py-2.5 rounded-lg text-base font-semibold text-red-300 hover:bg-red-500/10 hover:text-red-200 transition">
                    <span class="text-lg">🚪</span> Keluar
                </button>
            </form>
        </div>
    </aside>

    <div x-show="sidebarOpen" @click="sidebarOpen=false" x-cloak class="fixed inset-0 bg-black/30 z-30 lg:hidden"></div>

    {{-- Main --}}
    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-lg hover:bg-slate-100">☰</button>
                <h1 class="font-semibold text-slate-800 text-base lg:text-lg">@yield('title', 'Dashboard')</h1>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-semibold text-slate-800 leading-tight">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-400 leading-tight">{{ auth()->user()->roleLabel() }}</p>
                </div>
                <div class="w-9 h-9 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-bold text-sm">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 lg:p-8">
            @if(session('success'))
                <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm font-medium">
                    ✅ {{ session('success') }}
                </div>
            @endif
            @if($errors->any())
                <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                    <p class="font-medium mb-1">⚠️ Terjadi kesalahan:</p>
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
    .nav-link { display:flex; align-items:center; gap:.7rem; padding:.7rem .85rem; border-radius:.7rem; color:#c7d2ef; font-weight:600; font-size:1rem; transition:.15s; }
    .nav-link:hover { background:rgba(255,255,255,.08); color:#ffffff; }
    .nav-active { background:#2554e0; color:#ffffff; box-shadow:0 2px 8px 0 rgba(0,0,0,.25); }
    .nav-section { font-size:.78rem; text-transform:uppercase; letter-spacing:.06em; color:#7e93d6; font-weight:800; padding:1.1rem .85rem .35rem; }
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
</style>
</body>
</html>
