@extends('layouts.app')
@section('title', 'Persiapan Tahun Ajaran '.$tahunAjaran->nama)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="card p-6">
        @if($siapDiaktifkan)
            <span class="badge bg-emerald-50 text-emerald-700"><i class="fa-solid fa-circle-check mr-1.5"></i> Siap diaktifkan kapan saja</span>
        @else
            <span class="badge bg-brand-50 text-brand-700"><i class="fa-solid fa-circle-check mr-1.5"></i> Sudah aktif</span>
        @endif
        <p class="text-xs text-slate-400 mt-3">
            Checklist di bawah hanya PANDUAN — bukan syarat wajib. Anda tetap bisa mengaktifkan Tahun Ajaran ini
            kapan saja walau sebagian item masih <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>, selama Tahun Ajaran sebelumnya sudah ditutup & terkunci penuh.
        </p>
    </div>

    {{-- WAJIB --}}
    <div class="card p-6">
        <p class="font-bold text-slate-800 mb-3"><i class="fa-solid fa-circle-check text-emerald-600"></i> Wajib</p>
        <ul class="text-sm space-y-2">
            <li class="flex items-center gap-2"><span class="text-emerald-600"><i class="fa-solid fa-check"></i></span> Tahun Ajaran {{ $tahunAjaran->nama }} sudah dibuat</li>
            <li class="flex items-center gap-2"><span class="text-emerald-600"><i class="fa-solid fa-check"></i></span> Semester 1 tersedia</li>
            <li class="flex items-center gap-2">
                @if($semesterGenap)
                    <span class="text-emerald-600"><i class="fa-solid fa-check"></i></span> Semester 2 tersedia
                @else
                    <span class="text-amber-500"><i class="fa-solid fa-triangle-exclamation"></i></span> Semester 2 belum tersedia
                    <span class="text-xs text-slate-400">(harusnya sudah otomatis dibuat bersama Semester 1 — hubungi Admin kalau tidak ada)</span>
                @endif
            </li>
        </ul>
    </div>

    {{-- DIBUTUHKAN SEBELUM OPERASIONAL --}}
    <div class="card p-6">
        <p class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-clipboard-list mr-1.5"></i> Dibutuhkan Sebelum Operasional</p>
        <p class="text-xs text-slate-400 mb-3">Tanpa ini, guru & wali kelas belum bisa mulai bekerja di tahun ajaran ini — tapi tetap tidak menghalangi aktivasi.</p>
        <div class="grid sm:grid-cols-2 gap-3">
            <a href="{{ route('kelas.index', ['tahun_ajaran_id' => $tahunAjaran->id]) }}" class="rounded-xl border border-slate-200 p-4 hover:border-brand-300 transition">
                <p class="text-xs text-slate-400 mb-1">Struktur Kelas</p>
                <p class="text-lg font-bold text-slate-800">{{ $jumlahKelas }} kelas</p>
                <p class="text-xs {{ $jumlahKelas > 0 ? 'text-emerald-600' : 'text-amber-500' }}">
                    <i class="fa-solid {{ $jumlahKelas > 0 ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
                    {{ $jumlahKelas > 0 ? 'Sudah dibuat' : 'Belum ada kelas' }}
                </p>
            </a>
            <a href="{{ route('kelas.index', ['tahun_ajaran_id' => $tahunAjaran->id]) }}" class="rounded-xl border border-slate-200 p-4 hover:border-brand-300 transition">
                <p class="text-xs text-slate-400 mb-1">Siswa Ditempatkan</p>
                <p class="text-lg font-bold text-slate-800">{{ $jumlahSiswaDitempatkan }} siswa</p>
                <p class="text-xs {{ $jumlahSiswaDitempatkan > 0 ? 'text-emerald-600' : 'text-amber-500' }}">
                    <i class="fa-solid {{ $jumlahSiswaDitempatkan > 0 ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
                    {{ $jumlahSiswaDitempatkan > 0 ? 'Sudah ada penempatan' : 'Belum ada siswa ditempatkan' }}
                </p>
            </a>
        </div>
    </div>

    {{-- PERSIAPAN — Kenaikan Kelas --}}
    <div class="card p-6">
        <div class="flex items-center justify-between mb-1">
            <p class="font-bold text-slate-800"><i class="fa-solid fa-graduation-cap mr-1.5"></i> Kenaikan Kelas</p>
            <a href="{{ route('kenaikan-kelas.index', ['tahun_ajaran_asal_id' => $tahunSebelumnya->id ?? '']) }}" class="text-xs text-brand-600 font-semibold hover:underline">Buka Kenaikan Kelas →</a>
        </div>
        @if(!$tahunSebelumnya)
            <p class="text-sm text-slate-400">Tidak ada Tahun Ajaran sebelumnya untuk dibandingkan (mungkin ini tahun ajaran pertama).</p>
        @elseif($statusKenaikan->isEmpty())
            <p class="text-sm text-slate-400">Tahun Ajaran {{ $tahunSebelumnya->nama }} belum punya kelas/siswa untuk diproses.</p>
        @else
            <p class="text-sm mb-3 {{ $totalBelumDiproses > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                {{ $totalSiswaAsal - $totalBelumDiproses }} dari {{ $totalSiswaAsal }} siswa sudah diproses
                @if($totalBelumDiproses > 0) — {{ $totalBelumDiproses }} siswa BELUM memiliki kelas tujuan @endif
            </p>
            <div class="overflow-x-auto -mx-6">
                <table class="table-clean w-full">
                    <thead><tr><th>Kelas Asal ({{ $tahunSebelumnya->nama }})</th><th>Jumlah Siswa</th><th>Sudah Diproses</th><th>Belum</th></tr></thead>
                    <tbody>
                        @foreach($statusKenaikan as $s)
                        <tr>
                            <td class="font-medium">{{ $s['kelas']->nama_kelas }}</td>
                            <td>{{ $s['total'] }}</td>
                            <td>{{ $s['sudah'] }}</td>
                            <td>
                                @if($s['belum'] > 0)
                                    <span class="badge bg-amber-50 text-amber-700">{{ $s['belum'] }} belum</span>
                                @else
                                    <span class="badge bg-emerald-50 text-emerald-700"><i class="fa-solid fa-check mr-1.5"></i> Selesai</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- PERSIAPAN — Wali Kelas --}}
    <div class="card p-6">
        <div class="flex items-center justify-between mb-1">
            <p class="font-bold text-slate-800"><i class="fa-solid fa-chalkboard-user mr-1.5"></i> Wali Kelas</p>
            <a href="{{ route('kelas.index', ['tahun_ajaran_id' => $tahunAjaran->id]) }}" class="text-xs text-brand-600 font-semibold hover:underline">Atur Wali Kelas →</a>
        </div>
        @if($kelasList->isEmpty())
            <p class="text-sm text-slate-400">Belum ada kelas untuk diatur wali kelasnya.</p>
        @else
            <p class="text-sm mb-3 {{ $kelasBelumWali > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                {{ $jumlahKelas - $kelasBelumWali }} dari {{ $jumlahKelas }} kelas sudah punya wali kelas
                @if($kelasBelumWali > 0) — {{ $kelasBelumWali }} kelas BELUM diatur @endif
            </p>
            <div class="overflow-x-auto -mx-6">
                <table class="table-clean w-full">
                    <thead><tr><th>Kelas</th><th>Wali Kelas</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($kelasList as $k)
                        <tr>
                            <td class="font-medium">{{ $k->nama_kelas }}</td>
                            <td>{{ $k->waliKelas->name ?? '-' }}</td>
                            <td>
                                @if($k->wali_kelas_id)
                                    <span class="badge bg-emerald-50 text-emerald-700"><i class="fa-solid fa-check"></i></span>
                                @else
                                    <span class="badge bg-amber-50 text-amber-700"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Belum diatur</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- PERSIAPAN — Guru Mengajar & Jadwal --}}
    <div class="card p-6">
        <p class="font-bold text-slate-800 mb-3"><i class="fa-solid fa-chalkboard-user mr-1.5"></i> Guru Mengajar & <i class="fa-solid fa-calendar-days mr-1.5"></i> Jadwal</p>
        <div class="grid sm:grid-cols-2 gap-3">
            <a href="{{ route('kurikulum.guru-mengajar.index', ['tahun_ajaran_id' => $tahunAjaran->id]) }}" class="rounded-xl border border-slate-200 p-4 hover:border-brand-300 transition">
                <p class="text-xs text-slate-400 mb-1">Guru Mengajar</p>
                <p class="text-lg font-bold text-slate-800">{{ $totalMappingMengajar }} data</p>
                <p class="text-xs {{ $jumlahKelas > 0 && $kelasDenganMengajar === $jumlahKelas ? 'text-emerald-600' : 'text-amber-500' }}">
                    @if($jumlahKelas === 0) <i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Belum ada kelas
                    @elseif($kelasDenganMengajar === $jumlahKelas) <i class="fa-solid fa-circle-check mr-1.5"></i> Lengkap ({{ $kelasDenganMengajar }}/{{ $jumlahKelas }} kelas)
                    @else <i class="fa-solid fa-triangle-exclamation mr-1.5"></i> {{ $jumlahKelas - $kelasDenganMengajar }} kelas belum punya guru mengajar
                    @endif
                </p>
            </a>
            <a href="{{ route('jadwal.index') }}?tahun_ajaran_id={{ $tahunAjaran->id }}" class="rounded-xl border border-slate-200 p-4 hover:border-brand-300 transition">
                <p class="text-xs text-slate-400 mb-1">Jadwal</p>
                <p class="text-lg font-bold text-slate-800">{{ $jadwalTersedia ? 'Tersedia' : 'Belum ada' }}</p>
                <p class="text-xs {{ $jadwalTersedia ? 'text-emerald-600' : 'text-amber-500' }}">
                    <i class="fa-solid {{ $jadwalTersedia ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
                    {{ $jadwalTersedia ? 'Sudah tersedia' : 'Belum tersedia — buat baru atau salin dari periode sebelumnya' }}
                </p>
            </a>
        </div>
        @if(!$jadwalTersedia)
        <p class="text-xs text-slate-400 mt-3">
            Gunakan tombol "<i class="fa-solid fa-clipboard-list mr-1.5"></i> Salin Mapping Guru/Jadwal" di halaman
            <a href="{{ route('tahun-ajaran.index') }}" class="underline font-semibold">Tahun Ajaran</a>
            untuk menyalin dari periode sebelumnya, atau buat manual di menu Jadwal.
        </p>
        @endif
    </div>

    {{-- Aktivasi --}}
    <div class="card p-6">
        <p class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-rocket mr-1.5"></i> Aktivasi</p>
        @if($siapDiaktifkan)
            <p class="text-sm text-slate-500 mb-4">
                Setelah diaktifkan, {{ $tahunAjaran->nama }} Semester 1 akan menjadi periode aktif sistem —
                semua input baru (jurnal, absensi, BK, dll) otomatis masuk ke periode ini.
            </p>
            <form method="POST" action="{{ route('tahun-ajaran.aktifkan', $tahunAjaran) }}"
                  onsubmit="return confirm('Setelah diaktifkan, {{ $tahunAjaran->nama }} Semester 1 akan menjadi periode aktif sistem. Lanjutkan?')">
                @csrf
                <button class="btn-primary"><i class="fa-solid fa-circle-check mr-1.5"></i> Aktifkan Tahun Ajaran {{ $tahunAjaran->nama }}</button>
            </form>
        @else
            <p class="text-sm text-emerald-600"><i class="fa-solid fa-check mr-1.5"></i> Tahun Ajaran ini sudah aktif sekarang.</p>
        @endif
    </div>
</div>
@endsection
