@extends('layouts.app')
@section('title', 'Dashboard Guru')

@section('content')
<div class="space-y-6">
    @if(!$tahunAjaran)
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
            ⚠️ Belum ada Tahun Ajaran aktif. Hubungi Admin/Kurikulum.
        </div>
    @endif

    @if($kelasWali)
        <div class="rounded-xl bg-brand-50 border border-brand-100 text-brand-800 px-5 py-4 flex items-center justify-between flex-wrap gap-3">
            <div>
                <p class="font-bold">🎓 Anda adalah Wali Kelas {{ $kelasWali->nama_kelas }}</p>
                <p class="text-sm text-brand-700/70">Pantau kehadiran & jurnal mengajar kelas Anda.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('walikelas.absensi-bulanan') }}" class="btn-outline bg-white">Rekap Absensi</a>
                <a href="{{ route('walikelas.jurnal-kelas') }}" class="btn-outline bg-white">Jurnal Kelas</a>
            </div>
        </div>

        <div class="card p-5">
            <p class="font-bold text-slate-800 mb-1">🚩 Siswa Alfa Hari Ini &mdash; Kelas {{ $kelasWali->nama_kelas }}</p>
            <p class="text-xs text-slate-400 mb-4">Berdasarkan Absensi Kelas &mdash; status dari guru mapel dengan jam paling akhir yang mengisi hari ini.</p>
            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead><tr><th>Nama Siswa</th><th>Menurut Mapel</th><th>Jam</th></tr></thead>
                    <tbody>
                        @forelse($siswaAlfaHariIni as $a)
                        <tr>
                            <td class="font-medium">{{ $a['siswa']->nama ?? '-' }}</td>
                            <td class="text-slate-500">{{ $a['mapel'] ?? '-' }}</td>
                            <td class="text-slate-500">{{ $a['jam_ke'] ? "Jam ke-{$a['jam_ke']}" : '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-emerald-600 py-6">🎉 Tidak ada siswa Alfa hari ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <p class="font-bold text-slate-800">Jadwal Mengajar Hari Ini</p>
            <a href="{{ route('mengajar.index') }}" class="text-sm font-semibold text-brand-600 hover:underline">Lihat semua &rarr;</a>
        </div>

        @if($jadwalHariIni->isEmpty())
            <p class="text-sm text-slate-400 py-6 text-center">Tidak ada jadwal mengajar untuk hari ini.</p>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($jadwalHariIni as $sesi)
                <a href="{{ route('mengajar.form', $sesi['ids']) }}" class="border border-slate-200 rounded-xl p-4 hover:border-brand-400 hover:bg-brand-50/40 transition block">
                    <p class="text-xs font-bold text-brand-600 mb-1">
                        @if($sesi['jam_awal']->id === $sesi['jam_akhir']->id)
                            {{ $sesi['jam_awal']->label }}
                        @else
                            Jam ke-{{ $sesi['jam_awal']->jam_ke }} s.d ke-{{ $sesi['jam_akhir']->jam_ke }}
                        @endif
                    </p>
                    <p class="font-semibold text-slate-800">Kelas {{ $sesi['kelas']->nama_kelas }}</p>
                    <p class="text-sm text-slate-500">{{ $sesi['mapel']->nama_mapel }}</p>
                </a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-4">Jurnal Terakhir Saya</p>
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Tanggal</th><th>Kelas</th><th>Mapel</th><th>Materi</th></tr></thead>
                <tbody>
                    @forelse($jurnalTerakhir as $j)
                    <tr>
                        <td>{{ $j->tanggal->translatedFormat('d M Y') }}</td>
                        <td>{{ $j->kelas->nama_kelas }}</td>
                        <td>{{ $j->mapel->nama_mapel }}</td>
                        <td class="text-slate-500">{{ \Illuminate\Support\Str::limit($j->materi, 50) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-slate-400 py-6">Belum ada jurnal.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
