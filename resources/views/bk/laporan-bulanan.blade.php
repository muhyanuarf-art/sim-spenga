@extends('layouts.app')
@section('title', 'Laporan Bulanan BK')

@section('content')
@php
    $namaBulan = \Carbon\Carbon::create()->month($bulan)->translatedFormat('F');
    $selisih = $ringkasan['kasus'] - $ringkasan['kasus_bulan_lalu'];
@endphp

<div class="space-y-6">

    <x-bk-tab-catatan />

    {{-- ================= Penyaring ================= --}}
    <div class="card p-5 no-print">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Bulan</label>
                <select name="bulan" class="input" onchange="this.form.submit()">
                    @foreach(range(1, 12) as $b)
                        <option value="{{ $b }}" @selected($b === $bulan)>{{ \Carbon\Carbon::create()->month($b)->translatedFormat('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tahun</label>
                <select name="tahun" class="input" onchange="this.form.submit()">
                    @foreach(range(now()->year - 1, now()->year + 1) as $y)
                        <option value="{{ $y }}" @selected($y === $tahun)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" onclick="cetakBagian('print-laporan-bk')" class="btn-outline">
                <i class="fa-solid fa-print mr-1.5"></i> Cetak / Export PDF
            </button>
        </form>
    </div>

    {{-- ================= Ringkasan di layar ================= --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 no-print">
        <div class="card p-4">
            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Kasus Bulan Ini</p>
            <p class="text-2xl font-extrabold text-rose-600 mt-1">{{ $ringkasan['kasus'] }}</p>
            <p class="text-[11px] mt-1 {{ $selisih > 0 ? 'text-rose-500' : ($selisih < 0 ? 'text-emerald-600' : 'text-slate-400') }}">
                @if($selisih > 0)
                    <i class="fa-solid fa-arrow-up"></i> {{ $selisih }} lebih banyak dari bulan lalu
                @elseif($selisih < 0)
                    <i class="fa-solid fa-arrow-down"></i> {{ abs($selisih) }} lebih sedikit dari bulan lalu
                @else
                    sama dengan bulan lalu ({{ $ringkasan['kasus_bulan_lalu'] }})
                @endif
            </p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Sudah Ditangani</p>
            <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $ringkasan['kasus_selesai'] }}</p>
            <p class="text-[11px] text-slate-400 mt-1">{{ $ringkasan['kasus_belum_selesai'] }} masih belum selesai</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Pembinaan</p>
            <p class="text-2xl font-extrabold text-violet-600 mt-1">{{ $ringkasan['pembinaan'] }}</p>
            <p class="text-[11px] text-slate-400 mt-1">{{ $ringkasan['pembinaan_selesai'] }} sudah selesai</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Pemanggilan Ortu</p>
            <p class="text-2xl font-extrabold text-sky-600 mt-1">{{ $ringkasan['pemanggilan'] }}</p>
            <p class="text-[11px] text-slate-400 mt-1">{{ $ringkasan['ortu_hadir'] }} orang tua hadir</p>
        </div>
    </div>

    {{-- ================= LEMBAR LAPORAN (yang dicetak) ================= --}}
    <div class="card p-5 print-section" id="print-laporan-bk">
        <x-kop-surat />

        <div class="text-center mb-4">
            <p class="font-extrabold tracking-[0.2em] text-slate-800 text-sm uppercase">Laporan Bulanan Bimbingan Konseling</p>
            <p class="font-extrabold text-lg text-slate-800 uppercase">{{ $namaBulan }} {{ $tahun }}</p>
        </div>

        <div class="grid sm:grid-cols-2 gap-x-8 gap-y-1 text-xs text-slate-600 mb-5">
            <p><span class="inline-block w-28 text-slate-400">Sekolah</span>: {{ $pengaturanSekolahGlobal->nama_sekolah ?: '-' }}</p>
            <p><span class="inline-block w-28 text-slate-400">Periode</span>: <b class="text-slate-800">{{ $namaBulan }} {{ $tahun }}</b></p>
            <p><span class="inline-block w-28 text-slate-400">Guru BK</span>: {{ $guruBk->name ?? '-' }}</p>
            <p><span class="inline-block w-28 text-slate-400">Cakupan</span>:
                {{ $kelasList->isEmpty() ? 'Seluruh kelas' : $kelasList->pluck('nama_kelas')->implode(', ') }}</p>
        </div>

        {{-- ===== A. Rekap kegiatan ===== --}}
        <div class="cetak-utuh mb-5">
            <p class="font-extrabold text-slate-800 text-xs uppercase tracking-wide mb-2 pb-1 border-b border-slate-300">
                A. Rekap Kegiatan Bimbingan Konseling
            </p>
            <table class="w-full text-[11px] border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-700">
                        <th class="border border-slate-300 px-1 py-2 w-8">No.</th>
                        <th class="border border-slate-300 px-2 py-2 text-left">Kegiatan</th>
                        <th class="border border-slate-300 px-2 py-2 w-20">Jumlah</th>
                        <th class="border border-slate-300 px-2 py-2 text-left">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach([
                        ['Kasus / pelanggaran tercatat', $ringkasan['kasus'], $ringkasan['siswa_terlibat'].' peserta didik terlibat, total '.$ringkasan['total_poin'].' poin'],
                        ['Kasus sudah ditangani (selesai)', $ringkasan['kasus_selesai'], $ringkasan['kasus_belum_selesai'].' kasus masih dalam penanganan'],
                        ['Pembinaan dilaksanakan', $ringkasan['pembinaan'], $ringkasan['pembinaan_selesai'].' pembinaan dinyatakan selesai'],
                        ['Pengurangan poin diberikan', $ringkasan['pengurangan'], 'total '.$ringkasan['poin_dikurangi'].' poin dikurangi atas perubahan perilaku'],
                        ['Pemanggilan orang tua', $ringkasan['pemanggilan'], $ringkasan['ortu_hadir'].' pertemuan dihadiri orang tua'],
                    ] as $i => [$kegiatan, $jumlah, $ket])
                        <tr>
                            <td class="border border-slate-300 text-center text-slate-500">{{ $i + 1 }}</td>
                            <td class="border border-slate-300 px-2 py-1.5">{{ $kegiatan }}</td>
                            <td class="border border-slate-300 text-center font-bold tabular-nums">{{ $jumlah }}</td>
                            <td class="border border-slate-300 px-2 py-1.5 text-slate-500">{{ $ket }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ===== B. Sebaran pelanggaran ===== --}}
        <div class="cetak-utuh mb-5">
            <p class="font-extrabold text-slate-800 text-xs uppercase tracking-wide mb-2 pb-1 border-b border-slate-300">
                B. Sebaran Pelanggaran
            </p>

            @if($ringkasan['kasus'] === 0)
                <p class="text-[11px] text-slate-500 italic px-1 py-2">Tidak ada pelanggaran tercatat pada bulan ini.</p>
            @else
                <div class="grid md:grid-cols-2 gap-5">
                    <div>
                        <p class="text-[11px] font-semibold text-slate-600 mb-1">Menurut kategori</p>
                        <table class="w-full text-[11px] border-collapse">
                            <thead>
                                <tr class="bg-slate-100 text-slate-700">
                                    <th class="border border-slate-300 px-2 py-1.5 text-left">Kategori</th>
                                    <th class="border border-slate-300 px-2 py-1.5 w-16">Jumlah</th>
                                    <th class="border border-slate-300 px-2 py-1.5 w-16">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($perKategori as $kategori => $jumlah)
                                    <tr>
                                        <td class="border border-slate-300 px-2 py-1.5">{{ $kategori }}</td>
                                        <td class="border border-slate-300 text-center tabular-nums">{{ $jumlah }}</td>
                                        <td class="border border-slate-300 text-center tabular-nums">
                                            {{ $ringkasan['kasus'] > 0 ? round($jumlah / $ringkasan['kasus'] * 100) : 0 }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <p class="text-[11px] font-semibold text-slate-600 mb-1">Jenis pelanggaran terbanyak</p>
                        <table class="w-full text-[11px] border-collapse">
                            <thead>
                                <tr class="bg-slate-100 text-slate-700">
                                    <th class="border border-slate-300 px-2 py-1.5 text-left">Jenis</th>
                                    <th class="border border-slate-300 px-2 py-1.5 w-16">Kali</th>
                                    <th class="border border-slate-300 px-2 py-1.5 w-16">Poin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($jenisTerbanyak->take(5) as $j)
                                    <tr>
                                        <td class="border border-slate-300 px-2 py-1.5">{{ $j['nama'] }}</td>
                                        <td class="border border-slate-300 text-center tabular-nums font-semibold">{{ $j['jumlah'] }}</td>
                                        <td class="border border-slate-300 text-center tabular-nums">{{ $j['poin'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- ===== C. Rekap per kelas ===== --}}
        <div class="cetak-utuh mb-5">
            <p class="font-extrabold text-slate-800 text-xs uppercase tracking-wide mb-2 pb-1 border-b border-slate-300">
                C. Rekap per Kelas
            </p>
            @if($perKelas->isEmpty())
                <p class="text-[11px] text-slate-500 italic px-1 py-2">Tidak ada kelas dengan pelanggaran pada bulan ini.</p>
            @else
                <table class="w-full text-[11px] border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700">
                            <th class="border border-slate-300 px-1 py-2 w-8">No.</th>
                            <th class="border border-slate-300 px-2 py-2 text-left">Kelas</th>
                            <th class="border border-slate-300 px-2 py-2 w-24">Jumlah Kasus</th>
                            <th class="border border-slate-300 px-2 py-2 w-24">Peserta Didik</th>
                            <th class="border border-slate-300 px-2 py-2 w-20">Total Poin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($perKelas as $k)
                            <tr>
                                <td class="border border-slate-300 text-center text-slate-500">{{ $loop->iteration }}</td>
                                <td class="border border-slate-300 px-2 py-1.5 font-medium">{{ $k['kelas'] }}</td>
                                <td class="border border-slate-300 text-center tabular-nums font-semibold">{{ $k['kasus'] }}</td>
                                <td class="border border-slate-300 text-center tabular-nums">{{ $k['siswa'] }}</td>
                                <td class="border border-slate-300 text-center tabular-nums text-rose-600 font-semibold">{{ $k['poin'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50 font-bold text-slate-700">
                            <td colspan="2" class="border border-slate-300 px-2 py-1.5 text-right">JUMLAH</td>
                            <td class="border border-slate-300 text-center tabular-nums">{{ $perKelas->sum('kasus') }}</td>
                            <td class="border border-slate-300 text-center tabular-nums">{{ $ringkasan['siswa_terlibat'] }}</td>
                            <td class="border border-slate-300 text-center tabular-nums">{{ $ringkasan['total_poin'] }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>

        {{-- ===== D. Daftar peserta didik yang ditangani ===== --}}
        <div class="cetak-utuh">
            <p class="font-extrabold text-slate-800 text-xs uppercase tracking-wide mb-2 pb-1 border-b border-slate-300">
                D. Peserta Didik yang Ditangani Bulan Ini
            </p>
            @if($daftarSiswa->isEmpty())
                <p class="text-[11px] text-slate-500 italic px-1 py-2">
                    Tidak ada peserta didik yang ditangani pada bulan ini.
                </p>
            @else
                <div class="overflow-x-auto -mx-5">
                    <table class="w-full text-[11px] border-collapse">
                        <thead>
                            <tr class="bg-slate-100 text-slate-700">
                                <th class="border border-slate-300 px-1 py-2 w-8">No.</th>
                                <th class="border border-slate-300 px-2 py-2 text-left min-w-[160px]">Nama Peserta Didik</th>
                                <th class="border border-slate-300 px-2 py-2 w-16">Kelas</th>
                                <th class="border border-slate-300 px-1 py-2 w-14">Kasus</th>
                                <th class="border border-slate-300 px-1 py-2 w-14">Poin</th>
                                <th class="border border-slate-300 px-1 py-2 w-16 leading-tight">Pembi-<br>naan</th>
                                <th class="border border-slate-300 px-1 py-2 w-16 leading-tight">Poin<br>Dikurangi</th>
                                <th class="border border-slate-300 px-1 py-2 w-16 leading-tight">Panggil<br>Ortu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($daftarSiswa as $r)
                                <tr>
                                    <td class="border border-slate-300 text-center text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="border border-slate-300 px-2 py-1.5 font-medium">{{ $r['siswa']->nama }}</td>
                                    <td class="border border-slate-300 text-center">{{ $r['siswa']->kelas->nama_kelas ?? '-' }}</td>
                                    <td class="border border-slate-300 text-center tabular-nums">{{ $r['kasus'] ?: '–' }}</td>
                                    <td class="border border-slate-300 text-center tabular-nums font-semibold text-rose-600">{{ $r['poin'] ?: '–' }}</td>
                                    <td class="border border-slate-300 text-center tabular-nums">{{ $r['pembinaan'] ?: '–' }}</td>
                                    <td class="border border-slate-300 text-center tabular-nums text-emerald-700">{{ $r['pengurangan'] ?: '–' }}</td>
                                    <td class="border border-slate-300 text-center tabular-nums">{{ $r['pemanggilan'] ?: '–' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <p class="mt-4 text-[10px] text-slate-500">
            Kasus dihitung menurut <b>tanggal kejadian</b>; pembinaan, pengurangan poin, dan pemanggilan menurut
            <b>tanggal pelaksanaan</b>. Kasus yang dibatalkan tidak ikut dihitung. Angka pada laporan ini berasal
            dari catatan yang sama dengan tab-tab di Buku Catatan BK untuk bulan yang sama.
        </p>

        <x-blok-tanda-tangan-dua
            jabatan-kanan="Guru BK"
            :nama-kanan="$guruBk->name ?? null"
            :nip-kanan="$guruBk->nip ?? null"
        />
    </div>
</div>
@endsection
