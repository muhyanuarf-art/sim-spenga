@extends('layouts.app')
@section('title', 'Prestasi Siswa')

@section('content')
{{--
    HALAMAN PRESTASI SISWA.

    Dirancang supaya mencatat lebih ringan daripada menunda. Formulirnya
    ada di halaman yang sama dengan daftarnya — tidak ada halaman "tambah"
    terpisah — dan hanya empat isian yang wajib: siswa, nama lomba,
    peringkat, tanggal. Sisanya boleh menyusul.
--}}
<div class="space-y-6" x-data="{ showForm: false }">

    {{-- ================= Ringkasan ================= --}}
    <div class="grid sm:grid-cols-3 gap-4 no-print">
        <div class="card p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Total Tercatat</p>
            <p class="text-2xl font-extrabold text-slate-800 mt-1">{{ $ringkasan['total'] }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Tahun Ajaran Ini</p>
            <p class="text-2xl font-extrabold text-brand-600 mt-1">{{ $ringkasan['tahun_ini'] }}</p>
            <p class="text-[11px] text-slate-400 mt-0.5">{{ $periode?->nama ?? 'Belum ada periode aktif' }}</p>
        </div>
        <div class="card p-4">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Menunggu Verifikasi</p>
            <p class="text-2xl font-extrabold {{ $ringkasan['belum'] > 0 ? 'text-amber-600' : 'text-slate-800' }} mt-1">
                {{ $ringkasan['belum'] }}
            </p>
            @if($ringkasan['belum'] > 0 && $bolehKelola)
                <p class="text-[11px] text-amber-600 mt-0.5">Perlu diperiksa Kesiswaan.</p>
            @endif
        </div>
    </div>

    {{-- ================= Penyaring & tombol tambah ================= --}}
    <div class="flex items-end justify-between flex-wrap gap-3 no-print">
        <form method="GET" class="flex flex-wrap gap-2 items-end">
            <input type="text" name="cari" value="{{ request('cari') }}"
                   placeholder="Cari nama siswa / lomba…" class="input max-w-xs">

            @if($daftarKelas->count() > 1)
                <select name="kelas_id" class="input" onchange="this.form.submit()">
                    <option value="">Semua Kelas</option>
                    @foreach($daftarKelas as $k)
                        <option value="{{ $k->id }}" @selected(request('kelas_id') == $k->id)>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            @endif

            <select name="bidang" class="input" onchange="this.form.submit()">
                <option value="">Semua Bidang</option>
                @foreach(\App\Models\PrestasiSiswa::BIDANG as $kode => $label)
                    <option value="{{ $kode }}" @selected(request('bidang') === $kode)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="tingkat" class="input" onchange="this.form.submit()">
                <option value="">Semua Tingkat</option>
                @foreach(\App\Models\PrestasiSiswa::TINGKAT as $kode => $label)
                    <option value="{{ $kode }}" @selected(request('tingkat') === $kode)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="status" class="input" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="belum" @selected(request('status') === 'belum')>Belum diverifikasi</option>
                <option value="sudah" @selected(request('status') === 'sudah')>Sudah diverifikasi</option>
            </select>

            <button class="btn-outline">Cari</button>
        </form>

        @if($bolehCatat)
            <button @click="showForm = !showForm" class="btn-primary">
                <i class="fa-solid fa-trophy"></i> Catat Prestasi
            </button>
        @endif
    </div>

    {{-- ================= Formulir pencatatan ================= --}}
    @if($bolehCatat)
        <div class="card p-5 no-print" x-show="showForm" x-cloak x-transition>
            <p class="font-bold text-slate-800">Catat Prestasi Baru</p>
            <p class="text-sm text-slate-400 mb-4">
                Cukup isi siswa, nama lomba, peringkat, dan tanggalnya. Sertifikat boleh menyusul.
            </p>

            <form method="POST" action="{{ route('prestasi.store') }}" enctype="multipart/form-data"
                  class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 items-end">
                @csrf

                <div>
                    <label class="label">Siswa</label>
                    <select name="siswa_id" required class="select">
                        <option value="">— pilih siswa —</option>
                        @foreach($siswaPilihan as $s)
                            <option value="{{ $s->id }}">{{ $s->nama }} — {{ $s->kelas?->nama_kelas ?? 'tanpa kelas' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label class="label">Nama Prestasi / Lomba</label>
                    <input type="text" name="nama" required class="input"
                           placeholder="contoh: Olimpiade Sains Nasional Bidang IPA">
                </div>

                <div>
                    <label class="label">Bidang</label>
                    <select name="bidang" class="select">
                        @foreach(\App\Models\PrestasiSiswa::BIDANG as $kode => $label)
                            <option value="{{ $kode }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label">Tingkat</label>
                    <select name="tingkat" class="select">
                        @foreach(\App\Models\PrestasiSiswa::TINGKAT as $kode => $label)
                            <option value="{{ $kode }}" @selected($kode === 'kabupaten')>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label">Peringkat</label>
                    <select name="peringkat" class="select">
                        @foreach(\App\Models\PrestasiSiswa::PERINGKAT as $kode => $label)
                            <option value="{{ $kode }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label">Tanggal</label>
                    <input type="date" name="tanggal" required class="input" value="{{ now()->toDateString() }}">
                </div>

                <div>
                    <label class="label">Penyelenggara <span class="text-slate-300">(opsional)</span></label>
                    <input type="text" name="penyelenggara" class="input" placeholder="contoh: Dinas Pendidikan Kab. Brebes">
                </div>

                <div>
                    <label class="label">Sertifikat <span class="text-slate-300">(opsional)</span></label>
                    <input type="file" name="sertifikat" accept=".jpg,.jpeg,.png,.pdf" class="input py-1.5">
                </div>

                <div class="sm:col-span-2 lg:col-span-2">
                    <label class="label">Keterangan <span class="text-slate-300">(opsional)</span></label>
                    <input type="text" name="keterangan" class="input" placeholder="catatan tambahan bila perlu">
                </div>

                <button type="submit" class="btn-primary h-[38px]">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan
                </button>
            </form>
        </div>
    @endif

    {{-- ================= Daftar ================= --}}
    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead>
                    <tr>
                        <th class="w-12 text-center">No</th>
                        <th>Siswa</th>
                        <th>Prestasi</th>
                        <th>Peringkat &amp; Tingkat</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th class="th-aksi no-print">Aksi</th>
                    </tr>
                </thead>

                @forelse($daftar as $p)
                    @php
                        // Wali kelas kehilangan hak ubah begitu catatannya
                        // diverifikasi — dihitung sekali di sini supaya
                        // tombol yang tampil selalu sama dengan yang
                        // benar-benar diizinkan server.
                        $bolehUbahBaris = $bolehKelola || ! $p->sudahDiverifikasi();
                    @endphp
                    <tbody x-data="{ editing: false }">
                        <tr x-show="!editing">
                            <td class="text-center text-slate-400">{{ $daftar->firstItem() + $loop->index }}</td>
                            <td>
                                <p class="font-medium text-slate-800">{{ $p->siswa->nama }}</p>
                                <p class="text-xs text-slate-400">
                                    {{ $p->siswa->kelas?->nama_kelas ?? '—' }} · NIS {{ $p->siswa->nis }}
                                </p>
                            </td>
                            <td>
                                <p class="font-medium">{{ $p->nama }}</p>
                                <p class="text-xs text-slate-400">
                                    {{ $p->labelBidang() }}@if($p->penyelenggara) · {{ $p->penyelenggara }}@endif
                                </p>
                                @if($p->keterangan)
                                    <p class="text-xs text-slate-400 mt-0.5">{{ $p->keterangan }}</p>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-brand-50 text-brand-700">{{ $p->labelPeringkat() }}</span>
                                <p class="text-xs text-slate-500 mt-1">{{ $p->labelTingkat() }}</p>
                            </td>
                            <td class="whitespace-nowrap">
                                {{ $p->tanggal->translatedFormat('d M Y') }}
                                <p class="text-xs text-slate-400">{{ $p->tahunAjaran?->nama ?? '—' }}</p>
                            </td>
                            <td>
                                @if($p->sudahDiverifikasi())
                                    <span class="badge bg-emerald-50 text-emerald-700">
                                        <i class="fa-solid fa-circle-check mr-1"></i> Terverifikasi
                                    </span>
                                    <p class="text-[11px] text-slate-400 mt-1">oleh {{ $p->verifikator?->name ?? '—' }}</p>
                                @else
                                    <span class="badge bg-amber-50 text-amber-700">
                                        <i class="fa-solid fa-clock mr-1"></i> Menunggu
                                    </span>
                                    <p class="text-[11px] text-slate-400 mt-1">dicatat {{ $p->pencatat?->name ?? '—' }}</p>
                                @endif

                                @if($p->sertifikat_path)
                                    <a href="{{ route('berkas.lihat', ['path' => $p->sertifikat_path]) }}" target="_blank"
                                       class="text-[11px] text-brand-600 hover:underline inline-block mt-1">
                                        <i class="fa-solid fa-paperclip"></i> Lihat sertifikat
                                    </a>
                                @endif
                            </td>
                            <td class="td-aksi no-print">
                                <div class="action-buttons">
                                    @if($bolehKelola)
                                        <form method="POST" action="{{ route('prestasi.verifikasi', $p) }}">
                                            @csrf
                                            <button class="btn-chip {{ $p->sudahDiverifikasi() ? 'btn-chip-cancel' : 'btn-chip-success' }}">
                                                <i class="fa-solid {{ $p->sudahDiverifikasi() ? 'fa-rotate-left' : 'fa-check' }} mr-1.5"></i>
                                                {{ $p->sudahDiverifikasi() ? 'Batal Verifikasi' : 'Verifikasi' }}
                                            </button>
                                        </form>
                                    @endif

                                    @if($bolehUbahBaris)
                                        <button type="button" @click="editing = true" class="btn-chip btn-chip-edit">
                                            <i class="fa-solid fa-pen mr-1.5"></i> Edit
                                        </button>
                                        <form method="POST" action="{{ route('prestasi.destroy', $p) }}"
                                              onsubmit="return confirm('Hapus catatan prestasi ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn-chip btn-chip-delete">
                                                <i class="fa-solid fa-trash mr-1.5"></i> Hapus
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[11px] text-slate-400">
                                            <i class="fa-solid fa-lock"></i> Terkunci
                                        </span>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        @if($bolehUbahBaris)
                            <tr x-show="editing" x-cloak>
                                <td colspan="7" class="bg-brand-50/40">
                                    <form method="POST" action="{{ route('prestasi.update', $p) }}"
                                          enctype="multipart/form-data"
                                          class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 items-end py-2">
                                        @csrf @method('PUT')

                                        <select name="siswa_id" required class="select">
                                            @foreach($siswaPilihan as $s)
                                                <option value="{{ $s->id }}" @selected($s->id === $p->siswa_id)>
                                                    {{ $s->nama }} — {{ $s->kelas?->nama_kelas ?? 'tanpa kelas' }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <input type="text" name="nama" value="{{ $p->nama }}" required class="input lg:col-span-2">

                                        <select name="bidang" class="select">
                                            @foreach(\App\Models\PrestasiSiswa::BIDANG as $kode => $label)
                                                <option value="{{ $kode }}" @selected($p->bidang === $kode)>{{ $label }}</option>
                                            @endforeach
                                        </select>

                                        <select name="tingkat" class="select">
                                            @foreach(\App\Models\PrestasiSiswa::TINGKAT as $kode => $label)
                                                <option value="{{ $kode }}" @selected($p->tingkat === $kode)>{{ $label }}</option>
                                            @endforeach
                                        </select>

                                        <select name="peringkat" class="select">
                                            @foreach(\App\Models\PrestasiSiswa::PERINGKAT as $kode => $label)
                                                <option value="{{ $kode }}" @selected($p->peringkat === $kode)>{{ $label }}</option>
                                            @endforeach
                                        </select>

                                        <input type="date" name="tanggal" value="{{ $p->tanggal->toDateString() }}" required class="input">
                                        <input type="text" name="penyelenggara" value="{{ $p->penyelenggara }}" placeholder="Penyelenggara" class="input">
                                        <input type="text" name="keterangan" value="{{ $p->keterangan }}" placeholder="Keterangan" class="input">

                                        <div>
                                            <label class="label">
                                                Ganti sertifikat
                                                @if($p->sertifikat_path)<span class="text-slate-300">(sudah ada)</span>@endif
                                            </label>
                                            <input type="file" name="sertifikat" accept=".jpg,.jpeg,.png,.pdf" class="input py-1.5">
                                        </div>

                                        <div class="flex gap-2">
                                            <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                                            <button type="button" @click="editing = false" class="btn-outline h-[38px]">Batal</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="7" class="text-center py-10 text-slate-400">
                                <i class="fa-solid fa-trophy text-3xl text-slate-200"></i>
                                <p class="mt-3 font-medium">Belum ada prestasi tercatat.</p>
                                @if($bolehCatat)
                                    <p class="text-sm">Tekan <span class="font-semibold">Catat Prestasi</span> di atas untuk menambahkan yang pertama.</p>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        <div class="mt-4 no-print">{{ $daftar->links() }}</div>
    </div>
</div>
@endsection
