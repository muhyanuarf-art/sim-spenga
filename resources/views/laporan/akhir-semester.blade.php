@extends('layouts.app')
@section('title', 'Laporan Akhir Semester — Kelas ' . $kelas->nama_kelas)

@section('content')
@php
    $bolehPilihKelas = $daftarKelas->count() > 1;
    $lamaHari = $mulai->diffInDays($selesai) + 1;
    $lamaBulan = round($lamaHari / 30.44, 1);

    // Angka desimal pada dokumen resmi Indonesia memakai KOMA, bukan titik —
    // disamakan dengan lembar Nilai Rapor Kelas yang dibawa ke rapat yang
    // sama. Nol di ekor dibuang supaya kolom tetap ringkas ("100" bukan
    // "100,0"); nilai kosong ditulis tanda hubung.
    $desimal = fn (?float $v, int $d = 1) => $v === null
        ? '–'
        : rtrim(rtrim(number_format($v, $d, ',', ''), '0'), ',');
    // 4 identitas + nilai(4) + kehadiran(5) + kedisiplinan(3) + ekskul(2)
    $jumlahKolom = 4 + 4 + 5 + 3 + 2;
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
                <label class="label">Dari Tanggal</label>
                <input type="date" name="dari" class="input" value="{{ $mulai->toDateString() }}">
            </div>
            <div>
                <label class="label">Sampai Tanggal</label>
                <input type="date" name="sampai" class="input" value="{{ $selesai->toDateString() }}">
            </div>
            <button class="btn-primary"><i class="fa-solid fa-arrows-rotate mr-1.5"></i> Tampilkan</button>
            <button type="button" onclick="cetakBagian('print-laporan-semester')" class="btn-outline">
                <i class="fa-solid fa-print mr-1.5"></i> Cetak / Export PDF
            </button>
            @if(request()->hasAny(['dari', 'sampai']))
                <a href="{{ route('nilai.laporan-semester', ['kelas_id' => $kelas->id]) }}" class="btn-ghost">
                    <i class="fa-solid fa-xmark mr-1.5"></i> Kembalikan ke satu semester penuh
                </a>
            @endif
        </form>

        @if($tanggalDiturunkan)
            <p class="alert alert-warning mt-3 mb-0">
                <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                <span>
                    Tanggal mulai/selesai <b>{{ $periode->labelPeriode() }}</b> belum diisi, sehingga rentang laporan
                    ini <b>diperkirakan sendiri</b> dari nama tahun ajaran dan semesternya. Agar akurat, minta
                    Kurikulum/Admin melengkapinya di menu Tahun Ajaran.
                </span>
            </p>
        @endif
    </div>

    {{-- ================= Total per item (di layar) ================= --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 no-print">
        <x-panel judul="1. Nilai" ikon="fa-award">
            <p class="text-3xl font-extrabold text-slate-800">{{ $ringkasan['rata_kelas'] !== null ? number_format($ringkasan['rata_kelas'], 2, ',', '') : '–' }}</p>
            <p class="text-xs text-slate-400 mb-3">rata-rata kelas</p>
            <div class="text-xs text-slate-600 space-y-1">
                <p><i class="fa-solid fa-circle-check w-4 text-emerald-500"></i> Tuntas semua mapel: <b>{{ $ringkasan['tuntas_semua'] }}</b> siswa</p>
                <p><i class="fa-solid fa-circle-exclamation w-4 text-rose-500"></i> Ada mapel belum tuntas: <b>{{ $ringkasan['ada_belum_tuntas'] }}</b> siswa</p>
                <p><i class="fa-solid fa-arrows-up-down w-4 text-slate-400"></i> Tertinggi {{ $ringkasan['rata_tertinggi'] ?? '–' }} &middot; terendah {{ $ringkasan['rata_terendah'] ?? '–' }}</p>
            </div>
        </x-panel>

        <x-panel judul="2. Kehadiran" ikon="fa-calendar-check">
            <p class="text-3xl font-extrabold text-slate-800">{{ $ringkasan['persen_hadir_kelas'] !== null ? $desimal($ringkasan['persen_hadir_kelas']) . '%' : '–' }}</p>
            <p class="text-xs text-slate-400 mb-3">kehadiran kelas</p>
            <div class="text-xs text-slate-600 space-y-1">
                <p><i class="fa-solid fa-bed w-4 text-amber-500"></i> Sakit: <b>{{ $ringkasan['total_sakit'] }}</b> hari</p>
                <p><i class="fa-solid fa-envelope w-4 text-sky-500"></i> Izin: <b>{{ $ringkasan['total_izin'] }}</b> hari</p>
                <p><i class="fa-solid fa-user-xmark w-4 text-rose-500"></i> Alfa: <b>{{ $ringkasan['total_alfa'] }}</b> hari</p>
            </div>
        </x-panel>

        <x-panel judul="3. Kedisiplinan" ikon="fa-hand-holding-heart">
            <p class="text-3xl font-extrabold text-slate-800">{{ $ringkasan['total_kasus'] }}</p>
            <p class="text-xs text-slate-400 mb-3">kasus tercatat</p>
            <div class="text-xs text-slate-600 space-y-1">
                <p><i class="fa-solid fa-user w-4 text-slate-400"></i> Siswa berkasus: <b>{{ $ringkasan['siswa_berkasus'] }}</b></p>
                <p><i class="fa-solid fa-scale-unbalanced w-4 text-rose-500"></i> Total poin aktif: <b>{{ $ringkasan['total_poin_aktif'] }}</b></p>
                <p><i class="fa-solid fa-handshake-angle w-4 text-amber-500"></i> Dalam pembinaan: <b>{{ $ringkasan['dalam_pembinaan'] }}</b></p>
            </div>
        </x-panel>

        <x-panel judul="4. Ekstrakurikuler" ikon="fa-people-group">
            <p class="text-3xl font-extrabold text-slate-800">{{ $ringkasan['ikut_ekskul'] }}</p>
            <p class="text-xs text-slate-400 mb-3">siswa mengikuti ekskul</p>
            <div class="text-xs text-slate-600 space-y-1">
                <p><i class="fa-solid fa-list-check w-4 text-slate-400"></i> Total sesi tercatat: <b>{{ $ringkasan['total_sesi_ekskul'] }}</b></p>
                <p><i class="fa-solid fa-percent w-4 text-emerald-500"></i> Kehadiran ekskul: <b>{{ $ringkasan['persen_ekskul_kelas'] !== null ? $desimal($ringkasan['persen_ekskul_kelas']) . '%' : '–' }}</b></p>
                <p><i class="fa-solid fa-user-slash w-4 text-slate-400"></i> Tidak ikut ekskul: <b>{{ $ringkasan['jumlah_siswa'] - $ringkasan['ikut_ekskul'] }}</b></p>
            </div>
        </x-panel>
    </div>

    {{-- ================= LEMBAR LAPORAN (yang dicetak) ================= --}}
    <div class="card p-5 print-section" id="print-laporan-semester">
        <x-kop-surat />

        <div class="text-center mb-4">
            <p class="font-extrabold tracking-[0.2em] text-slate-800 text-sm uppercase">Laporan Akhir Semester</p>
            <p class="font-extrabold text-lg text-slate-800 uppercase">Kelas {{ $kelas->nama_kelas }}</p>
            <p class="text-xs text-slate-500 mt-1">Bahan Rapat Penerimaan Rapor</p>
        </div>

        <div class="grid sm:grid-cols-2 gap-x-8 gap-y-1 text-xs text-slate-600 mb-4">
            <div class="space-y-1">
                <p><span class="inline-block w-28 text-slate-400">Kelas / Fase</span>: <b class="text-slate-800">{{ $kelas->nama_kelas }} / {{ $kelas->fase() }}</b></p>
                <p><span class="inline-block w-28 text-slate-400">Wali Kelas</span>: {{ $kelas->waliKelas->name ?? '-' }}</p>
                <p><span class="inline-block w-28 text-slate-400">Jumlah Siswa</span>: {{ $ringkasan['jumlah_siswa'] }}</p>
                <p><span class="inline-block w-28 text-slate-400">Sekolah</span>: {{ $pengaturanSekolahGlobal->nama_sekolah ?: '-' }}</p>
            </div>
            <div class="space-y-1">
                <p><span class="inline-block w-32 text-slate-400">Semester</span>: {{ $periode->semester }} ({{ $periode->nomorSemester() }})</p>
                <p><span class="inline-block w-32 text-slate-400">Tahun Pelajaran</span>: {{ $periode->nama }}</p>
                <p><span class="inline-block w-32 text-slate-400">Periode Laporan</span>:
                    <b class="text-slate-800">{{ $mulai->translatedFormat('d F Y') }} s.d. {{ $selesai->translatedFormat('d F Y') }}</b>
                    <span class="text-slate-400">({{ $lamaBulan }} bulan)</span>
                </p>
                <p><span class="inline-block w-32 text-slate-400">KKTP</span>: {{ $skema->kktpMin }} &ndash; {{ $skema->kktpMax }}</p>
            </div>
        </div>

        {{-- ===== Tabel utama ===== --}}
        <div class="overflow-x-auto -mx-5">
            <table class="w-full text-[11px] border-collapse">
                <thead class="text-slate-700">
                    <tr class="bg-slate-100">
                        <th rowspan="2" class="border border-slate-300 px-1 py-2 w-8">NO.</th>
                        <th rowspan="2" class="border border-slate-300 px-2 py-2 min-w-[52px]">NIS</th>
                        <th rowspan="2" class="border border-slate-300 px-2 py-2 text-left min-w-[170px] sticky left-0 bg-slate-100 z-10">NAMA PESERTA DIDIK</th>
                        <th colspan="4" class="border border-slate-300 px-2 py-1.5 bg-sky-50">1. NILAI</th>
                        <th colspan="5" class="border border-slate-300 px-2 py-1.5 bg-emerald-50">2. KEHADIRAN</th>
                        <th colspan="3" class="border border-slate-300 px-2 py-1.5 bg-rose-50">3. KEDISIPLINAN</th>
                        <th colspan="2" class="border border-slate-300 px-2 py-1.5 bg-violet-50">4. EKSTRAKURIKULER</th>
                    </tr>
                    <tr class="bg-slate-50">
                        {{-- 1. Nilai --}}
                        <th class="border border-slate-300 px-1 py-1.5 w-10 leading-tight" title="Banyaknya mata pelajaran yang sudah dinilai">Mapel</th>
                        <th class="border border-slate-300 px-1 py-1.5 w-12 leading-tight">Rata-<br>rata</th>
                        <th class="border border-slate-300 px-1 py-1.5 w-12 leading-tight">Pering-<br>kat</th>
                        <th class="border border-slate-300 px-1 py-1.5 w-12 leading-tight" title="Banyaknya mapel dengan nilai di bawah KKTP">Blm<br>Tuntas</th>
                        {{-- 2. Kehadiran --}}
                        <th class="border border-slate-300 px-1 py-1.5 w-9" title="Hadir">H</th>
                        <th class="border border-slate-300 px-1 py-1.5 w-9" title="Sakit">S</th>
                        <th class="border border-slate-300 px-1 py-1.5 w-9" title="Izin">I</th>
                        <th class="border border-slate-300 px-1 py-1.5 w-9" title="Alfa / tanpa keterangan">A</th>
                        <th class="border border-slate-300 px-1 py-1.5 w-12 leading-tight" title="Persentase hadir dari hari yang tercatat">%<br>Hadir</th>
                        {{-- 3. Kedisiplinan --}}
                        <th class="border border-slate-300 px-1 py-1.5 w-10" title="Jumlah kasus pelanggaran">Kasus</th>
                        <th class="border border-slate-300 px-1 py-1.5 w-12 leading-tight" title="Poin pelanggaran aktif (sudah dikurangi pengurangan poin)">Poin<br>Aktif</th>
                        <th class="border border-slate-300 px-1 py-1.5 w-24">Status</th>
                        {{-- 4. Ekstrakurikuler --}}
                        <th class="border border-slate-300 px-2 py-1.5 min-w-[120px]">Kegiatan</th>
                        <th class="border border-slate-300 px-1 py-1.5 w-12 leading-tight">%<br>Hadir</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($baris as $b)
                        <tr class="hover:bg-brand-50/40">
                            <td class="border border-slate-300 text-center text-slate-500">{{ $loop->iteration }}</td>
                            <td class="border border-slate-300 px-2 text-center tabular-nums">{{ $b['siswa']->nis }}</td>
                            <td class="border border-slate-300 px-2 py-1 font-medium whitespace-nowrap sticky left-0 bg-white z-10">{{ $b['siswa']->nama }}</td>

                            {{-- 1. NILAI --}}
                            <td class="border border-slate-300 text-center tabular-nums">{{ $b['mapel_dinilai'] ?: '–' }}</td>
                            <td class="border border-slate-300 text-center tabular-nums font-bold bg-sky-50/50">
                                {{ $b['rata'] !== null ? number_format($b['rata'], 2, ',', '') : '–' }}
                            </td>
                            <td class="border border-slate-300 text-center tabular-nums font-bold bg-sky-50/50">{{ $b['peringkat'] ?? '–' }}</td>
                            <td class="border border-slate-300 text-center tabular-nums font-bold {{ $b['belum_tuntas'] > 0 ? 'bg-rose-50 text-rose-700' : 'text-slate-400' }}"
                                @if($b['mapel_belum_tuntas']->isNotEmpty()) title="{{ $b['mapel_belum_tuntas']->implode(', ') }}" @endif>
                                {{ $b['belum_tuntas'] ?: '–' }}
                            </td>

                            {{-- 2. KEHADIRAN --}}
                            <td class="border border-slate-300 text-center tabular-nums">{{ $b['hadir'] }}</td>
                            <td class="border border-slate-300 text-center tabular-nums {{ $b['sakit'] > 0 ? 'text-amber-600 font-semibold' : 'text-slate-300' }}">{{ $b['sakit'] ?: '–' }}</td>
                            <td class="border border-slate-300 text-center tabular-nums {{ $b['izin'] > 0 ? 'text-sky-600 font-semibold' : 'text-slate-300' }}">{{ $b['izin'] ?: '–' }}</td>
                            <td class="border border-slate-300 text-center tabular-nums {{ $b['alfa'] > 0 ? 'text-rose-600 font-bold' : 'text-slate-300' }}">{{ $b['alfa'] ?: '–' }}</td>
                            <td class="border border-slate-300 text-center tabular-nums font-bold bg-emerald-50/50
                                {{ $b['persen_hadir'] !== null && $b['persen_hadir'] < 90 ? 'text-rose-600' : '' }}">
                                {{ $desimal($b['persen_hadir']) }}
                            </td>

                            {{-- 3. KEDISIPLINAN --}}
                            <td class="border border-slate-300 text-center tabular-nums {{ $b['jumlah_kasus'] > 0 ? 'font-semibold' : 'text-slate-300' }}">{{ $b['jumlah_kasus'] ?: '–' }}</td>
                            <td class="border border-slate-300 text-center tabular-nums font-bold {{ $b['poin_aktif'] > 0 ? 'bg-rose-50 text-rose-700' : 'text-slate-300' }}">{{ $b['poin_aktif'] ?: '–' }}</td>
                            <td class="border border-slate-300 text-center">
                                @if($b['status_bk'] === 'Normal')
                                    <span class="text-slate-300">–</span>
                                @else
                                    <span class="badge {{ $b['status_bk'] === 'Selesai' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                        {{ $b['status_bk'] }}
                                    </span>
                                @endif
                            </td>

                            {{-- 4. EKSTRAKURIKULER --}}
                            <td class="border border-slate-300 px-2 py-1 text-slate-600">
                                {{ $b['ekskul_nama']->isEmpty() ? '–' : $b['ekskul_nama']->implode(', ') }}
                            </td>
                            <td class="border border-slate-300 text-center tabular-nums bg-violet-50/50">
                                {{ $desimal($b['ekskul_persen']) }}
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

                @if($baris->isNotEmpty())
                    <tfoot>
                        <tr class="bg-slate-100 font-bold text-slate-700">
                            <td colspan="3" class="border border-slate-300 px-2 py-1.5 text-right sticky left-0 bg-slate-100 z-10">JUMLAH / RATA-RATA KELAS</td>
                            <td class="border border-slate-300 text-center">{{ $ringkasan['dinilai'] }}</td>
                            <td class="border border-slate-300 text-center tabular-nums bg-sky-100/60">
                                {{ $ringkasan['rata_kelas'] !== null ? number_format($ringkasan['rata_kelas'], 2, ',', '') : '–' }}
                            </td>
                            <td class="border border-slate-300"></td>
                            <td class="border border-slate-300 text-center tabular-nums">{{ $ringkasan['ada_belum_tuntas'] }}</td>
                            <td class="border border-slate-300 text-center tabular-nums">{{ $ringkasan['total_hadir'] }}</td>
                            <td class="border border-slate-300 text-center tabular-nums">{{ $ringkasan['total_sakit'] }}</td>
                            <td class="border border-slate-300 text-center tabular-nums">{{ $ringkasan['total_izin'] }}</td>
                            <td class="border border-slate-300 text-center tabular-nums">{{ $ringkasan['total_alfa'] }}</td>
                            <td class="border border-slate-300 text-center tabular-nums bg-emerald-100/60">{{ $desimal($ringkasan['persen_hadir_kelas']) }}</td>
                            <td class="border border-slate-300 text-center tabular-nums">{{ $ringkasan['total_kasus'] }}</td>
                            <td class="border border-slate-300 text-center tabular-nums bg-rose-100/60">{{ $ringkasan['total_poin_aktif'] }}</td>
                            <td class="border border-slate-300 text-center">{{ $ringkasan['dalam_pembinaan'] }} dibina</td>
                            <td class="border border-slate-300 text-center">{{ $ringkasan['ikut_ekskul'] }} siswa ikut</td>
                            <td class="border border-slate-300 text-center tabular-nums bg-violet-100/60">{{ $desimal($ringkasan['persen_ekskul_kelas']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- ===== Simpulan per item ===== --}}
        <div class="mt-5 cetak-utuh">
            <p class="font-extrabold text-slate-800 text-xs uppercase tracking-wide mb-2 pb-1 border-b border-slate-300">
                Rekapitulasi Total per Item
            </p>
            <div class="grid sm:grid-cols-2 gap-x-8 gap-y-1 text-[11px] text-slate-600">
                <div class="space-y-0.5">
                    <p class="font-semibold text-slate-700 mt-1">1. Nilai</p>
                    <p>Rata-rata kelas <b>{{ $ringkasan['rata_kelas'] !== null ? number_format($ringkasan['rata_kelas'], 2, ',', '') : '–' }}</b>
                        (tertinggi {{ $ringkasan['rata_tertinggi'] ?? '–' }}, terendah {{ $ringkasan['rata_terendah'] ?? '–' }}).</p>
                    <p>Tuntas seluruh mapel <b>{{ $ringkasan['tuntas_semua'] }}</b> siswa;
                        masih ada mapel di bawah KKTP <b class="text-rose-600">{{ $ringkasan['ada_belum_tuntas'] }}</b> siswa.</p>

                    <p class="font-semibold text-slate-700 mt-2">2. Kehadiran</p>
                    <p>Hari tercatat <b>{{ $ringkasan['hari_tercatat'] }}</b> hari-siswa, kehadiran kelas
                        <b>{{ $ringkasan['persen_hadir_kelas'] !== null ? $desimal($ringkasan['persen_hadir_kelas']) . '%' : '–' }}</b>.</p>
                    <p>Sakit <b>{{ $ringkasan['total_sakit'] }}</b>, Izin <b>{{ $ringkasan['total_izin'] }}</b>,
                        Alfa <b class="text-rose-600">{{ $ringkasan['total_alfa'] }}</b> hari.</p>
                </div>
                <div class="space-y-0.5">
                    <p class="font-semibold text-slate-700 mt-1">3. Kedisiplinan</p>
                    <p><b>{{ $ringkasan['siswa_berkasus'] }}</b> siswa tercatat melakukan pelanggaran,
                        seluruhnya <b>{{ $ringkasan['total_kasus'] }}</b> kasus.</p>
                    <p>Total poin aktif kelas <b class="text-rose-600">{{ $ringkasan['total_poin_aktif'] }}</b>;
                        sedang dalam pembinaan <b>{{ $ringkasan['dalam_pembinaan'] }}</b> siswa.</p>

                    <p class="font-semibold text-slate-700 mt-2">4. Ekstrakurikuler</p>
                    <p><b>{{ $ringkasan['ikut_ekskul'] }}</b> dari {{ $ringkasan['jumlah_siswa'] }} siswa mengikuti
                        sedikitnya satu kegiatan.</p>
                    <p>Sesi tercatat <b>{{ $ringkasan['total_sesi_ekskul'] }}</b>, kehadiran
                        <b>{{ $ringkasan['persen_ekskul_kelas'] !== null ? $desimal($ringkasan['persen_ekskul_kelas']) . '%' : '–' }}</b>.</p>
                </div>
            </div>
        </div>

        {{-- ===== Peserta didik yang perlu dibicarakan ===== --}}
        <div class="mt-5 cetak-utuh">
            <p class="font-extrabold text-slate-800 text-xs uppercase tracking-wide mb-2 pb-1 border-b border-slate-300">
                Peserta Didik yang Perlu Perhatian Khusus
            </p>

            @if($perluPerhatian->isEmpty())
                <p class="text-[11px] text-slate-500 italic px-1 py-2">
                    Tidak ada. Seluruh peserta didik tuntas, kehadirannya baik, dan tidak memiliki poin pelanggaran aktif.
                </p>
            @else
                <table class="w-full text-[11px] border-collapse">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700">
                            <th class="border border-slate-300 px-1 py-1.5 w-8">No.</th>
                            <th class="border border-slate-300 px-2 py-1.5 text-left min-w-[160px]">Nama Peserta Didik</th>
                            <th class="border border-slate-300 px-1 py-1.5 w-16">Rata-rata</th>
                            <th class="border border-slate-300 px-2 py-1.5 text-left">Hal yang Perlu Dibicarakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($perluPerhatian as $p)
                            <tr>
                                <td class="border border-slate-300 text-center text-slate-500">{{ $loop->iteration }}</td>
                                <td class="border border-slate-300 px-2 py-1 font-medium">{{ $p['siswa']->nama }}</td>
                                <td class="border border-slate-300 text-center tabular-nums">
                                    {{ $p['rata'] !== null ? number_format($p['rata'], 2, ',', '') : '–' }}
                                </td>
                                <td class="border border-slate-300 px-2 py-1 text-slate-600">
                                    {{ collect($p['alasan'])->implode('; ') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <p class="mt-4 text-[10px] text-slate-500">
            Nilai berasal dari Daftar Nilai guru mata pelajaran; kehadiran memakai aturan satu status final per hari
            (absensi kegiatan sekolah didahulukan, selebihnya status dari guru mapel jam terakhir);
            poin pelanggaran memakai perhitungan yang sama dengan profil poin siswa pada modul BK.
            Persentase kehadiran dihitung dari hari yang <b>tercatat</b>, bukan dari jumlah hari kalender.
        </p>

        <x-blok-tanda-tangan-dua
            jabatan-kanan="Wali Kelas {{ $kelas->nama_kelas }}"
            :nama-kanan="$kelas->waliKelas->name ?? null"
            :nip-kanan="$kelas->waliKelas->nip ?? null"
        />
    </div>
</div>
@endsection
