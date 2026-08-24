@extends('layouts.app')
@section('title', 'Rekapitulasi')

@section('content')
<div class="space-y-6">
    <div class="card p-5 no-print">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Bulan</label>
                <select name="bulan" class="input" onchange="this.form.submit()">
                    @foreach(range(1,12) as $b)
                        <option value="{{ $b }}" {{ $b === $bulan ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($b)->translatedFormat('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tahun</label>
                <select name="tahun" class="input" onchange="this.form.submit()">
                    @foreach(range(now()->year - 1, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ $y === $tahun ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </form>
        <p class="text-xs text-slate-400 mt-3">
            <i class="fa-solid fa-calendar-days mr-1.5"></i> Hari ini: <b class="text-slate-500">{{ now()->translatedFormat('l, d F Y') }}</b>
            &middot; bulan &amp; tahun di atas otomatis mengikuti tanggal server saat halaman ini dibuka.
        </p>
    </div>

    @if(!$tahunAjaran)
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm no-print">
            <i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Belum ada Tahun Ajaran aktif, jadi jumlah "seharusnya" belum bisa dihitung dari jadwal.
        </div>
    @endif

    <div class="card p-5 print-section" id="print-rekap-guru">
        <x-kop-surat />

        <div class="flex items-center justify-between mb-1 flex-wrap gap-2">
            <p class="font-extrabold text-slate-800 text-lg">Rekapitulasi Jurnal Mengajar</p>
            <button type="button" onclick="cetakBagian('print-rekap-guru')" class="btn-outline no-print"><i class="fa-solid fa-print mr-1.5"></i> Cetak Rekap Guru</button>
        </div>
        <p class="text-sm text-slate-400 mb-4">
            Bulan {{ \Carbon\Carbon::create()->month($bulan)->translatedFormat('F') }} {{ $tahun }} &middot;
            "Seharusnya" dihitung dari jadwal pelajaran per SESI mengajar (jam berurutan = 1 sesi = 1 jurnal), bukan per jam.
        </p>

        <div class="overflow-x-auto -mx-5">
            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="border border-slate-200 px-2 py-2 w-8">No</th>
                        <th class="border border-slate-200 px-2 py-2 sticky left-0 bg-slate-50 text-left min-w-[170px]">Guru</th>
                        @for($t = 1; $t <= $jumlahHari; $t++)
                            <th class="border border-slate-200 px-1 py-2 w-9">{{ $t }}</th>
                        @endfor
                        <th class="border border-slate-200 px-2 py-2 bg-emerald-50">Terisi</th>
                        <th class="border border-slate-200 px-2 py-2 bg-slate-100">Seharusnya</th>
                        <th class="border border-slate-200 px-2 py-2 bg-indigo-50">%</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rekapGuru as $r)
                    <tr class="hover:bg-slate-50">
                        <td class="border border-slate-200 px-2 py-1.5 text-center">{{ $loop->iteration }}</td>
                        <td class="border border-slate-200 px-2 py-1.5 sticky left-0 bg-white font-medium whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <x-initial-avatar :nama="$r['guru']->name" />
                                {{ $r['guru']->name }}
                            </div>
                        </td>
                        @for($t = 1; $t <= $jumlahHari; $t++)
                            @php $h = $r['harian'][$t]; @endphp
                            <td class="border border-slate-200 text-center
                                @if($h['seharusnya'] === 0) text-slate-300
                                @elseif($h['terisi'] === $h['seharusnya']) text-emerald-600 font-bold bg-emerald-50/40
                                @else text-red-500 font-bold bg-red-50/40
                                @endif"
                                @if($h['seharusnya'] > 0) title="{{ $h['terisi'] }} dari {{ $h['seharusnya'] }} sesi terisi tanggal {{ $t }}" @endif>
                                @if($h['seharusnya'] === 0)
                                    &middot;
                                @else
                                    {{ $h['terisi'] }}/{{ $h['seharusnya'] }}
                                @endif
                            </td>
                        @endfor
                        <td class="border border-slate-200 text-center font-bold bg-emerald-50/50">{{ $r['total_terisi'] }}</td>
                        <td class="border border-slate-200 text-center font-bold bg-slate-100">{{ $r['total_seharusnya'] }}</td>
                        <td class="border border-slate-200 text-center font-bold
                            {{ $r['persen'] === null ? 'text-slate-300' : ($r['persen'] >= 90 ? 'text-emerald-600 bg-emerald-50/50' : ($r['persen'] >= 60 ? 'text-amber-600 bg-amber-50/50' : 'text-red-500 bg-red-50/50')) }}">
                            {{ $r['persen'] !== null ? $r['persen'].'%' : '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ $jumlahHari + 5 }}" class="text-center text-slate-400 py-8">Belum ada data guru / jadwal.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex gap-6 mt-4 text-xs text-slate-500 flex-wrap">
            <span><b class="text-emerald-600">Hijau</b> = semua sesi hari itu sudah terisi jurnalnya</span>
            <span><b class="text-red-500">Merah</b> = ada sesi yang belum terisi</span>
            <span>&middot; = tidak ada jadwal mengajar hari itu</span>
            <span class="text-slate-400">Hover angka untuk detail tanggal.</span>
        </div>

        <x-blok-tanda-tangan
            jabatan="Kepala Sekolah"
            :nama="$pengaturanSekolahGlobal->nama_kepala_sekolah"
            :nip="$pengaturanSekolahGlobal->nip_kepala_sekolah"
        />
    </div>

    <div class="card p-5 print-section" id="print-rekap-kelas">
        <x-kop-surat />

        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <p class="font-bold text-slate-800">Rekap Per Kelas</p>
            <button type="button" onclick="cetakBagian('print-rekap-kelas')" class="btn-outline no-print"><i class="fa-solid fa-print mr-1.5"></i> Cetak Rekap Kelas</button>
        </div>
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th class="w-10">No</th><th>Kelas</th><th>Siswa</th><th>Jurnal Terisi</th><th>Total Alfa</th></tr></thead>
                <tbody>
                    @foreach($rekapKelas as $r)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="font-semibold"><x-kelas-badge :nama="$r['kelas']->nama_kelas" /></td>
                        <td>{{ $r['kelas']->siswas_count }}</td>
                        <td>{{ $r['jumlah_jurnal'] }}</td>
                        <td class="font-bold {{ $r['total_alfa'] > 0 ? 'text-red-500' : 'text-slate-400' }}">{{ $r['total_alfa'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <x-blok-tanda-tangan
            jabatan="Kepala Sekolah"
            :nama="$pengaturanSekolahGlobal->nama_kepala_sekolah"
            :nip="$pengaturanSekolahGlobal->nip_kepala_sekolah"
        />
    </div>
</div>
@endsection
