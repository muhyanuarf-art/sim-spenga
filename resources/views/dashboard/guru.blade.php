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
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-brand-600 via-brand-600 to-indigo-500 text-white px-5 py-4 flex items-center justify-between flex-wrap gap-3 shadow-lg shadow-brand-500/20">
            <div class="relative z-10">
                <p class="font-bold flex items-center gap-2">🎓 Anda adalah Wali Kelas {{ $kelasWali->nama_kelas }}</p>
                <p class="text-sm text-white/80">Pantau kehadiran & jurnal mengajar kelas Anda.</p>
            </div>
            <div class="relative z-10 flex gap-2">
                <a href="{{ route('walikelas.absensi-bulanan') }}" class="btn-outline bg-white/95 border-transparent">Rekap Absensi</a>
                <a href="{{ route('walikelas.jurnal-kelas') }}" class="btn-outline bg-white/95 border-transparent">Jurnal Kelas</a>
            </div>
            <div class="absolute -right-6 -bottom-10 w-40 h-40 rounded-full bg-white/10 blur-2xl"></div>
        </div>

        <x-alfa-widget :data="$siswaAlfaHariIni" :title="'Siswa Alfa Hari Ini — Kelas '.$kelasWali->nama_kelas" :show-kelas="false" />
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
                @php
                    $palet = ['indigo', 'amber', 'sky', 'rose', 'teal', 'violet', 'fuchsia'];
                    $warna = $palet[crc32($sesi['mapel']->nama_mapel ?? '?') % count($palet)];
                    $terisi = $sesi['sudah_diisi'] ?? false;
                @endphp
                <a href="{{ route('mengajar.form', $sesi['ids']) }}"
                   class="relative overflow-hidden rounded-xl border p-4 transition block
                        {{ $terisi
                            ? 'border-emerald-200 bg-emerald-50/60'
                            : 'border-'.$warna.'-100 bg-gradient-to-br from-'.$warna.'-50 to-white hover:shadow-md hover:border-'.$warna.'-300' }}">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <p class="text-xs font-bold {{ $terisi ? 'text-emerald-600' : 'text-'.$warna.'-600' }}">
                            @if($sesi['jam_awal']->id === $sesi['jam_akhir']->id)
                                {{ $sesi['jam_awal']->label }}
                            @else
                                Jam ke-{{ $sesi['jam_awal']->jam_ke }} s.d ke-{{ $sesi['jam_akhir']->jam_ke }}
                            @endif
                        </p>
                        @if($terisi)
                            <span class="badge bg-emerald-100 text-emerald-700 shrink-0">Terisi</span>
                        @endif
                    </div>
                    <p class="font-semibold text-slate-800">Kelas {{ $sesi['kelas']->nama_kelas }}</p>
                    <p class="text-sm text-slate-500">{{ $sesi['mapel']->nama_mapel }}</p>
                    @if(!$terisi)
                        <div class="absolute -right-4 -bottom-6 w-20 h-20 rounded-full bg-{{ $warna }}-200/40 blur-xl pointer-events-none"></div>
                    @endif
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
                        <td class="text-slate-500">{{ $j->tanggal->translatedFormat('d M Y') }}</td>
                        <td><x-kelas-badge :nama="$j->kelas->nama_kelas" /></td>
                        <td><x-mapel-badge :nama="$j->mapel->nama_mapel" /></td>
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
