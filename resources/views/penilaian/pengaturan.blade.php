@extends('layouts.app')
@section('title', 'Pengaturan Penilaian')

@section('content')
@php
    $terkunci = $periode->isTerkunci();
@endphp

<div class="space-y-6">

    {{-- ================= Periode yang diatur ================= --}}
    <div class="card p-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="label">Periode yang diatur</label>
                <select name="tahun_ajaran_id" class="select" onchange="this.form.submit()">
                    @foreach($daftarPeriode as $p)
                        <option value="{{ $p->id }}" @selected($p->id === $periode->id)>
                            {{ $p->labelSingkat() }}{{ $p->is_active ? ' — aktif' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <p class="text-xs text-slate-500 pb-2 max-w-xl">
                Pengaturan berlaku <b>per periode</b>. Mengubah bobot untuk semester ini tidak mengubah
                angka rapor semester yang sudah lewat.
            </p>
        </form>

        @if($terkunci)
            <p class="alert alert-warning mt-3 mb-0">
                <i class="fa-solid fa-lock mt-0.5"></i>
                <span>{{ $periode->labelPeriode() }} sudah ditutup dan terkunci — pengaturannya tidak dapat diubah.</span>
            </p>
        @elseif($jumlahNilai > 0)
            <p class="alert alert-info mt-3 mb-0">
                <i class="fa-solid fa-circle-info mt-0.5"></i>
                <span>
                    Sudah ada <b>{{ number_format($jumlahNilai) }} baris nilai</b> pada periode ini. Menyimpan perubahan
                    di halaman ini akan <b>menghitung ulang seluruh nilai akhir, predikat, dan status ketuntasannya</b>
                    mengikuti aturan yang baru.
                </span>
            </p>
        @endif
    </div>

    <form method="POST" action="{{ route('penilaian.pengaturan.update', ['tahun_ajaran_id' => $periode->id]) }}"
          x-data="{
              bobot60: {{ old('bobot_formatif_sumatif', $pengaturan->bobot_formatif_sumatif) }},
              bobotAsts: {{ old('bobot_asts', $pengaturan->bobot_asts) }},
              bobotAsas: {{ old('bobot_asas', $pengaturan->bobot_asas) }},
              kf: {{ old('komposisi_formatif', $pengaturan->komposisi_formatif) }},
              ks: {{ old('komposisi_sumatif_lm', $pengaturan->komposisi_sumatif_lm) }},
              get totalBobot() { return Number(this.bobot60) + Number(this.bobotAsts) + Number(this.bobotAsas) },
              get totalKomposisi() { return Number(this.kf) + Number(this.ks) },
          }">
        @csrf
        @method('PUT')
        <input type="hidden" name="tahun_ajaran_id" value="{{ $periode->id }}">

        <div class="space-y-6">

            {{-- ================= Bobot nilai rapor ================= --}}
            <x-panel judul="Bobot Nilai Rapor"
                     deskripsi="Tiga komponen penyusun Nilai Akhir (Rapor). Totalnya harus tepat 100%."
                     ikon="fa-percent">
                <div class="grid md:grid-cols-3 gap-5">
                    <div>
                        <label class="label">Formatif + Sumatif Lingkup Materi</label>
                        <div class="relative">
                            <input type="number" name="bobot_formatif_sumatif" x-model="bobot60"
                                   min="0" max="100" class="input pr-9" @disabled($terkunci)>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">%</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Kolom “%BOBOT” pertama pada daftar nilai. Bawaan 60%.</p>
                    </div>
                    <div>
                        <label class="label">ASTS — Asesmen Sumatif Tengah Semester</label>
                        <div class="relative">
                            <input type="number" name="bobot_asts" x-model="bobotAsts"
                                   min="0" max="100" class="input pr-9" @disabled($terkunci)>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">%</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Bawaan 20%.</p>
                    </div>
                    <div>
                        <label class="label">{{ $skema->labelSumatifAkhir() }} — {{ $skema->labelPanjangSumatifAkhir() }}</label>
                        <div class="relative">
                            <input type="number" name="bobot_asas" x-model="bobotAsas"
                                   min="0" max="100" class="input pr-9" @disabled($terkunci)>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">%</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">
                            Bawaan 20%. Namanya otomatis <b>ASAS</b> pada Semester Ganjil dan <b>ASAT</b> pada Semester Genap.
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-3 text-sm">
                    <span class="text-slate-500">Total bobot:</span>
                    <span class="badge text-sm px-3 py-1"
                          :class="totalBobot === 100 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'">
                        <span x-text="totalBobot"></span>%
                    </span>
                    <span x-show="totalBobot !== 100" x-cloak class="text-rose-600 text-xs">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> Harus tepat 100% agar dapat disimpan.
                    </span>
                </div>
            </x-panel>

            {{-- ================= Komposisi di dalam bobot 60% ================= --}}
            <x-panel judul="Komposisi di Dalam Bobot Formatif + Sumatif Lingkup Materi"
                     deskripsi="Pada format daftar nilai, Formatif dan Sumatif Lingkup Materi berbagi satu kolom bobot. Porsi masing-masing ditentukan di sini."
                     ikon="fa-scale-balanced">
                <div class="grid md:grid-cols-2 gap-5">
                    <div>
                        <label class="label">Porsi Nilai Formatif (rata-rata TPF)</label>
                        <div class="relative">
                            <input type="number" name="komposisi_formatif" x-model="kf"
                                   min="0" max="100" class="input pr-9" @disabled($terkunci)>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">%</span>
                        </div>
                    </div>
                    <div>
                        <label class="label">Porsi Sumatif Lingkup Materi (rata-rata LM)</label>
                        <div class="relative">
                            <input type="number" name="komposisi_sumatif_lm" x-model="ks"
                                   min="0" max="100" class="input pr-9" @disabled($terkunci)>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">%</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-3 text-sm">
                    <span class="text-slate-500">Total komposisi:</span>
                    <span class="badge text-sm px-3 py-1"
                          :class="totalKomposisi === 100 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'">
                        <span x-text="totalKomposisi"></span>%
                    </span>
                    <span x-show="totalKomposisi !== 100" x-cloak class="text-rose-600 text-xs">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> Harus tepat 100%.
                    </span>
                </div>

                <p class="alert alert-info mt-4 mb-0 text-xs">
                    <i class="fa-solid fa-calculator mt-0.5"></i>
                    <span>
                        Contoh dengan pengaturan saat ini — siswa dengan rata-rata Formatif <b>80</b>,
                        rata-rata Sumatif Lingkup Materi <b>75</b>, ASTS <b>78</b>, {{ $skema->labelSumatifAkhir() }} <b>82</b>:
                        <br>
                        nilai gabungan =
                        (80 &times; <span x-text="kf"></span>% + 75 &times; <span x-text="ks"></span>%) ÷ 100 =
                        <b x-text="((80 * Number(kf) + 75 * Number(ks)) / (Number(kf) + Number(ks) || 1)).toFixed(2)"></b>,
                        <br>
                        Nilai Akhir =
                        (<span x-text="((80 * Number(kf) + 75 * Number(ks)) / (Number(kf) + Number(ks) || 1)).toFixed(2)"></span>
                        &times; <span x-text="bobot60"></span>% + 78 &times; <span x-text="bobotAsts"></span>% + 82 &times; <span x-text="bobotAsas"></span>%) ÷ 100 =
                        <b x-text="(((80 * Number(kf) + 75 * Number(ks)) / (Number(kf) + Number(ks) || 1) * Number(bobot60) + 78 * Number(bobotAsts) + 82 * Number(bobotAsas)) / (Number(bobot60) + Number(bobotAsts) + Number(bobotAsas) || 1)).toFixed(2)"></b>
                    </span>
                </p>
            </x-panel>

            {{-- ================= KKTP per tingkat ================= --}}
            <x-panel judul="KKTP — Kriteria Ketercapaian Tujuan Pembelajaran"
                     deskripsi="Nilai di bawah KKTP minimum wajib remedi. Rentangnya juga menentukan predikat A/B/C/D."
                     ikon="fa-bullseye">
                <div class="overflow-x-auto">
                    <table class="table-clean">
                        <thead>
                            <tr>
                                <th>Tingkat</th>
                                <th class="w-40">KKTP Minimum</th>
                                <th class="w-40">KKTP Maksimum</th>
                                <th>Predikat yang berlaku</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kktp as $tingkat => $baris)
                                @php $r = \App\Support\SkemaPenilaian::untuk($periode, $tingkat)->rentangPredikat(); @endphp
                                <tr>
                                    <td class="font-semibold text-slate-700">Kelas {{ $tingkat }}</td>
                                    <td>
                                        <input type="number" name="kktp[{{ $tingkat }}][kktp_min]" min="0" max="100"
                                               value="{{ old("kktp.$tingkat.kktp_min", $baris->kktp_min) }}"
                                               class="input" @disabled($terkunci)>
                                    </td>
                                    <td>
                                        <input type="number" name="kktp[{{ $tingkat }}][kktp_max]" min="0" max="100"
                                               value="{{ old("kktp.$tingkat.kktp_max", $baris->kktp_max) }}"
                                               class="input" @disabled($terkunci)>
                                    </td>
                                    <td class="text-xs text-slate-500">
                                        @foreach($r as $huruf => $rentang)
                                            <span class="inline-block mr-3 whitespace-nowrap">
                                                <b>{{ $huruf }}</b> {{ $rentang['dari'] }}–{{ $rentang['sampai'] }}
                                            </span>
                                        @endforeach
                                        <span class="block text-[11px] text-slate-400 mt-0.5">
                                            (mengikuti nilai yang tersimpan; berubah setelah disimpan)
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-panel>

            {{-- ================= Bentuk daftar nilai & remedi ================= --}}
            <x-panel judul="Bentuk Daftar Nilai & Perhitungan Remedi"
                     deskripsi="Banyaknya kolom yang tampil di lembar guru, serta cara nilai remedi diperhitungkan."
                     ikon="fa-sliders">
                <div class="grid md:grid-cols-3 gap-5">
                    <div>
                        <label class="label">Jumlah kolom TPF (Formatif)</label>
                        <input type="number" name="jumlah_tpf" min="1" max="12"
                               value="{{ old('jumlah_tpf', $pengaturan->jumlah_tpf) }}" class="input" @disabled($terkunci)>
                        <p class="text-[11px] text-slate-400 mt-1">TPF ke-n dibaca sebagai penilaian formatif BAB ke-n. Bawaan 7.</p>
                    </div>
                    <div>
                        <label class="label">Jumlah Lingkup Materi (LM)</label>
                        <input type="number" name="jumlah_lm" min="1" max="8"
                               value="{{ old('jumlah_lm', $pengaturan->jumlah_lm) }}" class="input" @disabled($terkunci)>
                        <p class="text-[11px] text-slate-400 mt-1">Tiap LM punya sepasang kolom SUM &amp; REM. Bawaan 4.</p>
                    </div>
                    <div>
                        <label class="label">Perhitungan nilai remedi</label>
                        <select name="kebijakan_remedial" class="select" @disabled($terkunci)>
                            @foreach(\App\Support\SkemaPenilaian::KEBIJAKAN as $kunci => $label)
                                <option value="{{ $kunci }}" @selected(old('kebijakan_remedial', $pengaturan->kebijakan_remedial) === $kunci)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="alert alert-info mt-4 mb-0 text-xs">
                    <i class="fa-solid fa-lightbulb mt-0.5"></i>
                    <div class="flex-1 space-y-1.5">
                        <p class="font-semibold">Bagaimana rata-rata Sumatif Lingkup Materi dihitung</p>
                        <p>
                            Setiap lingkup materi diringkas dulu menjadi <b>satu</b> nilai dari pasangan SUM &amp; REM-nya,
                            baru nilai-nilai itu dirata-ratakan. Ini penting: kalau SUM dan REM ikut dirata-rata sebagai
                            dua nilai terpisah, siswa yang remedi otomatis punya lebih banyak “suara” daripada siswa yang
                            sekali langsung tuntas, dan nilai satu lingkup materi jadi menekan lingkup materi lainnya.
                        </p>
                        <ul class="list-disc list-inside space-y-0.5">
                            <li><b>Dibatasi KKTP</b> (disarankan) — nilai = nilai tertinggi antara SUM dan REM, tetapi REM tidak boleh melampaui KKTP minimum. Adil dua arah: yang remedi tetap tuntas, tapi tidak melampaui yang sudah tuntas sejak awal.</li>
                            <li><b>Tertinggi</b> — hasil terbaik dipakai apa adanya, tanpa batas.</li>
                            <li><b>Rata-rata</b> — usaha awal dan hasil remedi sama-sama diperhitungkan.</li>
                        </ul>
                    </div>
                </div>
            </x-panel>

            @unless($terkunci)
                <div class="flex justify-end gap-2">
                    <a href="{{ route('penilaian.pengaturan.edit', ['tahun_ajaran_id' => $periode->id]) }}" class="btn-ghost">Batal</a>
                    <button class="btn-primary" :disabled="totalBobot !== 100 || totalKomposisi !== 100">
                        <i class="fa-solid fa-floppy-disk mr-1.5"></i> Simpan Pengaturan
                    </button>
                </div>
            @endunless
        </div>
    </form>
</div>
@endsection
