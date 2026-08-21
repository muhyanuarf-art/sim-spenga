<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Orang Tua - SIM-SPENGA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
            colors: { brand: { 50:'#eef7ff',100:'#d9ecff',600:'#1c68f2',700:'#1553de',900:'#193c8c' } } } } };
    </script>
</head>
<body class="font-sans bg-slate-50 min-h-screen">

    <header class="bg-white border-b border-slate-200">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-600 flex items-center justify-center text-white font-extrabold">SP</div>
                <div>
                    <p class="font-bold text-slate-800 leading-tight">Portal Orang Tua</p>
                    <p class="text-xs text-slate-400">SIM-SPENGA</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('orangtua.ganti-password.form') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 px-4 py-2 rounded-lg transition-colors">🔑 Ganti Password</a>
                <form method="POST" action="{{ route('orangtua.logout') }}">
                    @csrf
                    <button class="text-sm font-semibold text-white bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg transition-colors">Keluar</button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-6 space-y-6">
        @if(session('success'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        <div class="bg-white rounded-2xl shadow-sm p-6">
            <p class="text-sm text-slate-400">Data Anak</p>
            <p class="text-xl font-extrabold text-slate-800">{{ $siswa->nama }}</p>
            <div class="flex flex-wrap gap-4 mt-2 text-sm text-slate-500">
                <span>NIS: <strong class="text-slate-700">{{ $siswa->nis }}</strong></span>
                <span>Kelas: <strong class="text-slate-700">{{ $siswa->kelas->nama_kelas ?? '-' }}</strong></span>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                <p class="font-bold text-slate-800">Rekap Kehadiran</p>
                <form method="GET" class="flex gap-2">
                    <select name="bulan" class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm" onchange="this.form.submit()">
                        @foreach(range(1,12) as $b)
                            <option value="{{ $b }}" {{ $bulan == $b ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($b)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                    <select name="tahun" class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm" onchange="this.form.submit()">
                        @foreach(range(now()->year - 1, now()->year) as $y)
                            <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="grid grid-cols-4 gap-3 mb-5">
                <div class="rounded-xl bg-emerald-50 p-3 text-center">
                    <p class="text-2xl font-extrabold text-emerald-700">{{ $ringkasan['hadir'] }}</p>
                    <p class="text-xs text-emerald-600">Hadir</p>
                </div>
                <div class="rounded-xl bg-amber-50 p-3 text-center">
                    <p class="text-2xl font-extrabold text-amber-700">{{ $ringkasan['sakit'] }}</p>
                    <p class="text-xs text-amber-600">Sakit</p>
                </div>
                <div class="rounded-xl bg-sky-50 p-3 text-center">
                    <p class="text-2xl font-extrabold text-sky-700">{{ $ringkasan['izin'] }}</p>
                    <p class="text-xs text-sky-600">Izin</p>
                </div>
                <div class="rounded-xl bg-red-50 p-3 text-center">
                    <p class="text-2xl font-extrabold text-red-700">{{ $ringkasan['alfa'] }}</p>
                    <p class="text-xs text-red-600">Alfa</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-400 border-b border-slate-100">
                            <th class="py-2">Tanggal</th><th>Mapel</th><th>Status</th><th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($rekapHarian as $r)
                        <tr class="border-b border-slate-50">
                            <td class="py-2">{{ $r['tanggal'] }}</td>
                            <td>{{ $r['mapel'] ?? '-' }}</td>
                            <td>
                                @php $warna = ['Hadir'=>'emerald','Sakit'=>'amber','Izin'=>'sky','Alfa'=>'red'][$r['status']] ?? 'slate'; @endphp
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs bg-{{ $warna }}-50 text-{{ $warna }}-700">{{ $r['status'] }}</span>
                            </td>
                            <td class="text-slate-400">{{ $r['keterangan'] ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-slate-400 py-6">Belum ada data absensi bulan ini.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6">
            <p class="font-bold text-slate-800 mb-4">Riwayat Kelas</p>
            @forelse($riwayatKelas as $i => $r)
                <div class="flex gap-3 pb-3 {{ !$loop->last ? 'border-l-2 border-brand-100 ml-3' : 'ml-3' }} relative">
                    <div class="absolute -left-[0.95rem] top-0 w-6 h-6 rounded-full bg-brand-600 text-white text-[10px] font-bold flex items-center justify-center">
                        {{ $i + 1 }}
                    </div>
                    <div class="pl-6 pt-0">
                        <p class="font-semibold text-slate-700 text-sm">
                            {{ $r->kelasAsal->nama_kelas ?? 'Awal masuk' }}
                            <span class="text-slate-400">&rarr;</span>
                            {{ $r->kelas->nama_kelas ?? '-' }}
                        </p>
                        <p class="text-xs text-slate-400">{{ $r->tahunAjaran?->labelPeriode() ?? '-' }}</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-400 py-4 text-center">Belum ada riwayat kelas.</p>
            @endforelse
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <p class="font-bold text-slate-800">Kedisiplinan (BK)</p>
                <span class="text-sm text-slate-500">Poin pelanggaran aktif: <strong class="text-slate-800">{{ $poinBersih }}</strong></span>
            </div>

            @forelse($kasusBk as $kasus)
                <div class="border-b border-slate-50 py-3 last:border-0">
                    <div class="flex items-center justify-between">
                        <p class="font-semibold text-slate-700 text-sm">{{ $kasus->nama_pelanggaran }}</p>
                        <span class="text-xs text-slate-400">{{ $kasus->tanggal_kejadian->translatedFormat('d M Y') }}</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-0.5">Kategori: {{ $kasus->kategori }} · Poin: {{ $kasus->poin }} · Status: {{ $kasus->status }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-400 py-4 text-center">Tidak ada catatan pelanggaran. 👍</p>
            @endforelse
        </div>
    </main>

</body>
</html>
