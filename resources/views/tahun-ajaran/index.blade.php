@extends('layouts.app')
@section('title', 'Tahun Ajaran')

@section('content')
<div class="space-y-6" x-data="{ showForm: false }">

    {{-- Status ringkas periode aktif — tanpa tombol, tanpa tanggal (poin 1 & 2) --}}
    <div class="card p-5">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Periode Aktif</p>
        @if(!$periodeAktif)
            <p class="text-sm text-amber-600">Belum ada periode aktif. Aktifkan salah satu Tahun Ajaran di tabel bawah.</p>
        @else
            <p class="text-lg font-bold text-slate-800">
                {{ $periodeAktif->nama }} — Semester {{ $periodeAktif->semester }}
                <span class="badge {{ $periodeAktif->statusBadgeClass() }} ml-1 align-middle">{{ $periodeAktif->statusLabel() }}</span>
            </p>
        @endif
    </div>

    {{-- Buat Tahun Ajaran berikutnya (2 semester sekaligus) kalau belum ada --}}
    @if($namaTahunAjaranBerikutnya && !$tahunAjaranBerikutnyaSudahAda)
    <div class="card p-5">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <p class="text-sm text-slate-600">
                Siapkan Tahun Ajaran <span class="font-bold">{{ $namaTahunAjaranBerikutnya }}</span> (Semester Ganjil & Genap langsung dibuat, status Akan Datang — tidak langsung aktif).
            </p>
            <form method="POST" action="{{ route('tahun-ajaran.buat-baru') }}"
                  onsubmit="return confirm('Buat Tahun Ajaran {{ $namaTahunAjaranBerikutnya }} (Semester Ganjil & Genap)?')">
                @csrf
                <input type="hidden" name="nama" value="{{ $namaTahunAjaranBerikutnya }}">
                <button class="btn-primary whitespace-nowrap">+ Buat Tahun Ajaran {{ $namaTahunAjaranBerikutnya }}</button>
            </form>
        </div>
    </div>
    @endif

    <div class="flex justify-end">
        <button @click="showForm = !showForm" class="btn-primary">+ Tambah Tahun Ajaran</button>
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
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th>Tahun Ajaran</th><th>Semester</th><th>Status</th><th>Kunci</th><th class="th-aksi">Aksi</th></tr></thead>
                @forelse($tahunAjaran as $t)
                <tbody x-data="{ editing: false, salin: false }">
                    <tr x-show="!editing && !salin">
                        <td class="font-semibold">{{ $t->nama }}</td>
                        <td>{{ $t->semester }}</td>
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
                                @if($semesterHilangPerNama->get($t->nama))
                                <form method="POST" action="{{ route('tahun-ajaran.store') }}">
                                    @csrf
                                    <input type="hidden" name="nama" value="{{ $t->nama }}">
                                    <input type="hidden" name="semester" value="{{ $semesterHilangPerNama[$t->nama] }}">
                                    <button class="btn-chip btn-chip-success">+ Tambah Semester {{ $semesterHilangPerNama[$t->nama] }}</button>
                                </form>
                                @endif
                                <button type="button" @click="salin = true" class="btn-chip btn-chip-edit">📋 Salin Data</button>
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
                                <form method="POST" action="{{ route('tahun-ajaran.kunci', $t) }}" onsubmit="return confirm('Semester {{ $t->semester }} akan ditutup.\nSetelah ditutup, SELURUH data pada semester ini (jurnal, absensi, jadwal, guru mengajar, BK, dst) tidak dapat diubah oleh pengguna biasa — tapi tetap bisa dilihat & dijadikan sumber Salin Data.\nAnda yakin ingin melanjutkan?')">
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

                    {{-- poin 6: Salin Data — pilih tujuan, lalu ke halaman Preview (checklist) sebelum benar-benar menyalin --}}
                    <tr x-show="salin" x-cloak>
                        <td colspan="5" class="bg-brand-50/40">
                            <form method="GET" action="{{ route('tahun-ajaran.duplikasi.preview') }}" class="grid sm:grid-cols-3 gap-3 items-end py-2"
                                  onsubmit="return confirm('Anda akan menyalin data dari {{ $t->nama }} - Semester {{ $t->semester }} ke periode tujuan yang dipilih. Lanjutkan?')">
                                <input type="hidden" name="dari_tahun_ajaran_id" value="{{ $t->id }}">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-500 mb-1">
                                        Salin Kelas, Wali Kelas, Guru Mengajar & Jadwal dari {{ $t->nama }} - Semester {{ $t->semester }} ke:
                                    </label>
                                    <select name="tahun_ajaran_tujuan" required class="input">
                                        <option value="">Pilih tujuan...</option>
                                        @foreach($tahunAjaran as $tt)
                                            @if($tt->id !== $t->id)
                                            <option value="{{ $tt->id }}">{{ $tt->labelPeriode() }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit" class="btn-primary h-[38px]">Lihat Preview</button>
                                    <button type="button" @click="salin = false" class="btn-outline h-[38px]">Batal</button>
                                </div>
                            </form>
                        </td>
                    </tr>

                    <tr x-show="editing" x-cloak>
                        <td colspan="5" class="bg-brand-50/40">
                            <form method="POST" action="{{ route('tahun-ajaran.update', $t) }}" class="grid sm:grid-cols-3 gap-3 items-end py-2">
                                @csrf @method('PUT')
                                <input type="text" name="nama" value="{{ $t->nama }}" required class="input">
                                <select name="semester" required class="input">
                                    <option value="Ganjil" {{ $t->semester === 'Ganjil' ? 'selected' : '' }}>Ganjil</option>
                                    <option value="Genap" {{ $t->semester === 'Genap' ? 'selected' : '' }}>Genap</option>
                                </select>
                                @unless($t->is_active)
                                <select name="status" class="input">
                                    <option value="akan_datang" {{ $t->status === 'akan_datang' ? 'selected' : '' }}>Akan Datang</option>
                                    <option value="selesai" {{ $t->status === 'selesai' ? 'selected' : '' }}>Selesai</option>
                                </select>
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
                    <tr><td colspan="5" class="text-center text-slate-400 py-8">Belum ada data.</td></tr>
                </tbody>
                @endforelse
            </table>
        </div>
    </div>
</div>
@endsection
