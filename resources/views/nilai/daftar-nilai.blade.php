@extends('layouts.app')
@section('title', 'Daftar Nilai — ' . $mapel->nama_mapel . ' ' . $kelas->nama_kelas)
@section('deskripsi', 'Isi nilai formatif, sumatif lingkup materi, ASTS, dan ' . $skema->labelSumatifAkhir() . '. Nilai akhir dihitung otomatis dan langsung masuk ke laporan wali kelas.')

@section('aksi')
    <a href="{{ route('nilai.pilih') }}" class="btn-outline"><i class="fa-solid fa-arrow-left mr-1.5"></i> Daftar Lembar</a>
    <a href="{{ route('nilai.analisis', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}" class="btn-outline">
        <i class="fa-solid fa-magnifying-glass-chart mr-1.5"></i> Analisis Hasil Tes
    </a>
    <a href="{{ route('nilai.program', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}" class="btn-outline">
        <i class="fa-solid fa-user-graduate mr-1.5"></i> Pengayaan &amp; Perbaikan
    </a>
    <button type="button" onclick="cetakBagian('print-daftar-nilai')" class="btn-outline">
        <i class="fa-solid fa-print mr-1.5"></i> Cetak / Export PDF
    </button>
@endsection

@section('content')
@php
    $terkunci = $header->isFinal();
    $bisaSimpan = $bolehIsi && ! $terkunci;
    $labelAkhir = $skema->labelSumatifAkhir();

    // Skema yang sama dipakai oleh pratinjau di layar (lihat barisNilai()
    // di resources/js/app.js). Server tetap yang berwenang menghitung —
    // ini hanya supaya angka berubah langsung saat guru mengetik.
    $skemaJs = [
        'kktpMin' => $skema->kktpMin,
        'kktpMax' => $skema->kktpMax,
        'jumlahTpf' => $skema->jumlahTpf,
        'jumlahLm' => $skema->jumlahLm,
        'bobot60' => $skema->bobotFormatifSumatif,
        'bobotAsts' => $skema->bobotAsts,
        'bobotAsas' => $skema->bobotAsas,
        'komposisiFormatif' => $skema->komposisiFormatif,
        'komposisiSumatifLm' => $skema->komposisiSumatifLm,
        'kebijakan' => $skema->kebijakanRemedial,
    ];

    $kolomInput = 'w-full text-center text-[11px] tabular-nums px-0.5 py-1.5 rounded border border-transparent '
        .'bg-transparent hover:bg-slate-50 focus:bg-white focus:border-brand-400 focus:outline-none '
        .'focus:ring-2 focus:ring-brand-100 print:bg-transparent';
@endphp

<script>window.skemaNilai = @json($skemaJs);</script>

<div class="space-y-6">

    {{-- ================= Status & tombol aksi ================= --}}
    <div class="card p-4 no-print">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3 flex-wrap text-sm">
                <span class="badge {{ $header->statusBadgeClass() }}">
                    <i class="fa-solid {{ $terkunci ? 'fa-lock' : 'fa-pen' }} mr-1"></i> {{ $header->statusLabel() }}
                </span>
                <span class="text-slate-500">
                    <i class="fa-solid fa-user-check w-4 text-slate-400"></i>
                    Sudah dinilai <b class="text-slate-700">{{ $statistik['dinilai'] }}</b> dari {{ $statistik['total'] }} siswa
                </span>
                @if($statistik['rata'] !== null)
                    <span class="text-slate-500">
                        <i class="fa-solid fa-chart-simple w-4 text-slate-400"></i>
                        Rata-rata kelas <b class="text-slate-700">{{ $statistik['rata'] }}</b>
                        (tertinggi {{ $statistik['tertinggi'] }}, terendah {{ $statistik['terendah'] }})
                    </span>
                    @if($statistik['belum_tuntas'] > 0)
                        <span class="badge bg-rose-50 text-rose-700">
                            {{ $statistik['belum_tuntas'] }} siswa belum tuntas
                        </span>
                    @endif
                @endif
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                @if($bisaSimpan)
                    <button type="submit" form="form-daftar-nilai" class="btn-primary">
                        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Nilai
                    </button>
                    <form method="POST" action="{{ route('nilai.finalisasi', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}"
                          data-konfirmasi="Finalisasi daftar nilai ini? Setelah difinalisasi nilai terkunci dan hanya Kurikulum/Admin yang dapat membukanya kembali.">
                        @csrf
                        <button class="btn-outline"><i class="fa-solid fa-lock mr-1.5"></i> Finalisasi</button>
                    </form>
                @endif

                @if($terkunci && (auth()->user()->isKurikulum() || auth()->user()->isAdmin()))
                    <form method="POST" action="{{ route('nilai.buka-kunci', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}"
                          data-konfirmasi="Buka kunci daftar nilai ini supaya guru mata pelajaran dapat mengoreksinya kembali?">
                        @csrf
                        <button class="btn-outline"><i class="fa-solid fa-lock-open mr-1.5"></i> Buka Kunci</button>
                    </form>
                @endif
            </div>
        </div>

        @if($terkunci)
            <p class="alert alert-info mt-3 mb-0">
                <i class="fa-solid fa-circle-info mt-0.5"></i>
                <span>
                    Daftar nilai ini sudah difinalisasi
                    @if($header->difinalisasi_at) pada {{ $header->difinalisasi_at->translatedFormat('d F Y, H:i') }} @endif
                    @if($header->difinalisasiOleh) oleh {{ $header->difinalisasiOleh->name }} @endif
                    sehingga tidak dapat diubah. Bila ada koreksi, minta Kurikulum/Admin membuka kuncinya.
                </span>
            </p>
        @elseif(! $bolehIsi)
            <p class="alert alert-warning mt-3 mb-0">
                <i class="fa-solid fa-eye mt-0.5"></i>
                <span>Anda membuka lembar ini sebagai pemantau — nilai dapat dibaca dan dicetak, tetapi tidak dapat diubah.</span>
            </p>
        @endif
    </div>

    {{-- ================= Lembar daftar nilai ================= --}}
    <form method="POST" id="form-daftar-nilai"
          action="{{ route('nilai.store', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}">
        @csrf

        <div class="card p-5 print-section" id="print-daftar-nilai">
            <x-kop-surat />

            {{-- Kepala lembar, mengikuti format daftar nilai yang dipakai sekolah --}}
            <div class="text-center mb-4">
                <p class="font-extrabold tracking-[0.2em] text-slate-800 text-sm uppercase">Daftar Nilai</p>
                <p class="font-extrabold text-lg text-slate-800 uppercase">{{ $mapel->nama_mapel }}</p>
            </div>

            <div class="grid sm:grid-cols-2 gap-x-8 gap-y-1 text-xs text-slate-600 mb-4">
                <p><span class="inline-block w-28 text-slate-400">Kelas / Fase</span>: <b class="text-slate-800">{{ $kelas->nama_kelas }} / {{ $kelas->fase() }}</b></p>
                <p><span class="inline-block w-28 text-slate-400">Wali Kelas</span>: {{ $kelas->waliKelas->name ?? '-' }}</p>
                <p><span class="inline-block w-28 text-slate-400">Semester</span>: {{ $periode->semester }}</p>
                <p><span class="inline-block w-28 text-slate-400">Tahun Pelajaran</span>: {{ $periode->nama }}</p>
                <p><span class="inline-block w-28 text-slate-400">KKTP</span>: <b class="text-slate-800">{{ $skema->labelKktp() }}</b></p>
                <p><span class="inline-block w-28 text-slate-400">Guru Mata Pelajaran</span>: {{ $guruPengampu->name ?? '-' }}</p>
            </div>

            <div class="overflow-x-auto -mx-5">
                <table class="w-full text-[11px] border-collapse">
                    <thead class="text-slate-700">
                        {{-- Baris 1: kelompok besar --}}
                        <tr class="bg-slate-100">
                            <th rowspan="3" class="border border-slate-300 px-1 py-2 w-8">NO.</th>
                            <th rowspan="3" class="border border-slate-300 px-2 py-2 min-w-[86px]">NISN</th>
                            <th rowspan="3" class="border border-slate-300 px-2 py-2 min-w-[52px]">NIS</th>
                            <th rowspan="3" class="border border-slate-300 px-2 py-2 text-left min-w-[170px] sticky left-0 bg-slate-100 z-10">N A M A</th>
                            <th colspan="{{ $skema->jumlahTpf + 1 }}" class="border border-slate-300 px-2 py-1.5 bg-sky-50 tracking-widest">F O R M A T I F</th>
                            <th colspan="{{ $skema->jumlahLm * 2 + 2 }}" class="border border-slate-300 px-2 py-1.5 bg-violet-50 tracking-widest">SUMATIF LINGKUP MATERI</th>
                            <th colspan="2" class="border border-slate-300 px-2 py-1.5 bg-amber-50 tracking-widest">S U M A T I F</th>
                            <th rowspan="3" class="border border-slate-300 px-1 py-2 bg-emerald-50 w-14 leading-tight">NILAI<br>AKHIR<br>(RAPOR)</th>
                            <th rowspan="3" class="border border-slate-300 px-1 py-2 bg-emerald-50 w-12 leading-tight">PRE-<br>DIKAT</th>
                        </tr>

                        {{-- Baris 2: kolom --}}
                        <tr class="bg-slate-50">
                            @for($n = 1; $n <= $skema->jumlahTpf; $n++)
                                <th rowspan="2" class="border border-slate-300 px-1 py-1 w-10 leading-tight" title="Tujuan Pembelajaran Formatif ke-{{ $n }} (BAB {{ $n }})">TPF<br>{{ $n }}</th>
                            @endfor
                            <th rowspan="2" class="border border-slate-300 px-1 py-1 w-11 bg-sky-50" title="Rata-rata nilai formatif yang sudah terisi">RT</th>

                            @for($n = 1; $n <= $skema->jumlahLm; $n++)
                                <th colspan="2" class="border border-slate-300 px-1 py-1">LM {{ $n }}</th>
                            @endfor
                            <th rowspan="2" class="border border-slate-300 px-1 py-1 w-11 bg-violet-50" title="Rata-rata nilai lingkup materi (remedi sudah diperhitungkan)">RT</th>
                            <th rowspan="2" class="border border-slate-300 px-1 py-1 w-12 bg-violet-100 leading-tight" title="Gabungan Formatif & Sumatif Lingkup Materi, berbobot {{ $skema->bobotFormatifSumatif }}%">
                                %BOBOT<br>{{ $skema->bobotFormatifSumatif }}
                            </th>

                            <th rowspan="2" class="border border-slate-300 px-1 py-1 w-12 bg-amber-50 leading-tight" title="Asesmen Sumatif Tengah Semester — bobot {{ $skema->bobotAsts }}%">
                                ASTS<br><span class="font-normal text-[9px] text-slate-500">{{ $skema->bobotAsts }}%</span>
                            </th>
                            <th rowspan="2" class="border border-slate-300 px-1 py-1 w-12 bg-amber-50 leading-tight" title="{{ $skema->labelPanjangSumatifAkhir() }} — bobot {{ $skema->bobotAsas }}%">
                                {{ $labelAkhir }}<br><span class="font-normal text-[9px] text-slate-500">{{ $skema->bobotAsas }}%</span>
                            </th>
                        </tr>

                        {{-- Baris 3: pasangan SUM / REM tiap lingkup materi --}}
                        <tr class="bg-slate-50">
                            @for($n = 1; $n <= $skema->jumlahLm; $n++)
                                <th class="border border-slate-300 px-1 py-1 w-10" title="Sumatif Lingkup Materi ke-{{ $n }}">SUM</th>
                                <th class="border border-slate-300 px-1 py-1 w-10 text-rose-600" title="Remedi — hanya diisi bila SUM {{ $n }} kurang dari KKTP {{ $skema->kktpMin }}">REM</th>
                            @endfor
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($baris as $b)
                            @php
                                $siswa = $b['siswa'];
                                $n = $b['nilai'];
                                $dataAwal = [
                                    'formatif' => collect(range(1, $skema->jumlahTpf))
                                        ->mapWithKeys(fn ($i) => [$i => $n->tpf($i)])->all(),
                                    'lm' => collect(range(1, $skema->jumlahLm))
                                        ->mapWithKeys(fn ($i) => [$i => ['sum' => $n->lm($i, 'sum'), 'rem' => $n->lm($i, 'rem')]])->all(),
                                    'asts' => $n->asts,
                                    'asas' => $n->asas,
                                ];
                            @endphp

                            <tr x-data="barisNilai({{ Js::from($dataAwal) }}, window.skemaNilai)"
                                class="hover:bg-brand-50/40">
                                <td class="border border-slate-300 text-center text-slate-500">{{ $loop->iteration }}</td>
                                <td class="border border-slate-300 px-2 text-center tabular-nums">{{ $siswa->nisn ?: '-' }}</td>
                                <td class="border border-slate-300 px-2 text-center tabular-nums">{{ $siswa->nis }}</td>
                                <td class="border border-slate-300 px-2 py-1 font-medium whitespace-nowrap sticky left-0 bg-white z-10">
                                    {{ $siswa->nama }}
                                </td>

                                {{-- ===== FORMATIF ===== --}}
                                @for($i = 1; $i <= $skema->jumlahTpf; $i++)
                                    <td class="border border-slate-300 p-0">
                                        <input type="number" step="0.01" min="0" max="100" inputmode="decimal"
                                               name="formatif[{{ $siswa->id }}][{{ $i }}]"
                                               x-model="formatif[{{ $i }}]"
                                               @disabled(! $bisaSimpan)
                                               aria-label="TPF {{ $i }} — {{ $siswa->nama }}"
                                               class="{{ $kolomInput }}">
                                    </td>
                                @endfor
                                <td class="border border-slate-300 text-center font-bold bg-sky-50/60 tabular-nums"
                                    x-text="tampil(rataFormatif)"></td>

                                {{-- ===== SUMATIF LINGKUP MATERI ===== --}}
                                @for($i = 1; $i <= $skema->jumlahLm; $i++)
                                    <td class="border border-slate-300 p-0">
                                        <input type="number" step="0.01" min="0" max="100" inputmode="decimal"
                                               name="sumatif[{{ $siswa->id }}][{{ $i }}][sum]"
                                               x-model="lm[{{ $i }}].sum"
                                               @disabled(! $bisaSimpan)
                                               aria-label="Sumatif Lingkup Materi {{ $i }} — {{ $siswa->nama }}"
                                               class="{{ $kolomInput }}">
                                    </td>
                                    <td class="border border-slate-300 p-0"
                                        :class="wajibRemedi({{ $i }}) && ! lm[{{ $i }}].rem ? 'bg-rose-50' : ''">
                                        <input type="number" step="0.01" min="0" max="100" inputmode="decimal"
                                               name="sumatif[{{ $siswa->id }}][{{ $i }}][rem]"
                                               x-model="lm[{{ $i }}].rem"
                                               @disabled(! $bisaSimpan)
                                               :title="wajibRemedi({{ $i }})
                                                    ? 'Wajib diisi — nilai sumatif di bawah KKTP {{ $skema->kktpMin }}'
                                                    : 'Diisi hanya bila nilai SUM kurang dari KKTP {{ $skema->kktpMin }}'"
                                               aria-label="Remedi Lingkup Materi {{ $i }} — {{ $siswa->nama }}"
                                               class="{{ $kolomInput }}"
                                               :class="wajibRemedi({{ $i }}) ? 'text-rose-700 font-semibold' : 'text-slate-400'">
                                    </td>
                                @endfor
                                <td class="border border-slate-300 text-center font-bold bg-violet-50/60 tabular-nums"
                                    x-text="tampil(rataSumatifLm)"></td>
                                <td class="border border-slate-300 text-center font-bold bg-violet-100/60 tabular-nums"
                                    x-text="tampil(nilaiKomponen60)"></td>

                                {{-- ===== SUMATIF TENGAH & AKHIR SEMESTER ===== --}}
                                <td class="border border-slate-300 p-0 bg-amber-50/40">
                                    <input type="number" step="0.01" min="0" max="100" inputmode="decimal"
                                           name="asts[{{ $siswa->id }}]" x-model="asts"
                                           @disabled(! $bisaSimpan)
                                           aria-label="ASTS — {{ $siswa->nama }}"
                                           class="{{ $kolomInput }}">
                                </td>
                                <td class="border border-slate-300 p-0 bg-amber-50/40">
                                    <input type="number" step="0.01" min="0" max="100" inputmode="decimal"
                                           name="asas[{{ $siswa->id }}]" x-model="asas"
                                           @disabled(! $bisaSimpan)
                                           aria-label="{{ $labelAkhir }} — {{ $siswa->nama }}"
                                           class="{{ $kolomInput }}">
                                </td>

                                {{-- ===== NILAI AKHIR ===== --}}
                                <td class="border border-slate-300 text-center font-extrabold text-sm tabular-nums bg-emerald-50/60"
                                    :class="tuntas === false ? 'text-rose-600' : 'text-slate-800'"
                                    x-text="tampil(nilaiRapor)"></td>
                                <td class="border border-slate-300 text-center bg-emerald-50/60">
                                    <span class="badge" :class="warnaPredikat" x-text="predikat || '–'"></span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                {{-- 4 kolom identitas + TPF + RT + (SUM,REM)×LM + RT + %BOBOT + ASTS + akhir + NA + predikat --}}
                                <td colspan="{{ 11 + $skema->jumlahTpf + $skema->jumlahLm * 2 }}" class="empty-state border border-slate-300">
                                    Belum ada siswa aktif di kelas {{ $kelas->nama_kelas }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ================= Keterangan lembar ================= --}}
            <div class="mt-4 text-[11px] text-slate-500 space-y-1.5">
                <p class="flex flex-wrap gap-x-5 gap-y-1">
                    <span><b>TPF n</b> = Penilaian Formatif BAB ke-n</span>
                    <span><b>LM n</b> = Lingkup Materi ke-n</span>
                    <span><b>SUM</b> = Sumatif Lingkup Materi</span>
                    <span><b>REM</b> = Remedi</span>
                    <span><b>RT</b> = Rata-rata</span>
                    <span><b>ASTS</b> = Asesmen Sumatif Tengah Semester</span>
                    <span><b>{{ $labelAkhir }}</b> = {{ $skema->labelPanjangSumatifAkhir() }}</span>
                </p>
                <p>
                    <b>Cara nilai akhir dihitung:</b>
                    ({{ $skema->komposisiFormatif }}% RT Formatif + {{ $skema->komposisiSumatifLm }}% RT Sumatif Lingkup Materi)
                    berbobot <b>{{ $skema->bobotFormatifSumatif }}%</b>,
                    ASTS berbobot <b>{{ $skema->bobotAsts }}%</b>,
                    {{ $labelAkhir }} berbobot <b>{{ $skema->bobotAsas }}%</b>.
                </p>
                <p>
                    <b>Remedi:</b> kolom REM wajib diisi bila nilai SUM kurang dari KKTP ({{ $skema->kktpMin }}).
                    Kebijakan yang berlaku: <b>{{ $skema->labelKebijakanRemedial() }}</b> — nilai satu lingkup materi
                    dihitung sekali dari pasangan SUM &amp; REM, bukan dirata-rata sebagai dua nilai terpisah.
                </p>
                <p class="print:hidden">
                    <b>Predikat:</b>
                    @foreach($skema->rentangPredikat() as $huruf => $r)
                        <span class="inline-block mr-3">{{ $huruf }} = {{ $r['dari'] }}–{{ $r['sampai'] }} ({{ $r['label'] }})</span>
                    @endforeach
                </p>
                <p class="no-print text-amber-600">
                    <i class="fa-solid fa-circle-info mr-1"></i>
                    Kolom RT, %BOBOT, dan NILAI AKHIR terhitung otomatis saat Anda mengetik. Tekan
                    <b>Simpan Nilai</b> agar tersimpan dan masuk ke laporan wali kelas.
                </p>
            </div>

            <x-blok-tanda-tangan-dua
                jabatan-kanan="Guru Mata Pelajaran {{ $mapel->nama_mapel }}"
                :nama-kanan="$guruPengampu->name ?? null"
                :nip-kanan="$guruPengampu->nip ?? null"
            />
        </div>
    </form>

    @if($bisaSimpan)
        <div class="flex justify-end no-print">
            <button type="submit" form="form-daftar-nilai" class="btn-primary">
                <i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Nilai
            </button>
        </div>
    @endif
</div>
@endsection
