@extends('layouts.app')
@section('title', 'Analisis Hasil Tes — ' . $mapel->nama_mapel . ' ' . $kelas->nama_kelas)
@section('deskripsi', 'Analisis butir soal hasil tes sumatif lingkup materi. Skor tiap nomor soal diturunkan otomatis dari nilai yang sudah Anda input di Daftar Nilai.')

@section('aksi')
    <a href="{{ route('nilai.form', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}" class="btn-outline">
        <i class="fa-solid fa-table-list mr-1.5"></i> Daftar Nilai
    </a>
    <a href="{{ route('nilai.program', ['kelas' => $kelas->id, 'mapel' => $mapel->id] + (isset($lm) ? ['lm' => $lm] : [])) }}" class="btn-outline">
        <i class="fa-solid fa-user-graduate mr-1.5"></i> Pengayaan &amp; Perbaikan
    </a>
    @if($lembar)
        <button type="button" onclick="cetakBagian('print-analisis')" class="btn-outline">
            <i class="fa-solid fa-print mr-1.5"></i> Cetak / Export PDF
        </button>
    @endif
@endsection

@section('content')

{{-- ================= Belum ada nilai sumatif sama sekali ================= --}}
@if($lingkupTerisi->isEmpty())
    <div class="card p-5">
        <div class="empty-state">
            <i class="fa-solid fa-clipboard-question text-3xl text-slate-300 mb-3 block"></i>
            <p class="font-semibold text-slate-600 mb-1">Belum ada nilai Sumatif Lingkup Materi</p>
            <p>
                Lembar analisis muncul otomatis begitu Anda mengisi kolom <b>SUM</b> pada
                <a href="{{ route('nilai.form', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}"
                   class="text-brand-600 underline font-semibold">Daftar Nilai</a>
                {{ $mapel->nama_mapel }} kelas {{ $kelas->nama_kelas }}.<br>
                Kalau Anda mengisi sampai Sumatif ke-3, lembar analisisnya juga muncul sampai ke-3.
            </p>
        </div>
    </div>
@else

@php
    $bisaSimpan = $bolehIsi;
    $jumlahSoal = $analisis->jumlah_soal;
@endphp

<div class="space-y-6">

    {{-- ================= Pilih lingkup materi + keterangan lembar ================= --}}
    <div class="card p-4 no-print">
        {{-- Tab per Sumatif Lingkup Materi yang sudah ada nilainya --}}
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide mr-1">Sumatif Lingkup Materi ke-</span>
            @foreach($lingkupTerisi as $n)
                <a href="{{ route('nilai.analisis', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'lm' => $n]) }}"
                   class="btn-chip {{ $n === $lm ? 'btn-chip-edit ring-2 ring-brand-300' : 'btn-chip-cancel' }}">
                    {{ $n }}
                </a>
            @endforeach
            <span class="text-xs text-slate-400 ml-2">
                <i class="fa-solid fa-circle-info mr-1"></i>
                Hanya lingkup materi yang sudah ada nilainya yang muncul di sini.
            </span>
        </div>

        <form method="POST" action="{{ route('nilai.analisis.update', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}"
              class="flex flex-wrap items-end gap-3 pt-4 border-t border-slate-100">
            @csrf
            @method('PUT')
            <input type="hidden" name="lingkup_materi" value="{{ $lm }}">

            <div class="flex-1 min-w-[240px]">
                <label class="label">Materi Ajar</label>
                <input type="text" name="materi_ajar" class="input"
                       value="{{ old('materi_ajar', $analisis->materi_ajar) }}"
                       placeholder="Ketik materi yang diujikan pada sumatif ke-{{ $lm }} ini"
                       @disabled(! $bisaSimpan)>
            </div>
            <div class="w-32">
                <label class="label">Banyak Soal</label>
                <input type="number" name="jumlah_soal" class="input" min="1" max="50"
                       value="{{ old('jumlah_soal', $analisis->jumlah_soal) }}" @disabled(! $bisaSimpan)>
            </div>
            <div class="w-44">
                <label class="label">Tanggal Pelaksanaan</label>
                <input type="date" name="tanggal_pelaksanaan" class="input"
                       value="{{ old('tanggal_pelaksanaan', $analisis->tanggal_pelaksanaan?->toDateString()) }}"
                       @disabled(! $bisaSimpan)>
            </div>
            @if($bisaSimpan)
                <button class="btn-primary"><i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Keterangan</button>
            @endif
        </form>

        @unless($bolehIsi)
            <p class="alert alert-warning mt-3 mb-0">
                <i class="fa-solid fa-eye mt-0.5"></i>
                <span>Anda membuka lembar ini sebagai pemantau — dapat dibaca dan dicetak, tetapi tidak dapat diubah.</span>
            </p>
        @endunless

        @if($ringkasan['belum_dinilai'] > 0)
            <p class="alert alert-info mt-3 mb-0">
                <i class="fa-solid fa-circle-info mt-0.5"></i>
                <span>
                    <b>{{ $ringkasan['belum_dinilai'] }} peserta didik</b> belum memiliki nilai SUM {{ $lm }} di Daftar Nilai,
                    sehingga baris skornya masih kosong. Analisis butir soal dihitung dari {{ $ringkasan['peserta'] }} peserta yang sudah dinilai.
                </span>
            </p>
        @endif
    </div>

    {{-- ================= LEMBAR ANALISIS (yang dicetak) ================= --}}
    <div class="card p-5 print-section" id="print-analisis">
        <x-kop-surat />

        <p class="text-center font-extrabold text-slate-800 text-sm uppercase tracking-wide mb-4">
            Analisis Hasil Tes Sumatif Lingkup Materi Ke-{{ $lm }}
        </p>

        {{-- Kepala lembar, mengikuti tata letak dokumen yang dipakai sekolah --}}
        <div class="grid sm:grid-cols-2 gap-x-8 gap-y-1 text-xs text-slate-600 mb-4">
            <div class="space-y-1">
                <p><span class="inline-block w-32 text-slate-400">Mata Pelajaran</span>: <b class="text-slate-800">{{ $mapel->nama_mapel }}</b></p>
                <p><span class="inline-block w-32 text-slate-400">Materi Ajar</span>: {{ $analisis->materi_ajar ?: '-' }}</p>
                <p><span class="inline-block w-32 text-slate-400">Kelas / Fase</span>: <b class="text-slate-800">{{ $kelas->nama_kelas }} / {{ $kelas->fase() }}</b></p>
                <p><span class="inline-block w-32 text-slate-400">Sekolah</span>: {{ $pengaturanSekolahGlobal->nama_sekolah ?: '-' }}</p>
                <p><span class="inline-block w-32 text-slate-400">KKTP</span>: <b class="text-slate-800">{{ $skema->kktpMin }} &ndash; {{ $skema->kktpMax }}</b></p>
            </div>
            <div class="space-y-1">
                <p><span class="inline-block w-40 text-slate-400">Semester</span>: {{ $periode->nomorSemester() }}</p>
                <p><span class="inline-block w-40 text-slate-400">Sum. Lingkup Materi ke-</span>: <b class="text-slate-800">{{ $lm }}</b></p>
                <p><span class="inline-block w-40 text-slate-400">Banyak Soal</span>: <b class="text-slate-800">{{ $jumlahSoal }}</b></p>
                <p><span class="inline-block w-40 text-slate-400">Banyak Peserta Tes</span>: <b class="text-slate-800">{{ $ringkasan['peserta'] }}</b></p>
                <p><span class="inline-block w-40 text-slate-400">Tahun Pelajaran</span>: {{ $periode->nama }}</p>
                @if($analisis->tanggal_pelaksanaan)
                    <p><span class="inline-block w-40 text-slate-400">Tanggal Pelaksanaan</span>: {{ $analisis->tanggal_pelaksanaan->translatedFormat('d F Y') }}</p>
                @endif
            </div>
        </div>

        {{-- ===== Tabel utama: skor tiap peserta didik per nomor soal ===== --}}
        <div class="overflow-x-auto -mx-5">
            <table class="w-full text-[10px] border-collapse">
                <thead class="text-slate-700">
                    <tr class="bg-slate-100">
                        <th rowspan="2" class="border border-slate-300 px-1 py-2 w-8">No.</th>
                        <th rowspan="2" class="border border-slate-300 px-2 py-2 text-left min-w-[170px] sticky left-0 bg-slate-100 z-10">Nama Peserta Didik</th>
                        <th colspan="{{ $jumlahSoal }}" class="border border-slate-300 px-2 py-1.5 bg-sky-50">Nomor Soal dan Skor yang diperoleh</th>
                        <th rowspan="2" class="border border-slate-300 px-1 py-2 w-12 bg-emerald-50 leading-tight">Jml.<br>Skor</th>
                        <th rowspan="2" class="border border-slate-300 px-1 py-2 w-14 bg-emerald-50 leading-tight">% Keter-<br>capaian</th>
                        <th colspan="2" class="border border-slate-300 px-1 py-1.5 bg-amber-50 leading-tight">Keterc. Belajar</th>
                    </tr>
                    <tr class="bg-slate-50">
                        @for($n = 1; $n <= $jumlahSoal; $n++)
                            <th class="border border-slate-300 px-0.5 py-1 w-7">{{ $n }}</th>
                        @endfor
                        <th class="border border-slate-300 px-1 py-1 w-9">Ya</th>
                        <th class="border border-slate-300 px-1 py-1 w-9">Tidak</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($baris as $b)
                        <tr class="hover:bg-brand-50/40">
                            <td class="border border-slate-300 text-center text-slate-500">{{ $loop->iteration }}</td>
                            <td class="border border-slate-300 px-2 py-1 font-medium whitespace-nowrap sticky left-0 bg-white z-10">
                                {{ $b['siswa']->nama }}
                            </td>

                            @for($n = 1; $n <= $jumlahSoal; $n++)
                                @php $s = $b['skor'][$n] ?? null; @endphp
                                <td class="border border-slate-300 text-center tabular-nums
                                    {{ $s === null ? 'text-slate-300' : ($s <= 0 ? 'text-rose-600 font-semibold' : ($s < 1 ? 'text-amber-600 font-semibold' : '')) }}">
                                    {{-- Skor sebagian ditulis dengan koma desimal (0,6) sesuai kelaziman
                                         dokumen sekolah. Sampai 2 angka di belakang koma, angka nol di
                                         ekor dibuang supaya kolomnya tetap ringkas (1 tetap "1"). --}}
                                    {{ $s === null ? '' : rtrim(rtrim(number_format($s, 2, ',', ''), '0'), ',') }}
                                </td>
                            @endfor

                            <td class="border border-slate-300 text-center font-bold tabular-nums bg-emerald-50/60">
                                {{ $b['jumlah_skor'] === null ? '–' : rtrim(rtrim(number_format($b['jumlah_skor'], 2, ',', ''), '0'), ',') }}
                            </td>
                            <td class="border border-slate-300 text-center tabular-nums bg-emerald-50/60">
                                {{ $b['jumlah_skor'] === null ? '–' : round($b['jumlah_skor']) . '%' }}
                            </td>
                            <td class="border border-slate-300 text-center font-bold text-emerald-700">
                                {{ $b['tuntas'] === true ? 'Ya' : '' }}
                            </td>
                            <td class="border border-slate-300 text-center font-bold text-rose-600">
                                {{ $b['tuntas'] === false ? 'Tidak' : '' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                {{-- ===== Rekap per butir soal: inilah kegunaan utama lembar ini ===== --}}
                <tfoot>
                    <tr class="bg-slate-50 font-semibold text-slate-700">
                        <td colspan="2" class="border border-slate-300 px-2 py-1.5 text-right sticky left-0 bg-slate-50 z-10">Jumlah skor tiap butir</td>
                        @foreach($butir as $s)
                            <td class="border border-slate-300 text-center tabular-nums">{{ rtrim(rtrim(number_format($s['jumlah'], 1, ',', ''), '0'), ',') }}</td>
                        @endforeach
                        <td colspan="4" class="border border-slate-300"></td>
                    </tr>
                    <tr class="bg-sky-50/70 font-semibold text-slate-700">
                        <td colspan="2" class="border border-slate-300 px-2 py-1.5 text-right sticky left-0 bg-sky-50 z-10">% Daya serap butir</td>
                        @foreach($butir as $s)
                            <td class="border border-slate-300 text-center tabular-nums">{{ $s['daya_serap'] === null ? '–' : round($s['daya_serap']) }}</td>
                        @endforeach
                        <td colspan="4" class="border border-slate-300"></td>
                    </tr>
                    <tr class="bg-slate-50 text-slate-700">
                        <td colspan="2" class="border border-slate-300 px-2 py-1.5 text-right sticky left-0 bg-slate-50 z-10">Tingkat kesukaran</td>
                        @foreach($butir as $s)
                            {{-- Disingkat 1 huruf agar muat: M = Mudah, S = Sedang, K = Sukar --}}
                            <td class="border border-slate-300 text-center font-bold
                                {{ $s['label'] === 'Sukar' ? 'text-rose-600' : ($s['label'] === 'Sedang' ? 'text-amber-600' : 'text-emerald-700') }}"
                                title="Soal {{ $s['nomor'] }}: {{ $s['label'] }}">
                                {{ ['Mudah' => 'M', 'Sedang' => 'S', 'Sukar' => 'K'][$s['label']] ?? '-' }}
                            </td>
                        @endforeach
                        <td colspan="4" class="border border-slate-300"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- ===== Keterangan & simpulan ===== --}}
        <div class="mt-4 grid md:grid-cols-2 gap-4 text-[11px] text-slate-600">
            <div class="space-y-1">
                <p class="font-bold text-slate-700 uppercase tracking-wide text-[10px]">Hasil Tes</p>
                <p>Banyak peserta tes : <b>{{ $ringkasan['peserta'] }}</b> peserta didik</p>
                <p>Rata-rata kelas : <b>{{ $ringkasan['rata'] !== null ? number_format($ringkasan['rata'], 2, ',', '') : '–' }}</b>
                    (tertinggi {{ $ringkasan['tertinggi'] ?? '–' }}, terendah {{ $ringkasan['terendah'] ?? '–' }})</p>
                <p>Daya serap kelas : <b>{{ $ringkasan['daya_serap_kelas'] !== null ? round($ringkasan['daya_serap_kelas']) . '%' : '–' }}</b></p>
                <p>Tuntas : <b class="text-emerald-700">{{ $ringkasan['tuntas'] }}</b> &middot;
                   Belum tuntas : <b class="text-rose-600">{{ $ringkasan['belum_tuntas'] }}</b>
                   (KKTP {{ $skema->kktpMin }})</p>
            </div>
            <div class="space-y-1">
                <p class="font-bold text-slate-700 uppercase tracking-wide text-[10px]">Tindak Lanjut</p>
                <p>
                    Butir soal <b>sukar</b> (daya serap &lt; 30%) :
                    <b class="text-rose-600">{{ $ringkasan['soal_sukar']->isEmpty() ? 'tidak ada' : 'nomor ' . $ringkasan['soal_sukar']->implode(', ') }}</b>
                    @if($ringkasan['soal_sukar']->isNotEmpty()) &mdash; perlu dibahas ulang di kelas. @endif
                </p>
                <p>
                    Butir soal <b>sedang</b> (30&ndash;69%) :
                    {{ $ringkasan['soal_sedang']->isEmpty() ? 'tidak ada' : 'nomor ' . $ringkasan['soal_sedang']->implode(', ') }}
                </p>
                <p>
                    Peserta didik yang <b>perlu remedial</b> ({{ $ringkasan['perlu_remedial']->count() }} anak) :
                    <b class="text-rose-600">{{ $ringkasan['perlu_remedial']->isEmpty() ? 'tidak ada' : $ringkasan['perlu_remedial']->map(fn ($b) => $b['siswa']->nama)->implode('; ') }}</b>
                </p>
            </div>
        </div>

        <p class="mt-3 text-[10px] text-slate-500">
            Keterangan tingkat kesukaran : <b>M</b> = Mudah (daya serap &ge; 70%),
            <b>S</b> = Sedang (30&ndash;69%), <b>K</b> = Sukar (&lt; 30%).
            Skor tiap butir soal maksimal 1; skor sebagian ditulis desimal (mis. 0,6).
            Jumlah skor dinyatakan dalam skala 0&ndash;100 dan selalu sama dengan nilai
            Sumatif Lingkup Materi ke-{{ $lm }} pada Daftar Nilai.
        </p>

        <x-blok-tanda-tangan-dua
            jabatan-kanan="Guru Mata Pelajaran {{ $mapel->nama_mapel }}"
            :nama-kanan="$guruPengampu->name ?? null"
            :nip-kanan="$guruPengampu->nip ?? null"
        />
    </div>

    {{-- ================= Daftar remedial (layar saja) ================= --}}
    @if($ringkasan['perlu_remedial']->isNotEmpty())
        <x-panel judul="Peserta Didik yang Perlu Remedial"
                 deskripsi="Nilai sumatif di bawah KKTP {{ $skema->kktpMin }}. Isi hasil remedinya di kolom REM pada Daftar Nilai."
                 ikon="fa-user-clock" rapat class="no-print">
            <div class="overflow-x-auto">
                <table class="table-clean">
                    <thead>
                        <tr>
                            <th class="w-10">No</th>
                            <th>Nama Peserta Didik</th>
                            <th class="w-24">Nilai SUM {{ $lm }}</th>
                            <th class="w-24">Nilai REM</th>
                            <th class="w-32">Status Remedi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ringkasan['perlu_remedial'] as $b)
                            <tr>
                                <td class="text-center text-slate-400">{{ $loop->iteration }}</td>
                                <td class="font-medium text-slate-700">{{ $b['siswa']->nama }}</td>
                                <td class="text-center font-bold text-rose-600 tabular-nums">{{ round($b['jumlah_skor']) }}</td>
                                <td class="text-center tabular-nums">{{ $b['nilai_remedi'] !== null ? round($b['nilai_remedi']) : '–' }}</td>
                                <td>
                                    @if($b['nilai_remedi'] !== null)
                                        <span class="badge bg-emerald-50 text-emerald-700">Sudah remedi</span>
                                    @else
                                        <span class="badge bg-amber-50 text-amber-700">Belum remedi</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-panel>
    @endif
</div>

@endif
@endsection
