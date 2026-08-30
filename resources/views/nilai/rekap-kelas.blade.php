@extends('layouts.app')
@section('title', 'Nilai Rapor Kelas ' . $kelas->nama_kelas)

@section('content')
@php
    $bolehPilihKelas = $daftarKelas->count() > 1;
    $jumlahKolom = 4 + $mapels->count() + 4; // identitas + mapel + jumlah/rata/peringkat/belum tuntas
@endphp

<div class="space-y-6">

    {{-- ================= Penyaring ================= --}}
    <div class="card p-4 no-print">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            @if($bolehPilihKelas)
                <div>
                    <label class="label">Kelas</label>
                    <select name="kelas_id" class="select" onchange="this.form.submit()">
                        @foreach($daftarKelas as $k)
                            <option value="{{ $k->id }}" @selected($k->id === $kelas->id)>{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <button type="button" onclick="cetakBagian('print-rekap-nilai')" class="btn-outline">
                <i class="fa-solid fa-print mr-1.5"></i> Cetak / Export PDF
            </button>
            <a href="{{ route('nilai.per-mapel', ['kelas_id' => $kelas->id]) }}" class="btn-outline">
                <i class="fa-solid fa-list-ol mr-1.5"></i> Lihat Rincian per Mata Pelajaran
            </a>
        </form>

        @php $belumFinal = $ringkasanMapel->filter(fn ($r) => ! $r['header']?->isFinal()); @endphp
        @if($belumFinal->isNotEmpty())
            <p class="alert alert-warning mt-3 mb-0">
                <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                <span>
                    <b>{{ $belumFinal->count() }} mata pelajaran belum difinalisasi</b> guru pengampunya
                    ({{ $belumFinal->take(6)->map(fn ($r) => $r['mapel']->nama_mapel)->implode(', ') }}{{ $belumFinal->count() > 6 ? ', dan lainnya' : '' }}).
                    Angka pada mata pelajaran tersebut masih dapat berubah.
                </span>
            </p>
        @endif
    </div>

    {{-- ================= Lembar rekap ================= --}}
    <div class="card p-5 print-section" id="print-rekap-nilai">
        <x-kop-surat />

        <div class="text-center mb-4">
            <p class="font-extrabold tracking-[0.2em] text-slate-800 text-sm uppercase">Rekapitulasi Nilai Akhir (Rapor)</p>
            <p class="font-extrabold text-lg text-slate-800 uppercase">Kelas {{ $kelas->nama_kelas }}</p>
        </div>

        <div class="grid sm:grid-cols-2 gap-x-8 gap-y-1 text-xs text-slate-600 mb-4">
            <p><span class="inline-block w-28 text-slate-400">Kelas / Fase</span>: <b class="text-slate-800">{{ $kelas->nama_kelas }} / {{ $kelas->fase() }}</b></p>
            <p><span class="inline-block w-28 text-slate-400">Wali Kelas</span>: {{ $kelas->waliKelas->name ?? '-' }}</p>
            <p><span class="inline-block w-28 text-slate-400">Semester</span>: {{ $periode->semester }}</p>
            <p><span class="inline-block w-28 text-slate-400">Tahun Pelajaran</span>: {{ $periode->nama }}</p>
            <p><span class="inline-block w-28 text-slate-400">KKTP</span>: <b class="text-slate-800">{{ $skema->labelKktp() }}</b></p>
            <p><span class="inline-block w-28 text-slate-400">Jumlah Siswa</span>: {{ $baris->count() }}</p>
        </div>

        <div class="overflow-x-auto -mx-5">
            <table class="w-full text-[11px] border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-700">
                        <th rowspan="2" class="border border-slate-300 px-1 py-2 w-8">NO.</th>
                        <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[86px]">NISN</th>
                        <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[52px]">NIS</th>
                        <th rowspan="2" class="border border-slate-300 px-2 py-2 text-left min-w-[170px] sticky left-0 bg-slate-100 z-10">N A M A</th>
                        <th colspan="{{ max(1, $mapels->count()) }}" class="border border-slate-300 px-2 py-1.5 bg-sky-50 tracking-widest">NILAI AKHIR PER MATA PELAJARAN</th>
                        <th colspan="4" class="border border-slate-300 px-2 py-1.5 bg-emerald-50 tracking-widest">R E K A P</th>
                    </tr>
                    <tr class="bg-slate-50 text-slate-700">
                        @forelse($mapels as $m)
                            {{-- Yang ditulis di sini SINGKATANNYA (kolom `kode`,
                                 mis. PJOK), bukan nama lengkapnya.

                                 Dulu nama lengkap diputar 90° di dalam kotak
                                 setinggi 112px. Selama namanya pendek itu rapi,
                                 tetapi "Pendidikan Jasmani, Olahraga dan
                                 Kesehatan" jauh lebih panjang dari 112px — teks
                                 yang diputar itu meluber ke bawah dan menimpa
                                 baris siswa pertama.

                                 Dengan singkatan, teksnya cukup ditulis mendatar:
                                 tidak ada lagi tinggi tetap yang bisa dilampaui,
                                 dan kepala tabelnya jadi jauh lebih pendek serta
                                 lebih mudah dibaca. Nama lengkapnya tetap terbaca
                                 lewat tooltip. --}}
                            <th class="border border-slate-300 px-1 py-2 align-middle min-w-[38px] max-w-[56px] leading-tight break-words"
                                title="{{ $m->nama_mapel }}">{{ $m->labelRingkas() }}</th>
                        @empty
                            <th class="border border-slate-300 px-2 py-2 text-slate-400 font-normal">—</th>
                        @endforelse
                        <th class="border border-slate-300 px-1 py-2 w-12 bg-emerald-50">JML</th>
                        <th class="border border-slate-300 px-1 py-2 w-12 bg-emerald-50">RATA</th>
                        <th class="border border-slate-300 px-1 py-2 w-12 bg-emerald-50 leading-tight">PE-<br>RING-<br>KAT</th>
                        <th class="border border-slate-300 px-1 py-2 w-12 bg-rose-50 leading-tight" title="Jumlah mata pelajaran dengan nilai di bawah KKTP">BLM<br>TUNTAS</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($baris as $b)
                        <tr class="hover:bg-brand-50/40">
                            <td class="border border-slate-300 text-center text-slate-500">{{ $loop->iteration }}</td>
                            <td class="border border-slate-300 px-2 text-center tabular-nums">{{ $b['siswa']->nisn ?: '-' }}</td>
                            <td class="border border-slate-300 px-2 text-center tabular-nums">{{ $b['siswa']->nis }}</td>
                            <td class="border border-slate-300 px-2 py-1 font-medium whitespace-nowrap sticky left-0 bg-white z-10">{{ $b['siswa']->nama }}</td>

                            @forelse($mapels as $m)
                                @php $n = $b['nilai']->get($m->id); @endphp
                                <td class="border border-slate-300 text-center tabular-nums font-semibold
                                    {{ $n?->nilai_akhir === null ? 'text-slate-300' : ($n->tuntas ? 'text-slate-800' : 'text-rose-600') }}"
                                    @if($n?->nilai_akhir !== null)
                                        title="{{ $m->nama_mapel }}: {{ $n->nilai_akhir }} — predikat {{ $n->predikat }}{{ $n->lengkap ? '' : ' (masih sementara, komponen belum lengkap)' }}"
                                    @endif>
                                    {{ $n?->nilaiRapor() ?? '–' }}@if($n && $n->nilai_akhir !== null && ! $n->lengkap)<span class="text-amber-500">*</span>@endif
                                </td>
                            @empty
                                <td class="border border-slate-300 text-center text-slate-300">–</td>
                            @endforelse

                            <td class="border border-slate-300 text-center tabular-nums bg-emerald-50/50">{{ $b['jumlah'] ? round($b['jumlah']) : '–' }}</td>
                            <td class="border border-slate-300 text-center tabular-nums font-bold bg-emerald-50/50">{{ $b['rata'] !== null ? number_format($b['rata'], 2, ',', '') : '–' }}</td>
                            <td class="border border-slate-300 text-center tabular-nums font-bold bg-emerald-50/50">{{ $b['peringkat'] ?? '–' }}</td>
                            <td class="border border-slate-300 text-center tabular-nums font-bold {{ $b['belum_tuntas'] > 0 ? 'bg-rose-50 text-rose-700' : 'bg-slate-50 text-slate-400' }}">
                                {{ $b['belum_tuntas'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $jumlahKolom }}" class="empty-state border border-slate-300">
                                Belum ada siswa aktif di kelas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if($baris->isNotEmpty() && $mapels->isNotEmpty())
                    <tfoot>
                        <tr class="bg-slate-50 font-semibold text-slate-700">
                            <td colspan="4" class="border border-slate-300 px-2 py-1.5 text-right sticky left-0 bg-slate-50 z-10">Rata-rata kelas</td>
                            @foreach($ringkasanMapel as $r)
                                <td class="border border-slate-300 text-center tabular-nums">{{ $r['rata'] !== null ? round($r['rata']) : '–' }}</td>
                            @endforeach
                            <td class="border border-slate-300"></td>
                            <td class="border border-slate-300 text-center tabular-nums">
                                {{ $baris->pluck('rata')->filter(fn ($r) => $r !== null)->avg() !== null
                                    ? number_format($baris->pluck('rata')->filter(fn ($r) => $r !== null)->avg(), 2, ',', '') : '–' }}
                            </td>
                            <td class="border border-slate-300"></td>
                            <td class="border border-slate-300 text-center tabular-nums">{{ $baris->sum('belum_tuntas') }}</td>
                        </tr>
                        <tr class="bg-rose-50/60 text-rose-700">
                            <td colspan="4" class="border border-slate-300 px-2 py-1.5 text-right sticky left-0 bg-rose-50 z-10">Siswa belum tuntas</td>
                            @foreach($ringkasanMapel as $r)
                                <td class="border border-slate-300 text-center tabular-nums">{{ $r['belum_tuntas'] }}</td>
                            @endforeach
                            <td colspan="4" class="border border-slate-300"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <div class="mt-4 text-[11px] text-slate-500 space-y-1.5">
            <p>
                Nilai pada tabel ini <b>otomatis</b> berasal dari Daftar Nilai yang diisi guru mata pelajaran —
                wali kelas tidak perlu memasukkan ulang. Nilai merah berarti di bawah KKTP ({{ $skema->kktpMin }}).
                Tanda <span class="text-amber-500 font-bold">*</span> berarti komponen nilainya belum lengkap sehingga angkanya masih sementara.
            </p>
            <p>
                <b>Peringkat</b> dihitung dari rata-rata nilai akhir seluruh mata pelajaran. Nilai yang sama
                mendapat peringkat yang sama. Siswa yang belum memiliki nilai sama sekali tidak diberi peringkat.
            </p>
            <p class="print:hidden">
                <b>Predikat:</b>
                @foreach($skema->rentangPredikat() as $huruf => $r)
                    <span class="inline-block mr-3">{{ $huruf }} = {{ $r['dari'] }}–{{ $r['sampai'] }} ({{ $r['label'] }})</span>
                @endforeach
            </p>
        </div>

        <x-blok-tanda-tangan-dua
            jabatan-kanan="Wali Kelas {{ $kelas->nama_kelas }}"
            :nama-kanan="$kelas->waliKelas->name ?? null"
            :nip-kanan="$kelas->waliKelas->nip ?? null"
        />
    </div>

    {{-- ================= Status pengisian tiap mapel (layar saja) ================= --}}
    <x-panel judul="Status Pengisian per Mata Pelajaran"
             deskripsi="Siapa gurunya, sudah difinalisasi atau belum."
             ikon="fa-clipboard-check" rapat class="no-print">
        <div class="overflow-x-auto">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th class="w-10">No</th>
                        <th>Mata Pelajaran</th>
                        <th>Guru Pengampu</th>
                        <th>Siswa Dinilai</th>
                        <th>Rata-rata</th>
                        <th>Belum Tuntas</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ringkasanMapel as $r)
                        <tr>
                            <td class="text-center text-slate-400">{{ $loop->iteration }}</td>
                            <td class="font-medium text-slate-700">{{ $r['mapel']->nama_mapel }}</td>
                            <td class="text-slate-600">{{ $r['header']?->guru?->name ?? '-' }}</td>
                            <td class="tabular-nums">{{ $r['terisi'] }} / {{ $baris->count() }}</td>
                            <td class="tabular-nums font-semibold">{{ $r['rata'] !== null ? number_format($r['rata'], 2, ',', '') : '–' }}</td>
                            <td>
                                @if($r['belum_tuntas'] > 0)
                                    <span class="badge bg-rose-50 text-rose-700">{{ $r['belum_tuntas'] }} siswa</span>
                                @else
                                    <span class="text-slate-400">–</span>
                                @endif
                            </td>
                            <td>
                                @if($r['header']?->isFinal())
                                    <span class="badge bg-emerald-50 text-emerald-700"><i class="fa-solid fa-lock mr-1"></i> Final</span>
                                @elseif($r['terisi'] > 0)
                                    <span class="badge bg-amber-50 text-amber-700">Draft</span>
                                @else
                                    <span class="badge bg-slate-100 text-slate-500">Belum diisi</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state">Belum ada mata pelajaran yang dipetakan untuk kelas ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-panel>
</div>
@endsection
