@extends('layouts.app')
@section('title', 'Program Pengayaan & Perbaikan — ' . $mapel->nama_mapel . ' ' . $kelas->nama_kelas)
@section('deskripsi', 'Peserta perbaikan dan pengayaan beserta butir soal yang belum dikuasai, otomatis dari hasil Analisis Sumatif Lingkup Materi.')

@section('aksi')
    <a href="{{ route('nilai.form', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}" class="btn-outline">
        <i class="fa-solid fa-table-list mr-1.5"></i> Daftar Nilai
    </a>
    <a href="{{ route('nilai.analisis', ['kelas' => $kelas->id, 'mapel' => $mapel->id] + (isset($lm) ? ['lm' => $lm] : [])) }}" class="btn-outline">
        <i class="fa-solid fa-magnifying-glass-chart mr-1.5"></i> Analisis Hasil Tes
    </a>
    @if($program)
        <button type="button" onclick="cetakBagian('print-program')" class="btn-outline">
            <i class="fa-solid fa-print mr-1.5"></i> Cetak / Export PDF
        </button>
    @endif
@endsection

@section('content')

{{-- ================= Belum ada nilai sumatif sama sekali ================= --}}
@if($lingkupTerisi->isEmpty())
    <div class="card p-5">
        <div class="empty-state">
            <i class="fa-solid fa-user-graduate text-3xl text-slate-300 mb-3 block"></i>
            <p class="font-semibold text-slate-600 mb-1">Belum ada nilai Sumatif Lingkup Materi</p>
            <p>
                Program pengayaan dan perbaikan disusun dari hasil tes sumatif. Isi dulu kolom <b>SUM</b> pada
                <a href="{{ route('nilai.form', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}"
                   class="text-brand-600 underline font-semibold">Daftar Nilai</a>
                {{ $mapel->nama_mapel }} kelas {{ $kelas->nama_kelas }}.
            </p>
        </div>
    </div>
@else

@php
    $bisaSimpan = $bolehIsi;
    // Saran redaksi yang lazim dipakai di dokumen sekolah. Hanya muncul
    // sebagai placeholder / tombol contoh — tidak pernah tersimpan sendiri,
    // supaya yang tercetak benar-benar tulisan guru yang bersangkutan.
    $contohPerbaikan = 'Pembelajaran ulang secara klasikal pada materi yang belum dikuasai, dilanjutkan bimbingan individual dan tes perbaikan tertulis.';
    $contohPengayaan = 'Pemberian tugas pengembangan materi berupa soal-soal bertaraf lebih tinggi (HOTS) dan menjadi tutor sebaya bagi peserta perbaikan.';
@endphp

<div class="space-y-6">

    {{-- ================= Pilih lingkup materi + rencana pelaksanaan ================= --}}
    <div class="card p-4 no-print">
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide mr-1">Sumatif Lingkup Materi ke-</span>
            @foreach($lingkupTerisi as $n)
                <a href="{{ route('nilai.program', ['kelas' => $kelas->id, 'mapel' => $mapel->id, 'lm' => $n]) }}"
                   class="btn-chip {{ $n === $lm ? 'btn-chip-edit ring-2 ring-brand-300' : 'btn-chip-cancel' }}">
                    {{ $n }}
                </a>
            @endforeach
        </div>

        <form method="POST" action="{{ route('nilai.program.update', ['kelas' => $kelas->id, 'mapel' => $mapel->id]) }}"
              class="pt-4 border-t border-slate-100"
              x-data="{
                  perbaikan: @js(old('bentuk_perbaikan', $program->bentuk_perbaikan)),
                  pengayaan: @js(old('bentuk_pengayaan', $program->bentuk_pengayaan)),
              }">
            @csrf
            @method('PUT')
            <input type="hidden" name="lingkup_materi" value="{{ $lm }}">

            <div class="grid md:grid-cols-2 gap-5">
                {{-- Rencana perbaikan --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <label class="label mb-0">Bentuk Pelaksanaan Perbaikan</label>
                        @if($bisaSimpan)
                            <button type="button" @click="perbaikan = @js($contohPerbaikan)"
                                    class="text-[11px] font-semibold text-brand-600 hover:underline">
                                <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Isi contoh
                            </button>
                        @endif
                    </div>
                    <textarea name="bentuk_perbaikan" rows="3" class="input" x-model="perbaikan"
                              placeholder="{{ $contohPerbaikan }}" @disabled(! $bisaSimpan)></textarea>
                    <div>
                        <label class="label">Tanggal Pelaksanaan Perbaikan</label>
                        <input type="date" name="tanggal_perbaikan" class="input"
                               value="{{ old('tanggal_perbaikan', $program->tanggal_perbaikan?->toDateString()) }}"
                               @disabled(! $bisaSimpan)>
                    </div>
                </div>

                {{-- Rencana pengayaan --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <label class="label mb-0">Bentuk Pelaksanaan Pengayaan</label>
                        @if($bisaSimpan)
                            <button type="button" @click="pengayaan = @js($contohPengayaan)"
                                    class="text-[11px] font-semibold text-brand-600 hover:underline">
                                <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Isi contoh
                            </button>
                        @endif
                    </div>
                    <textarea name="bentuk_pengayaan" rows="3" class="input" x-model="pengayaan"
                              placeholder="{{ $contohPengayaan }}" @disabled(! $bisaSimpan)></textarea>
                    <div>
                        <label class="label">Tanggal Pelaksanaan Pengayaan</label>
                        <input type="date" name="tanggal_pengayaan" class="input"
                               value="{{ old('tanggal_pengayaan', $program->tanggal_pengayaan?->toDateString()) }}"
                               @disabled(! $bisaSimpan)>
                    </div>
                </div>
            </div>

            @if($bisaSimpan)
                <div class="flex justify-end mt-4">
                    <button class="btn-primary"><i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Rencana</button>
                </div>
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
                    <b>{{ $ringkasan['belum_dinilai'] }} peserta didik</b> belum memiliki nilai SUM {{ $lm }},
                    sehingga belum dapat ditentukan masuk program perbaikan atau pengayaan.
                </span>
            </p>
        @endif
    </div>

    {{-- ================= Ringkasan ================= --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 no-print">
        <div class="card p-4">
            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Peserta Perbaikan</p>
            <p class="text-2xl font-extrabold text-rose-600 mt-1">{{ $ringkasan['jumlah_perbaikan'] }}</p>
            <p class="text-[11px] text-slate-400 mt-1">nilai di bawah KKTP {{ $skema->kktpMin }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Peserta Pengayaan</p>
            <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $ringkasan['jumlah_pengayaan'] }}</p>
            <p class="text-[11px] text-slate-400 mt-1">sudah mencapai KKTP</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Sudah Remedi</p>
            <p class="text-2xl font-extrabold text-sky-600 mt-1">{{ $ringkasan['sudah_remedi'] }}</p>
            <p class="text-[11px] text-slate-400 mt-1">dari {{ $ringkasan['jumlah_perbaikan'] }} peserta perbaikan</p>
        </div>
        <div class="card p-4">
            <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Tuntas Setelah Perbaikan</p>
            <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $ringkasan['tuntas_setelah'] }}</p>
            <p class="text-[11px] text-slate-400 mt-1">nilai akhir LM sudah ≥ KKTP</p>
        </div>
    </div>

    {{-- ================= DOKUMEN (yang dicetak) ================= --}}
    <div class="card p-5 print-section" id="print-program">
        <x-kop-surat />

        <p class="text-center font-extrabold text-slate-800 text-sm uppercase tracking-wide mb-1">
            Program Pengayaan dan Perbaikan
        </p>
        <p class="text-center text-xs text-slate-500 mb-4">
            Tindak Lanjut Analisis Hasil Tes Sumatif Lingkup Materi Ke-{{ $lm }}
        </p>

        <div class="grid sm:grid-cols-2 gap-x-8 gap-y-1 text-xs text-slate-600 mb-4">
            <div class="space-y-1">
                <p><span class="inline-block w-32 text-slate-400">Mata Pelajaran</span>: <b class="text-slate-800">{{ $mapel->nama_mapel }}</b></p>
                <p><span class="inline-block w-32 text-slate-400">Materi Ajar</span>: {{ $program->materi_ajar ?: '-' }}</p>
                <p><span class="inline-block w-32 text-slate-400">Kelas / Fase</span>: <b class="text-slate-800">{{ $kelas->nama_kelas }} / {{ $kelas->fase() }}</b></p>
                <p><span class="inline-block w-32 text-slate-400">Sekolah</span>: {{ $pengaturanSekolahGlobal->nama_sekolah ?: '-' }}</p>
            </div>
            <div class="space-y-1">
                <p><span class="inline-block w-40 text-slate-400">Semester</span>: {{ $periode->nomorSemester() }}</p>
                <p><span class="inline-block w-40 text-slate-400">Sum. Lingkup Materi ke-</span>: <b class="text-slate-800">{{ $lm }}</b></p>
                <p><span class="inline-block w-40 text-slate-400">KKTP</span>: <b class="text-slate-800">{{ $skema->kktpMin }} &ndash; {{ $skema->kktpMax }}</b></p>
                <p><span class="inline-block w-40 text-slate-400">Tahun Pelajaran</span>: {{ $periode->nama }}</p>
                <p><span class="inline-block w-40 text-slate-400">Banyak Peserta Tes</span>: {{ $ringkasan['peserta_dinilai'] }}</p>
            </div>
        </div>

        {{-- ============ A. PROGRAM PERBAIKAN ============ --}}
        <div class="cetak-utuh mb-5">
            <p class="font-extrabold text-slate-800 text-xs uppercase tracking-wide mb-2 pb-1 border-b border-slate-300">
                A. Program Perbaikan (Remedial)
            </p>

            <div class="text-xs text-slate-600 space-y-1 mb-3">
                <p class="flex gap-2">
                    <span class="w-36 shrink-0 text-slate-400">Sasaran</span>
                    <span>: peserta didik dengan nilai kurang dari KKTP ({{ $skema->kktpMin }}) &mdash;
                        <b>{{ $ringkasan['jumlah_perbaikan'] }} anak</b></span>
                </p>
                <p class="flex gap-2">
                    <span class="w-36 shrink-0 text-slate-400">Bentuk pelaksanaan</span>
                    <span>: {{ $program->bentuk_perbaikan ?: '-' }}</span>
                </p>
                <p class="flex gap-2">
                    <span class="w-36 shrink-0 text-slate-400">Tanggal pelaksanaan</span>
                    <span>: {{ $program->tanggal_perbaikan?->translatedFormat('d F Y') ?: '-' }}</span>
                </p>
                @if($ringkasan['butir_lemah']->isNotEmpty())
                    <p class="flex gap-2">
                        <span class="w-36 shrink-0 text-slate-400">Materi yang diulang</span>
                        <span>: butir soal nomor
                            <b>{{ $ringkasan['butir_lemah']->pluck('nomor')->implode(', ') }}</b>
                            (daya serap kelas masih di bawah 70%)</span>
                    </p>
                @endif
            </div>

            @if($perbaikan->isEmpty())
                <p class="text-xs text-slate-500 italic border border-slate-300 px-3 py-3 text-center">
                    Tidak ada peserta didik yang memerlukan perbaikan &mdash; seluruh peserta tes sudah mencapai KKTP.
                </p>
            @else
                <div class="overflow-x-auto -mx-5">
                    <table class="w-full text-[11px] border-collapse">
                        <thead>
                            <tr class="bg-slate-100 text-slate-700">
                                <th class="border border-slate-300 px-1 py-2 w-8">No.</th>
                                <th class="border border-slate-300 px-2 py-2 text-left min-w-[170px]">Nama Peserta Didik</th>
                                <th class="border border-slate-300 px-1 py-2 w-16 leading-tight">Nilai<br>Sebelum</th>
                                <th class="border border-slate-300 px-2 py-2 min-w-[180px] leading-tight">Butir Soal yang Belum Dikuasai</th>
                                <th class="border border-slate-300 px-1 py-2 w-16 leading-tight">Nilai<br>Setelah</th>
                                <th class="border border-slate-300 px-1 py-2 w-16 leading-tight">Nilai<br>Akhir LM</th>
                                <th class="border border-slate-300 px-1 py-2 w-20">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($perbaikan as $p)
                                <tr class="hover:bg-brand-50/40">
                                    <td class="border border-slate-300 text-center text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="border border-slate-300 px-2 py-1 font-medium whitespace-nowrap">{{ $p['siswa']->nama }}</td>
                                    <td class="border border-slate-300 text-center font-bold text-rose-600 tabular-nums">
                                        {{ rtrim(rtrim(number_format($p['nilai'], 2, ',', ''), '0'), ',') }}
                                    </td>
                                    <td class="border border-slate-300 px-2 py-1 text-slate-600">
                                        {{ $p['butir']->isEmpty() ? '-' : 'Nomor ' . $p['butir']->implode(', ') }}
                                    </td>
                                    <td class="border border-slate-300 text-center tabular-nums {{ $p['nilai_remedi'] === null ? 'text-slate-300' : 'font-semibold' }}">
                                        {{ $p['nilai_remedi'] === null ? '–' : rtrim(rtrim(number_format($p['nilai_remedi'], 2, ',', ''), '0'), ',') }}
                                    </td>
                                    <td class="border border-slate-300 text-center font-bold tabular-nums
                                        {{ $p['tuntas'] === true ? 'text-emerald-700' : ($p['tuntas'] === false ? 'text-rose-600' : 'text-slate-300') }}">
                                        {{ $p['nilai_akhir_lm'] === null ? '–' : rtrim(rtrim(number_format($p['nilai_akhir_lm'], 2, ',', ''), '0'), ',') }}
                                    </td>
                                    <td class="border border-slate-300 text-center">
                                        @if($p['tuntas'] === true)
                                            <span class="badge bg-emerald-50 text-emerald-700">Tuntas</span>
                                        @elseif($p['tuntas'] === false)
                                            <span class="badge bg-rose-50 text-rose-700">Belum Tuntas</span>
                                        @else
                                            <span class="badge bg-amber-50 text-amber-700">Belum Remedi</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-[10px] text-slate-500 mt-2">
                    <b>Nilai Setelah</b> diambil dari kolom REM pada Daftar Nilai.
                    <b>Nilai Akhir LM</b> adalah nilai yang benar-benar dipakai menghitung rapor, mengikuti kebijakan
                    <b>{{ $skema->labelKebijakanRemedial() }}</b>.
                </p>
            @endif
        </div>

        {{-- ============ B. PROGRAM PENGAYAAN ============ --}}
        <div class="cetak-utuh">
            <p class="font-extrabold text-slate-800 text-xs uppercase tracking-wide mb-2 pb-1 border-b border-slate-300">
                B. Program Pengayaan
            </p>

            <div class="text-xs text-slate-600 space-y-1 mb-3">
                <p class="flex gap-2">
                    <span class="w-36 shrink-0 text-slate-400">Sasaran</span>
                    <span>: peserta didik yang sudah mencapai KKTP ({{ $skema->kktpMin }}) &mdash;
                        <b>{{ $ringkasan['jumlah_pengayaan'] }} anak</b></span>
                </p>
                <p class="flex gap-2">
                    <span class="w-36 shrink-0 text-slate-400">Bentuk pelaksanaan</span>
                    <span>: {{ $program->bentuk_pengayaan ?: '-' }}</span>
                </p>
                <p class="flex gap-2">
                    <span class="w-36 shrink-0 text-slate-400">Tanggal pelaksanaan</span>
                    <span>: {{ $program->tanggal_pengayaan?->translatedFormat('d F Y') ?: '-' }}</span>
                </p>
            </div>

            @if($pengayaan->isEmpty())
                <p class="text-xs text-slate-500 italic border border-slate-300 px-3 py-3 text-center">
                    Belum ada peserta didik yang mencapai KKTP pada lingkup materi ini.
                </p>
            @else
                <div class="overflow-x-auto -mx-5">
                    <table class="w-full text-[11px] border-collapse">
                        <thead>
                            <tr class="bg-slate-100 text-slate-700">
                                <th class="border border-slate-300 px-1 py-2 w-8">No.</th>
                                <th class="border border-slate-300 px-2 py-2 text-left min-w-[170px]">Nama Peserta Didik</th>
                                <th class="border border-slate-300 px-1 py-2 w-16">Nilai</th>
                                <th class="border border-slate-300 px-2 py-2 min-w-[220px]">Bentuk Pengayaan</th>
                                <th class="border border-slate-300 px-1 py-2 w-24">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pengayaan as $p)
                                <tr class="hover:bg-brand-50/40">
                                    <td class="border border-slate-300 text-center text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="border border-slate-300 px-2 py-1 font-medium whitespace-nowrap">{{ $p['siswa']->nama }}</td>
                                    <td class="border border-slate-300 text-center font-bold tabular-nums text-emerald-700">
                                        {{ rtrim(rtrim(number_format($p['nilai'], 2, ',', ''), '0'), ',') }}
                                    </td>
                                    <td class="border border-slate-300 px-2 py-1 text-slate-600">{{ $program->bentuk_pengayaan ?: '-' }}</td>
                                    <td class="border border-slate-300 text-center">
                                        @if($p['istimewa'])
                                            <span class="badge bg-emerald-50 text-emerald-700">Di atas KKTP</span>
                                        @else
                                            <span class="badge bg-sky-50 text-sky-700">Tuntas</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-[10px] text-slate-500 mt-2">
                    Keterangan <b>Di atas KKTP</b> untuk nilai melampaui batas atas rentang KKTP ({{ $skema->kktpMax }}),
                    yaitu peserta didik yang paling siap diberi pengayaan bertaraf lebih tinggi.
                </p>
            @endif
        </div>

        <x-blok-tanda-tangan-dua
            jabatan-kanan="Guru Mata Pelajaran {{ $mapel->nama_mapel }}"
            :nama-kanan="$guruPengampu->name ?? null"
            :nip-kanan="$guruPengampu->nip ?? null"
        />
    </div>
</div>

@endif
@endsection
