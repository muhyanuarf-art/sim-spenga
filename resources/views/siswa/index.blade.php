@extends('layouts.app')
@section('title', 'Data Siswa')

@section('content')
<div class="space-y-6" x-data="{ showForm: false }">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama/NIS..." class="input max-w-xs">
            <select name="kelas_id" class="input max-w-[160px]" onchange="this.form.submit()">
                <option value="">Semua Kelas</option>
                @foreach($kelasList as $k)<option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>@endforeach
            </select>
            <button class="btn-outline">Cari</button>
        </form>
        <div class="flex gap-2 flex-wrap">
            @if($siswaTanpaAkunOrtu > 0)
                {{-- Sekali klik untuk seluruh siswa yang belum punya akun portal.
                     Aman diulang: siswa yang sudah punya akun dilewati. --}}
                <form method="POST" action="{{ route('akun-ortu.buat-semua') }}"
                      onsubmit="return confirm('Buatkan akun portal orang tua untuk {{ $siswaTanpaAkunOrtu }} siswa yang belum punya? Login memakai NIS masing-masing, password awal &quot;{{ \App\Models\OrangTua::PASSWORD_DEFAULT }}&quot;.')">
                    @csrf
                    <button class="btn-outline">
                        <i class="fa-solid fa-user-plus mr-1.5"></i> Buatkan Akun Ortu ({{ $siswaTanpaAkunOrtu }})
                    </button>
                </form>
            @endif
            <a href="{{ route('siswa.import.form') }}" class="btn-outline"><i class="fa-solid fa-file-import mr-1.5"></i> Import Excel</a>
            <button @click="showForm = !showForm" class="btn-primary">+ Tambah Siswa</button>
        </div>
    </div>

    @if($siswaTanpaAkunOrtu > 0)
        <p class="alert alert-info mb-0">
            <i class="fa-solid fa-circle-info mt-0.5"></i>
            <span>
                <b>{{ $siswaTanpaAkunOrtu }} siswa aktif belum punya akun Portal Orang Tua</b>, sehingga orang tuanya
                belum bisa memantau kehadiran anaknya. Tekan tombol <b>Buatkan Akun Ortu</b> di atas untuk membuat
                semuanya sekaligus.
            </span>
        </p>
    @endif

    <div class="card p-5" x-show="showForm" x-cloak x-transition>
        <p class="font-bold text-slate-800 mb-4">Tambah Siswa</p>
        <form method="POST" action="{{ route('siswa.store') }}" class="grid sm:grid-cols-4 gap-3 items-end">
            @csrf
            <input type="text" name="nis" placeholder="NIS" required class="input">
            <input type="text" name="nisn" placeholder="NISN (opsional)" class="input">
            <input type="text" name="nama" placeholder="Nama Lengkap" required class="input sm:col-span-2">
            <select name="jenis_kelamin" required class="input">
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
            </select>
            <select name="kelas_id" required class="input">
                <option value="">Pilih Kelas</option>
                @foreach($kelasList as $k)<option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>@endforeach
            </select>
            <input type="text" name="nama_ortu" placeholder="Nama Orang Tua/Wali (opsional)" class="input sm:col-span-2">
            <input type="text" name="no_wa_ortu" placeholder="No. WhatsApp Ortu, mis. 081234567890" class="input">
            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
        </form>
    </div>

    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th class="w-12 text-center">No</th><th>NIS</th><th>Nama</th><th>L/P</th><th>Kelas</th><th>WA Ortu</th><th>Akun Ortu</th><th>Status</th><th class="th-aksi">Aksi</th></tr></thead>
                @forelse($siswas as $s)
                <tbody x-data="{ editing: false, pindah: false }">
                    <tr x-show="!editing && !pindah">
                        <td class="text-center text-slate-400">{{ $siswas->firstItem() + $loop->index }}</td>
                        <td>{{ $s->nis }}</td>
                        <td class="font-medium">{{ $s->nama }}</td>
                        <td>{{ $s->jenis_kelamin }}</td>
                        <td>{{ $s->kelas->nama_kelas }}</td>
                        <td>
                            @if($s->no_wa_ortu)
                                <span class="badge bg-emerald-50 text-emerald-700" title="{{ $s->nama_ortu }}"><i class="fa-solid fa-mobile-screen mr-1.5"></i> {{ $s->no_wa_ortu }}</span>
                            @else
                                <span class="badge bg-slate-100 text-slate-400">Belum diisi</span>
                            @endif
                        </td>
                        {{-- Akun Portal Orang Tua. Menunya yang berdiri sendiri dihapus
                             (isinya cuma kredensial, tidak ada data yang perlu dikelola
                             tersendiri) dan dipindah ke sini — statusnya langsung terlihat
                             di baris siswanya, dan tindakannya tinggal satu klik. --}}
                        <td>
                            @if(! $s->orangTua)
                                <form method="POST" action="{{ route('akun-ortu.buat-satu', $s) }}" class="inline-block"
                                      onsubmit="return confirm('Buatkan akun portal untuk orang tua {{ $s->nama }}? Login memakai NIS {{ $s->nis }}, password awal &quot;{{ \App\Models\OrangTua::PASSWORD_DEFAULT }}&quot;.')">
                                    @csrf
                                    <button class="btn-chip btn-chip-cancel" title="Orang tua belum bisa login">
                                        <i class="fa-solid fa-user-plus"></i> Buatkan
                                    </button>
                                </form>
                            @else
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @if($s->orangTua->password_diubah_at)
                                        <span class="badge bg-emerald-50 text-emerald-700"
                                              title="Password sudah diganti sendiri oleh orang tua">
                                            <i class="fa-solid fa-circle-check mr-1"></i> Aktif
                                        </span>
                                    @else
                                        <span class="badge bg-amber-50 text-amber-700"
                                              title="Masih memakai password default &quot;{{ \App\Models\OrangTua::PASSWORD_DEFAULT }}&quot; — belum pernah diganti orang tua">
                                            Password default
                                        </span>
                                    @endif
                                    <form method="POST" action="{{ route('akun-ortu.reset-password', $s->orangTua) }}" class="inline-block"
                                          onsubmit="return confirm('Reset password akun orang tua {{ $s->nama }} ke &quot;{{ \App\Models\OrangTua::PASSWORD_DEFAULT }}&quot;?')">
                                        @csrf
                                        <button class="btn-chip btn-chip-cancel btn-chip-icon" title="Reset password ke default">
                                            <i class="fa-solid fa-key"></i>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($s->is_active)<span class="badge bg-emerald-50 text-emerald-700">Aktif</span>
                            @else<span class="badge bg-slate-100 text-slate-500">Nonaktif</span>@endif
                        </td>
                        <td class="td-aksi">
                            <div class="action-buttons">
                                <a href="{{ route('siswa.riwayat-kelas', $s) }}" class="btn-chip btn-chip-cancel"><i class="fa-solid fa-clock-rotate-left mr-1.5"></i> Riwayat Kelas</a>
                                <button type="button" @click="pindah = true" class="btn-chip btn-chip-cancel"><i class="fa-solid fa-right-left mr-1.5"></i> Pindah Kelas</button>
                                <button type="button" @click="editing = true" class="btn-chip btn-chip-edit"><i class="fa-solid fa-pen mr-1.5"></i> Edit</button>
                                <form method="POST" action="{{ route('siswa.destroy', $s) }}" onsubmit="return confirm('Hapus siswa ini?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-chip btn-chip-delete"><i class="fa-solid fa-trash mr-1.5"></i> Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr x-show="editing" x-cloak>
                        <td colspan="9" class="bg-brand-50/40">
                            <form method="POST" action="{{ route('siswa.update', $s) }}" class="grid sm:grid-cols-7 gap-3 items-end py-2">
                                @csrf @method('PUT')
                                <input type="text" name="nis" value="{{ $s->nis }}" placeholder="NIS" required class="input">
                                <input type="text" name="nisn" value="{{ $s->nisn }}" placeholder="NISN" class="input">
                                <input type="text" name="nama" value="{{ $s->nama }}" placeholder="Nama Lengkap" required class="input sm:col-span-2">
                                <select name="jenis_kelamin" required class="input">
                                    <option value="L" {{ $s->jenis_kelamin === 'L' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="P" {{ $s->jenis_kelamin === 'P' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                                <select name="kelas_id" required class="input">
                                    @foreach($kelasList as $k)
                                        <option value="{{ $k->id }}" {{ optional($s->kelas)->id == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                                    @endforeach
                                </select>
                                <label class="flex items-center gap-1.5 text-xs text-slate-600 font-semibold">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" {{ $s->is_active ? 'checked' : '' }} class="rounded">
                                    Aktif
                                </label>
                                <input type="text" name="nama_ortu" value="{{ $s->nama_ortu }}" placeholder="Nama Orang Tua/Wali" class="input sm:col-span-2">
                                <input type="text" name="no_wa_ortu" value="{{ $s->no_wa_ortu }}" placeholder="No. WhatsApp Ortu" class="input">
                                <div class="flex gap-2 sm:col-span-4">
                                    <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                                    <button type="button" @click="editing = false" class="btn-outline h-[38px]">Batal</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    <tr x-show="pindah" x-cloak>
                        <td colspan="9" class="bg-amber-50/50">
                            <form method="POST" action="{{ route('siswa.pindah-kelas', $s) }}" class="grid sm:grid-cols-6 gap-3 items-end py-2">
                                @csrf
                                <div class="sm:col-span-6 text-xs text-slate-500">
                                    Pindahkan <span class="font-semibold text-slate-700">{{ $s->nama }}</span> dari kelas
                                    <span class="font-semibold text-slate-700">{{ $s->kelas->nama_kelas }}</span> ke kelas baru.
                                    Data absensi &amp; jurnal bulan-bulan sebelumnya tetap tersimpan di riwayat kelas lama.
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-500">Kelas Tujuan</label>
                                    <select name="kelas_tujuan_id" required class="input">
                                        <option value="">Pilih Kelas</option>
                                        @foreach($kelasList as $k)
                                            @if($k->id != optional($s->kelas)->id)
                                                <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-500">Tanggal Efektif Pindah</label>
                                    <input type="date" name="tanggal_mutasi" value="{{ now()->toDateString() }}" class="input">
                                </div>
                                <input type="text" name="keterangan" placeholder="Keterangan (opsional), mis. alasan pindah" class="input sm:col-span-2">
                                <div class="flex gap-2 sm:col-span-2">
                                    <button type="submit" class="btn-primary h-[38px]">Pindahkan</button>
                                    <button type="button" @click="pindah = false" class="btn-outline h-[38px]">Batal</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                </tbody>
                @empty
                <tbody>
                    <tr><td colspan="9" class="text-center text-slate-400 py-8">Belum ada data siswa.</td></tr>
                </tbody>
                @endforelse
            </table>
        </div>
        <div class="mt-4">{{ $siswas->links() }}</div>
    </div>
</div>
@endsection
