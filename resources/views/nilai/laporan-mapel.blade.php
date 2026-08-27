@extends('layouts.app')
@section('title', 'Nilai Akhir — ' . $mapel->nama_mapel)
@section('deskripsi', 'Rincian nilai formatif dan nilai akhir (rapor) satu mata pelajaran untuk kelas ' . $kelas->nama_kelas . '.')

@section('content')
@php
    $labelAkhir = $skema->labelSumatifAkhir();
    $bolehPilihKelas = $daftarKelas->count() > 1;
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
            <div>
                <label class="label">Mata Pelajaran</label>
                <select name="mapel_id" class="select" onchange="this.form.submit()">
                    @foreach($mapels as $m)
                        <option value="{{ $m->id }}" @selected($m->id === $mapel->id)>{{ $m->nama_mapel }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" onclick="cetakBagian('print-nilai-mapel')" class="btn-outline">
                <i class="fa-solid fa-print mr-1.5"></i> Cetak / Export PDF
            </button>
            <a href="{{ route('nilai.rekap-kelas', ['kelas_id' => $kelas->id]) }}" class="btn-outline">
                <i class="fa-solid fa-award mr-1.5"></i> Rekap Semua Mata Pelajaran
            </a>
        </form>

        @unless($header?->isFinal())
            <p class="alert alert-warning mt-3 mb-0">
                <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                <span>Daftar nilai mata pelajaran ini <b>belum difinalisasi</b> guru pengampunya, sehingga angkanya masih dapat berubah.</span>
            </p>
        @endunless
    </div>

    {{-- ================= Lembar laporan ================= --}}
    <div class="card p-5 print-section" id="print-nilai-mapel">
        <x-kop-surat />

        <div class="text-center mb-4">
            <p class="font-extrabold tracking-[0.2em] text-slate-800 text-sm uppercase">Nilai Akhir (Rapor)</p>
            <p class="font-extrabold text-lg text-slate-800 uppercase">{{ $mapel->nama_mapel }}</p>
        </div>

        <div class="grid sm:grid-cols-2 gap-x-8 gap-y-1 text-xs text-slate-600 mb-4">
            <p><span class="inline-block w-32 text-slate-400">Kelas / Fase</span>: <b class="text-slate-800">{{ $kelas->nama_kelas }} / {{ $kelas->fase() }}</b></p>
            <p><span class="inline-block w-32 text-slate-400">Wali Kelas</span>: {{ $kelas->waliKelas->name ?? '-' }}</p>
            <p><span class="inline-block w-32 text-slate-400">Semester</span>: {{ $periode->semester }}</p>
            <p><span class="inline-block w-32 text-slate-400">Th. Pelajaran</span>: {{ $periode->nama }}</p>
            <p><span class="inline-block w-32 text-slate-400">KKTP</span>: <b class="text-slate-800">{{ $skema->labelKktp() }}</b></p>
            <p><span class="inline-block w-32 text-slate-400">Guru Mata Pelajaran</span>: {{ $header?->guru?->name ?? '-' }}</p>
        </div>

        <div class="overflow-x-auto -mx-5">
            <table class="w-full text-[11px] border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-700">
                        <th rowspan="2" class="border border-slate-300 px-1 py-2 w-8">NO.</th>
                        <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[86px]">NISN</th>
                        <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[52px]">NIS</th>
                        <th rowspan="2" class="border border-slate-300 px-2 py-2 text-left min-w-[170px] sticky left-0 bg-slate-100 z-10">N A M A</th>
                        <th colspan="{{ $skema->jumlahTpf + 1 }}" class="border border-slate-300 px-2 py-1.5 bg-sky-50">NILAI FORMATIF</th>
                        <th colspan="3" class="border border-slate-300 px-2 py-1.5 bg-violet-50 leading-tight">SUMATIF</th>
                        <th rowspan="2" class="border border-slate-300 px-1 py-2 bg-emerald-50 w-14 leading-tight">N A<br>(RAPOR)</th>
                        <th rowspan="2" class="border border-slate-300 px-1 py-2 bg-emerald-50 w-12 leading-tight">PRE-<br>DIKAT</th>
                    </tr>
                    <tr class="bg-slate-50 text-slate-700">
                        @for($n = 1; $n <= $skema->jumlahTpf; $n++)
                            <th class="border border-slate-300 px-1 py-1.5 w-10 leading-tight">TPF {{ $n }}</th>
                        @endfor
                        <th class="border border-slate-300 px-1 py-1.5 w-11 bg-sky-50">RT</th>
                        <th class="border border-slate-300 px-1 py-1.5 w-11 bg-violet-50 leading-tight" title="Rata-rata sumatif lingkup materi (remedi diperhitungkan)">LM</th>
                        <th class="border border-slate-300 px-1 py-1.5 w-11 bg-violet-50">ASTS</th>
                        <th class="border border-slate-300 px-1 py-1.5 w-11 bg-violet-50">{{ $labelAkhir }}</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($baris as $b)
                        @php $n = $b['nilai']; @endphp
                        <tr class="hover:bg-brand-50/40">
                            <td class="border border-slate-300 text-center text-slate-500">{{ $loop->iteration }}</td>
                            <td class="border border-slate-300 px-2 text-center tabular-nums">{{ $b['siswa']->nisn ?: '-' }}</td>
                            <td class="border border-slate-300 px-2 text-center tabular-nums">{{ $b['siswa']->nis }}</td>
                            <td class="border border-slate-300 px-2 py-1 font-medium whitespace-nowrap sticky left-0 bg-white z-10">{{ $b['siswa']->nama }}</td>

                            @for($i = 1; $i <= $skema->jumlahTpf; $i++)
                                <td class="border border-slate-300 text-center tabular-nums {{ $n?->tpf($i) === null ? 'text-slate-300' : '' }}">
                                    {{ $n?->tpf($i) !== null ? round($n->tpf($i)) : '–' }}
                                </td>
                            @endfor
                            <td class="border border-slate-300 text-center tabular-nums font-bold bg-sky-50/60">
                                {{ $n?->rata_formatif !== null ? number_format($n->rata_formatif, 2) : '–' }}
                            </td>

                            <td class="border border-slate-300 text-center tabular-nums bg-violet-50/50">
                                {{ $n?->rata_sumatif_lm !== null ? number_format($n->rata_sumatif_lm, 2) : '–' }}
                            </td>
                            <td class="border border-slate-300 text-center tabular-nums bg-violet-50/50">
                                {{ $n?->asts !== null ? round($n->asts) : '–' }}
                            </td>
                            <td class="border border-slate-300 text-center tabular-nums bg-violet-50/50">
                                {{ $n?->asas !== null ? round($n->asas) : '–' }}
                            </td>

                            <td class="border border-slate-300 text-center font-extrabold text-sm tabular-nums bg-emerald-50/60
                                {{ $n?->tuntas === false ? 'text-rose-600' : 'text-slate-800' }}">
                                {{ $n?->nilaiRapor() ?? '–' }}
                            </td>
                            <td class="border border-slate-300 text-center bg-emerald-50/60">
                                <span class="badge {{ $n?->warnaPredikat() ?? 'bg-slate-100 text-slate-400' }}">{{ $n?->predikat ?? '–' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 10 + $skema->jumlahTpf }}" class="empty-state border border-slate-300">
                                Belum ada siswa aktif di kelas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 text-[11px] text-slate-500 space-y-1">
            <p>
                <b>TPF n</b> = Penilaian Formatif BAB ke-n &middot;
                <b>RT</b> = rata-rata formatif &middot;
                <b>LM</b> = rata-rata sumatif lingkup materi &middot;
                <b>{{ $labelAkhir }}</b> = {{ $skema->labelPanjangSumatifAkhir() }}
            </p>
            <p>
                Nilai akhir = ({{ $skema->komposisiFormatif }}% RT + {{ $skema->komposisiSumatifLm }}% LM) berbobot {{ $skema->bobotFormatifSumatif }}%,
                ASTS {{ $skema->bobotAsts }}%, {{ $labelAkhir }} {{ $skema->bobotAsas }}%. KKTP {{ $skema->labelKktp() }}.
            </p>
        </div>

        <x-blok-tanda-tangan-dua
            jabatan-kanan="Guru Mata Pelajaran {{ $mapel->nama_mapel }}"
            :nama-kanan="$header?->guru?->name"
            :nip-kanan="$header?->guru?->nip"
        />
    </div>

    {{-- ================= Deskripsi capaian (bahan pengisian rapor) ================= --}}
    <x-panel judul="Usulan Deskripsi Capaian Kompetensi"
             deskripsi="Dirakit otomatis dari predikat dan bab formatif tertinggi/terendah tiap siswa — siap disalin ke kolom deskripsi rapor."
             ikon="fa-comment-dots" rapat class="print-section" id="print-deskripsi-capaian">
        <div class="px-5 pt-4 no-print">
            <button type="button" onclick="cetakBagian('print-deskripsi-capaian')" class="btn-outline">
                <i class="fa-solid fa-print mr-1.5"></i> Cetak Deskripsi
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th class="w-10">No</th>
                        <th class="min-w-[180px]">Nama Siswa</th>
                        <th class="w-16">NA</th>
                        <th>Deskripsi Capaian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($baris as $b)
                        <tr>
                            <td class="text-center text-slate-400">{{ $loop->iteration }}</td>
                            <td class="font-medium text-slate-700">{{ $b['siswa']->nama }}</td>
                            <td class="text-center font-bold tabular-nums">{{ $b['nilai']?->nilaiRapor() ?? '–' }}</td>
                            <td class="text-slate-600">{{ $b['deskripsi'] ?? 'Nilai belum lengkap — deskripsi belum dapat disusun.' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-state">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-panel>
</div>
@endsection
