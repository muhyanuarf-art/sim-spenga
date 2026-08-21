@extends('layouts.app')
@section('title', 'Tahun Ajaran')

@section('content')
<div class="space-y-6" x-data="{ showForm: false, showDuplikasi: false }">

    {{-- STEP 3 Bagian 14 — Kartu Periode Aktif + tombol pergantian semester --}}
    <div class="card p-5">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Periode Aktif</p>
        @if(!$periodeAktif)
            <p class="text-sm text-amber-600">Belum ada periode aktif. Aktifkan salah satu Tahun Ajaran di tabel bawah.</p>
        @else
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-lg font-bold text-slate-800">Tahun Ajaran {{ $periodeAktif->nama }}</p>
                    <p class="text-sm text-slate-500">Semester {{ $periodeAktif->semester }}</p>
                    <span class="badge {{ $periodeAktif->statusBadgeClass() }} mt-1 inline-block">{{ $periodeAktif->statusLabel() }}</span>
                </div>

                @if($periodeAktif->bisaGantiSemester())
                    @php $semesterBerikutnya = $periodeAktif->semesterBerikutnya(); @endphp
                    <div class="text-right">
                        <form method="POST" action="{{ route('tahun-ajaran.ganti-semester', $periodeAktif) }}"
                              onsubmit="return confirm('Semester {{ $periodeAktif->semester }} akan ditutup dan dikunci. Semester {{ $semesterBerikutnya->semester }} akan menjadi periode aktif. Data Semester {{ $periodeAktif->semester }} tetap dapat dilihat tetapi tidak dapat diubah oleh pengguna biasa. Lanjutkan?')">
                            @csrf
                            <button class="btn-primary">🔁 Tutup Semester {{ $periodeAktif->semester }} & Aktifkan Semester {{ $semesterBerikutnya->semester }}</button>
                        </form>
                        @if($jadwalSemesterBerikutnyaTersedia === false)
                            <p class="text-xs text-amber-600 mt-2 max-w-xs">
                                ⚠️ Jadwal Semester {{ $semesterBerikutnya->semester }} belum tersedia. Gunakan tombol
                                "📋 Salin Mapping Guru/Jadwal" di atas untuk menyalin dari Semester {{ $periodeAktif->semester }}, atau buat jadwal baru sebelum/sesudah pergantian.
                            </p>
                        @endif
                    </div>
                @elseif($periodeAktif->semester === 'Ganjil' && !$periodeAktif->semesterBerikutnya())
                    <p class="text-sm text-slate-400 max-w-xs text-right">
                        Semester Genap untuk tahun ajaran {{ $periodeAktif->nama }} belum dibuat. Tambahkan dulu di tabel bawah untuk mengaktifkan pergantian semester.
                    </p>
                @endif
            </div>
        @endif
    </div>

    {{-- STEP 4 Bagian 2/4/19-21 — Kartu Tahun Ajaran Berikutnya --}}
    @if($namaTahunAjaranBerikutnya)
    <div class="card p-5">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Tahun Ajaran Berikutnya</p>
        @if($tahunAjaranBerikutnyaSudahAda)
            <div class="flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm text-slate-600">
                    Tahun Ajaran <span class="font-bold">{{ $namaTahunAjaranBerikutnya }}</span> sudah dibuat.
                    Sistem akan menolak aktivasi otomatis kalau Tahun Ajaran {{ $periodeAktif->nama }} belum
                    selesai &amp; terkunci sepenuhnya (kedua semester).
                </p>
                <a href="{{ route('tahun-ajaran.persiapan', $tahunAjaranBerikutnya) }}" class="btn-primary whitespace-nowrap">
                    📋 Lihat Persiapan {{ $namaTahunAjaranBerikutnya }}
                </a>
            </div>
        @else
            <div class="flex flex-wrap items-end justify-between gap-4">
                <p class="text-sm text-slate-600 max-w-md">
                    Siapkan Tahun Ajaran <span class="font-bold">{{ $namaTahunAjaranBerikutnya }}</span> sekarang
                    (Semester 1 &amp; Semester 2 langsung dibuat, status Akan Datang — TIDAK langsung aktif),
                    supaya Kenaikan Kelas, Wali Kelas, Guru Mengajar, dan Jadwal bisa disiapkan lebih dulu
                    sebelum Tahun Ajaran {{ $periodeAktif->nama }} benar-benar ditutup.
                </p>
                <form method="POST" action="{{ route('tahun-ajaran.buat-baru') }}" class="flex flex-wrap items-end gap-3"
                      onsubmit="return confirm('Buat Tahun Ajaran {{ $namaTahunAjaranBerikutnya }} (Semester 1 & Semester 2)? Tahun ajaran ini TIDAK akan langsung aktif.')">
                    @csrf
                    <input type="hidden" name="nama" value="{{ $namaTahunAjaranBerikutnya }}">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="input">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Selesai</label>
                        <input type="date" name="tanggal_selesai" class="input">
                    </div>
                    <button class="btn-primary h-[38px]">+ Buat Tahun Ajaran {{ $namaTahunAjaranBerikutnya }}</button>
                </form>
            </div>
        @endif
    </div>
    @endif

    <div class="flex justify-end gap-2">
        <button @click="showDuplikasi = !showDuplikasi" class="btn-outline">📋 Salin Mapping Guru/Jadwal</button>
        <button @click="showForm = !showForm" class="btn-primary">+ Tambah Tahun Ajaran</button>
    </div>

    <div class="card p-5" x-show="showDuplikasi" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-1">Salin Mapping Guru Mengajar & Jadwal</p>
        <p class="text-sm text-slate-400 mb-4">
            Menyalin mapping Guru Mengajar dan Jadwal Pelajaran dari tahun ajaran sumber ke tahun ajaran tujuan,
            supaya tidak perlu input ulang dari nol. Kelas tujuan dicari otomatis berdasarkan nama & tingkat
            yang sama PADA TAHUN AJARAN TUJUAN (bukan kelas dari tahun sumber) — kalau belum ada, baris itu
            dilewati dan disebutkan di halaman preview berikutnya. Data yang sudah ada di tujuan juga otomatis
            dilewati (aman diulang, tidak akan dobel).
        </p>
        <form method="GET" action="{{ route('tahun-ajaran.duplikasi.preview') }}" class="grid sm:grid-cols-3 gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Dari (sumber)</label>
                <select name="dari_tahun_ajaran_id" required class="input">
                    @foreach($tahunAjaran as $t)
                        <option value="{{ $t->id }}">{{ $t->labelPeriode() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Ke (tujuan)</label>
                <select name="tahun_ajaran_tujuan" required class="input">
                    @foreach($tahunAjaran as $t)
                        <option value="{{ $t->id }}">{{ $t->labelPeriode() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary h-[38px]">Lihat Preview</button>
        </form>
    </div>

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Tahun Ajaran</p>
        <form method="POST" action="{{ route('tahun-ajaran.store') }}" class="grid sm:grid-cols-3 gap-3 items-end">
            @csrf
            <input type="text" name="nama" placeholder="Contoh: 2026/2027" required class="input">
            <select name="semester" required class="input">
                <option value="Ganjil">Ganjil</option>
                <option value="Genap">Genap</option>
            </select>
            <div></div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" class="input">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai" class="input">
            </div>
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Tahun Ajaran</th><th>Semester</th><th>Tanggal</th><th>Status</th><th>Kunci</th><th class="th-aksi">Aksi</th></tr></thead>
                @forelse($tahunAjaran as $t)
                <tbody x-data="{ editing: false }">
                    <tr x-show="!editing">
                        <td class="font-semibold">{{ $t->nama }}</td>
                        <td>{{ $t->semester }}</td>
                        <td class="text-sm text-slate-500">{{ $t->rentangTanggal() ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $t->statusBadgeClass() }}">{{ $t->statusLabel() }}</span>
                        </td>
                        <td>
                            @if($t->isTerkunci())
                                <span class="badge bg-red-50 text-red-700" title="Ditutup {{ optional($t->terkunci_at)->translatedFormat('d M Y H:i') }} oleh {{ $t->terkunciOleh->name ?? '-' }}">🔒 Terkunci</span>
                            @elseif($t->dibuka_at)
                                <span class="badge bg-slate-100 text-slate-500" title="Dibuka kembali {{ $t->dibuka_at->translatedFormat('d M Y H:i') }} oleh {{ $t->dibukaOleh->name ?? '-' }}">🔓 Terbuka</span>
                            @else
                                <span class="badge bg-slate-100 text-slate-500">🔓 Terbuka</span>
                            @endif
                        </td>
                        <td class="td-aksi">
                            <div class="action-buttons">
                                <button type="button" @click="editing = true" class="btn-chip btn-chip-edit">✏️ Edit</button>
                                @unless($t->is_active)
                                <form method="POST" action="{{ route('tahun-ajaran.aktifkan', $t) }}">
                                    @csrf
                                    <button class="btn-chip btn-chip-success">✅ Aktifkan</button>
                                </form>
                                @endunless
                                @if($t->isTerkunci())
                                    @if(auth()->user()->isAdmin())
                                    <form method="POST" action="{{ route('tahun-ajaran.buka-kunci', $t) }}" onsubmit="return confirm('Semester {{ $t->semester }} ini sudah terkunci.\nMembuka kembali akan memungkinkan perubahan data historis.\nLanjutkan?')">
                                        @csrf
                                        <button class="btn-chip btn-chip-success">🔓 Buka Kembali</button>
                                    </form>
                                    @endif
                                @else
                                <form method="POST" action="{{ route('tahun-ajaran.kunci', $t) }}" onsubmit="return confirm('Semester {{ $t->semester }} akan ditutup.\nSetelah ditutup, data transaksi pada semester ini tidak dapat diubah oleh pengguna biasa.\nAnda yakin ingin melanjutkan?')">
                                    @csrf
                                    <button class="btn-chip btn-chip-cancel">🔒 Tutup Semester</button>
                                </form>
                                @endif
                                @unless($t->isTerkunci())
                                <form method="POST" action="{{ route('tahun-ajaran.destroy', $t) }}" onsubmit="return confirm('Hapus tahun ajaran ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-chip btn-chip-delete">🗑️ Hapus</button>
                                </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                    <tr x-show="editing" x-cloak>
                        <td colspan="6" class="bg-brand-50/40">
                            <form method="POST" action="{{ route('tahun-ajaran.update', $t) }}" class="grid sm:grid-cols-3 gap-3 items-end py-2">
                                @csrf @method('PUT')
                                <input type="text" name="nama" value="{{ $t->nama }}" required class="input">
                                <select name="semester" required class="input">
                                    <option value="Ganjil" {{ $t->semester === 'Ganjil' ? 'selected' : '' }}>Ganjil</option>
                                    <option value="Genap" {{ $t->semester === 'Genap' ? 'selected' : '' }}>Genap</option>
                                </select>
                                <div></div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Mulai</label>
                                    <input type="date" name="tanggal_mulai" value="{{ optional($t->tanggal_mulai)->format('Y-m-d') }}" class="input">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 mb-1">Tanggal Selesai</label>
                                    <input type="date" name="tanggal_selesai" value="{{ optional($t->tanggal_selesai)->format('Y-m-d') }}" class="input">
                                </div>
                                @unless($t->is_active)
                                <div>
                                    <label class="block text-xs font-semibold text-slate-500 mb-1">Status</label>
                                    <select name="status" class="input">
                                        <option value="akan_datang" {{ $t->status === 'akan_datang' ? 'selected' : '' }}>Akan Datang</option>
                                        <option value="selesai" {{ $t->status === 'selesai' ? 'selected' : '' }}>Selesai</option>
                                    </select>
                                </div>
                                @endunless
                                <div class="flex gap-2">
                                    <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                                    <button type="button" @click="editing = false" class="btn-outline h-[38px]">Batal</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                </tbody>
                @empty
                <tbody>
                    <tr><td colspan="6" class="text-center text-slate-400 py-8">Belum ada data.</td></tr>
                </tbody>
                @endforelse
            </table>
        </div>
    </div>
</div>
@endsection
