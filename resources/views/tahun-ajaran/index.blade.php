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
                  data-konfirmasi="Buat Tahun Ajaran {{ $namaTahunAjaranBerikutnya }} (Semester Ganjil & Genap)?">
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
                <thead><tr><th class="w-12 text-center">No</th><th>Tahun Ajaran</th><th>Semester</th><th>Rentang Tanggal</th><th>Status</th><th>Kunci</th><th class="th-aksi">Aksi</th></tr></thead>
                @forelse($tahunAjaran as $t)
                <tbody x-data="{ editing: false, salin: false }">
                    <tr x-show="!editing && !salin">
                        <td class="text-center text-slate-400">{{ $loop->iteration }}</td>
                        <td class="font-semibold">{{ $t->nama }}</td>
                        <td>{{ $t->semester }}</td>
                        {{-- Rentang tanggal yang BERLAKU: batas tanggal yang boleh
                             disimpan pada periode ini, sekaligus batas data yang
                             masuk Laporan Akhir Semester. --}}
                        <td class="text-xs">
                            @php
                                $rentang = \App\Support\RentangPeriode::untuk($t);
                            @endphp
                            @if($rentang)
                                <div class="text-slate-600">{{ \App\Support\RentangPeriode::label($t) }}</div>
                                @if($rentang[2])
                                    <span class="text-amber-600" title="Belum diisi admin — diturunkan otomatis dari nama tahun ajaran & semester. Isi lewat tombol Edit bila kalender sekolah berbeda."><i class="fa-solid fa-wand-magic-sparkles mr-1"></i> otomatis</span>
                                @endif
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $t->statusBadgeClass() }}">{{ $t->statusLabel() }}</span>
                        </td>
                        <td>
                            @if($t->isTerkunci())
                                <span class="badge bg-red-50 text-red-700" title="Ditutup {{ optional($t->terkunci_at)->translatedFormat('d M Y H:i') }} oleh {{ $t->terkunciOleh->name ?? '-' }}"><i class="fa-solid fa-lock mr-1.5"></i> Terkunci</span>
                            @elseif($t->dibuka_at)
                                <span class="badge bg-slate-100 text-slate-500" title="Dibuka kembali {{ $t->dibuka_at->translatedFormat('d M Y H:i') }} oleh {{ $t->dibukaOleh->name ?? '-' }}"><i class="fa-solid fa-lock-open mr-1.5"></i> Terbuka</span>
                            @else
                                <span class="badge bg-slate-100 text-slate-500"><i class="fa-solid fa-lock-open mr-1.5"></i> Terbuka</span>
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
                                <button type="button" @click="salin = true" class="btn-chip btn-chip-edit"><i class="fa-solid fa-clipboard-list mr-1.5"></i> Salin Data</button>
                                <button type="button" @click="editing = true" class="btn-chip btn-chip-edit"><i class="fa-solid fa-pen mr-1.5"></i> Edit</button>
                                @unless($t->is_active)
                                @php
                                    $adaPeriodeAktifLain = $periodeAktif && $periodeAktif->id !== $t->id;
                                    $periodeAktifBelumTerkunci = $adaPeriodeAktifLain && ! $periodeAktif->isTerkunci();
                                @endphp
                                <form method="POST" action="{{ route('tahun-ajaran.aktifkan', $t) }}"
                                    @if($periodeAktifBelumTerkunci)
                                        data-konfirmasi="PERHATIAN: {{ $periodeAktif->labelPeriode() }} masih AKTIF dan BELUM DIKUNCI.\nMengaktifkan {{ $t->labelPeriode() }} akan memindahkan status periode aktif sekolah, tapi data {{ $periodeAktif->labelPeriode() }} (jurnal, absensi, jadwal, dll) tetap bisa diubah sampai Anda menutup/menguncinya sendiri lewat tombol 'Tutup Semester'.\nYakin ingin mengaktifkan {{ $t->labelPeriode() }} sekarang?"
                                    @else
                                        data-konfirmasi="Aktifkan {{ $t->labelPeriode() }} sebagai periode aktif sekolah?"
                                    @endif
                                >
                                    @csrf
                                    <button class="btn-chip btn-chip-success"><i class="fa-solid fa-circle-check mr-1.5"></i> Aktifkan</button>
                                </form>
                                @endunless
                                @if($t->isTerkunci())
                                    @if(auth()->user()->isAdmin())
                                    <form method="POST" action="{{ route('tahun-ajaran.buka-kunci', $t) }}" data-konfirmasi="Semester {{ $t->semester }} ini sudah terkunci.\nMembuka kembali akan memungkinkan perubahan data historis.\nLanjutkan?">
                                        @csrf
                                        <button class="btn-chip btn-chip-success"><i class="fa-solid fa-lock-open mr-1.5"></i> Buka Kembali</button>
                                    </form>
                                    @endif
                                @else
                                <form method="POST" action="{{ route('tahun-ajaran.kunci', $t) }}" data-konfirmasi="Semester {{ $t->semester }} akan ditutup.\nSetelah ditutup, SELURUH data pada semester ini (jurnal, absensi, jadwal, guru mengajar, BK, dst) tidak dapat diubah oleh pengguna biasa — tapi tetap bisa dilihat & dijadikan sumber Salin Data.\nAnda yakin ingin melanjutkan?">
                                    @csrf
                                    <button class="btn-chip btn-chip-cancel"><i class="fa-solid fa-lock mr-1.5"></i> Tutup Semester</button>
                                </form>
                                @endif
                                {{-- ===== ARSIP SEMESTER =====
                                     Dibuat MANUAL, tidak otomatis saat semester ditutup.
                                     Admin sering menutup lalu membuka lagi untuk koreksi;
                                     arsip otomatis di tiap penutupan hanya menghasilkan
                                     tumpukan berkas setengah jadi, dan yang paling
                                     berbahaya, Admin mengira yang pertama itu final. --}}
                                @if(auth()->user()->isAdmin())
                                    @php $arsip = $t->arsipTerbaru; @endphp

                                    @if($arsip?->sedangDikerjakan())
                                        {{-- Batang kemajuan. Yang mengerjakan arsip adalah
                                             pekerja antrian — proses terpisah dari peramban
                                             ini — jadi keadaannya ditanyakan berkala ke
                                             server. Lihat resources/js/arsip-progres.js. --}}
                                        <div class="w-56 text-left"
                                             x-data="arsipProgres({{ $arsip->id }}, {{ (int) $arsip->progres }}, @js($arsip->langkah))"
                                             x-init="mulai()">

                                            <template x-if="!selesai && !gagal">
                                                <div>
                                                    <div class="flex items-center justify-between mb-1">
                                                        <span class="text-xs font-semibold text-slate-600">
                                                            <i class="fa-solid fa-gear fa-spin mr-1"></i> Membuat arsip
                                                        </span>
                                                        <span class="text-xs font-bold text-brand-600" x-text="persen + '%'"></span>
                                                    </div>

                                                    <div class="h-2.5 w-full rounded-full bg-slate-200 overflow-hidden">
                                                        <div class="h-full rounded-full bg-brand-600 transition-all duration-500"
                                                             :style="`width:${persen}%`"></div>
                                                    </div>

                                                    <p class="text-[11px] text-slate-500 mt-1 truncate" x-text="langkah"></p>
                                                </div>
                                            </template>

                                            <template x-if="selesai">
                                                <div class="text-left">
                                                    <p class="text-sm font-bold text-emerald-600 mb-1.5">
                                                        <i class="fa-solid fa-circle-check mr-1"></i> Silakan Unduh Arsip
                                                    </p>
                                                    <a :href="urlUnduh" class="btn-chip btn-chip-success">
                                                        <i class="fa-solid fa-file-zipper mr-1.5"></i> Unduh Arsip
                                                    </a>
                                                </div>
                                            </template>

                                            <template x-if="gagal">
                                                <p class="text-xs text-rose-600" x-text="'Gagal: ' + gagal"></p>
                                            </template>
                                        </div>
                                    @else
                                        @if($arsip?->bisaDiunduh())
                                            <a href="{{ route('arsip.unduh', $arsip) }}"
                                               class="btn-chip {{ $arsip->status === 'kedaluwarsa' ? 'btn-chip-reset' : 'btn-chip-success' }}"
                                               title="{{ $arsip->keterangan() }} · {{ $arsip->ukuranTerbaca() }}">
                                                <i class="fa-solid fa-file-zipper mr-1.5"></i>
                                                {{ $arsip->status === 'kedaluwarsa' ? 'Unduh Arsip (lama)' : 'Unduh Arsip' }}
                                            </a>
                                        @endif

                                        <form method="POST" action="{{ route('arsip.buat', $t) }}"
                                              {{-- Pemisah paragraf ditulis sebagai "\n" biasa, BUKAN entitas
                                                   HTML &#10;. Di dalam {{ }} Blade meloloskan tanda "&"
                                                   menjadi "&amp;", sehingga entitasnya justru tampil apa
                                                   adanya sebagai tulisan di layar. --}}
                                              data-konfirmasi="{{ $arsip
                                                  ? 'Buat ulang arsip '.$t->labelPeriode()."?\n\nArsip yang lama akan tetap tersimpan sampai Anda menghapusnya."
                                                  : 'Buat arsip '.$t->labelPeriode()."?\n\nSeluruh laporan semester ini akan dijadikan satu berkas ZIP berisi PDF. Prosesnya berjalan di latar belakang." }}"
                                              data-konfirmasi-judul="Arsip Semester"
                                              data-konfirmasi-gaya="biasa"
                                              data-konfirmasi-lanjut="Ya, Buat">
                                            @csrf
                                            <button class="btn-chip btn-chip-edit">
                                                <i class="fa-solid fa-box-archive mr-1.5"></i>
                                                {{ $arsip ? 'Buat Ulang Arsip' : 'Buat Arsip' }}
                                            </button>
                                        </form>
                                    @endif
                                @endif

                                @unless($t->isTerkunci())
                                <form method="POST" action="{{ route('tahun-ajaran.destroy', $t) }}" data-konfirmasi="Hapus tahun ajaran ini?">
                                    @csrf @method('DELETE')
                                    <button class="btn-chip btn-chip-delete"><i class="fa-solid fa-trash mr-1.5"></i> Hapus</button>
                                </form>
                                @endunless
                            </div>
                        </td>
                    </tr>

                    {{-- poin 6: Salin Data — pilih tujuan, lalu ke halaman Preview (checklist) sebelum benar-benar menyalin --}}
                    <tr x-show="salin" x-cloak>
                        <td colspan="7" class="bg-brand-50/40">
                            <form method="GET" action="{{ route('tahun-ajaran.duplikasi.preview') }}" class="grid sm:grid-cols-3 gap-3 items-end py-2"
                                  data-konfirmasi="Anda akan menyalin data dari {{ $t->nama }} - Semester {{ $t->semester }} ke periode tujuan yang dipilih. Lanjutkan?">
                                <input type="hidden" name="dari_tahun_ajaran_id" value="{{ $t->id }}">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-500 mb-1">
                                        Salin Mata Pelajaran, Jam Pelajaran, Jenis Pelanggaran, Jenis Surat, Ekstrakurikuler,
                                        Kelas &amp; Wali Kelas, Guru Mengajar, Guru BK &amp; Jadwal dari {{ $t->nama }} - Semester {{ $t->semester }} ke:
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
                        <td colspan="7" class="bg-brand-50/40">
                            <form method="POST" action="{{ route('tahun-ajaran.update', $t) }}" class="grid sm:grid-cols-3 gap-3 items-end py-2">
                                @csrf @method('PUT')
                                <div>
                                    <label class="label">Tahun Ajaran</label>
                                    <input type="text" name="nama" value="{{ $t->nama }}" required class="input">
                                </div>
                                <div>
                                    <label class="label">Semester</label>
                                    <select name="semester" required class="input">
                                        <option value="Ganjil" {{ $t->semester === 'Ganjil' ? 'selected' : '' }}>Ganjil</option>
                                        <option value="Genap" {{ $t->semester === 'Genap' ? 'selected' : '' }}>Genap</option>
                                    </select>
                                </div>
                                @unless($t->is_active)
                                <div>
                                    <label class="label">Status</label>
                                    <select name="status" class="input">
                                        <option value="akan_datang" {{ $t->status === 'akan_datang' ? 'selected' : '' }}>Akan Datang</option>
                                        <option value="selesai" {{ $t->status === 'selesai' ? 'selected' : '' }}>Selesai</option>
                                    </select>
                                </div>
                                @endunless
                                <div>
                                    <label class="label">Tanggal Mulai <span class="font-normal text-slate-400">(opsional)</span></label>
                                    <input type="date" name="tanggal_mulai" value="{{ optional($t->tanggal_mulai)->format('Y-m-d') }}" class="input">
                                </div>
                                <div>
                                    <label class="label">Tanggal Selesai <span class="font-normal text-slate-400">(opsional)</span></label>
                                    <input type="date" name="tanggal_selesai" value="{{ optional($t->tanggal_selesai)->format('Y-m-d') }}" class="input">
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                                    <button type="button" @click="editing = false" class="btn-outline h-[38px]">Batal</button>
                                </div>
                                <p class="sm:col-span-3 -mt-1 text-xs text-slate-500">
                                    <i class="fa-solid fa-circle-info mr-1"></i>
                                    Kedua tanggal ini menentukan tanggal yang boleh disimpan pada periode ini
                                    (jurnal, absensi, BK, surat, kegiatan) sekaligus batas data pada Laporan Akhir Semester.
                                    <strong>Kosongkan saja</strong> bila kalender sekolah mengikuti pola umum —
                                    sistem otomatis memakai Juli–Desember untuk Ganjil dan Januari–Juni untuk Genap.
                                </p>
                            </form>
                        </td>
                    </tr>
                </tbody>
                @empty
                <tbody>
                    <tr><td colspan="7" class="text-center text-slate-400 py-8">Belum ada data.</td></tr>
                </tbody>
                @endforelse
            </table>
        </div>
    </div>

    {{-- Panduan penggunaan halaman ini — diletakkan di bagian paling bawah,
         tertutup secara default supaya tidak mengganggu admin yang sudah
         terbiasa, tapi selalu ada untuk dibuka kapan saja butuh. --}}
    <div class="card p-5" x-data="{ showBantuan: false }">
        <button type="button" @click="showBantuan = !showBantuan" class="w-full flex items-center justify-between text-left">
            <span class="font-bold text-slate-800"><i class="fa-solid fa-circle-info mr-1.5"></i> Panduan Penggunaan Halaman Tahun Ajaran</span>
            <span class="text-slate-400" x-text="showBantuan ? '▲ Tutup' : '▼ Buka'"></span>
        </button>

        <div x-show="showBantuan" x-cloak x-transition class="mt-5 space-y-6">

            <div>
                <p class="font-semibold text-slate-700 mb-2">Fungsi Setiap Tombol</p>
                <div class="overflow-x-auto -mx-5">
                    <table class="table-clean w-full text-sm">
                        <thead><tr><th>Tombol</th><th>Fungsi</th></tr></thead>
                        <tbody>
                            <tr>
                                <td class="font-semibold whitespace-nowrap">+ Tambah Tahun Ajaran</td>
                                <td>Membuat SATU baris tahun ajaran + semester (misalnya cuma Semester Ganjil saja). Dipakai untuk menambah data secara manual, termasuk melengkapi semester yang kurang.</td>
                            </tr>
                            <tr>
                                <td class="font-semibold whitespace-nowrap">+ Buat Tahun Ajaran [nama]</td>
                                <td>Tombol cepat yang HANYA muncul kalau tahun ajaran berikutnya (dihitung otomatis dari periode aktif) belum ada. Sekali klik langsung membuat Semester Ganjil DAN Genap sekaligus, berstatus Akan Datang.</td>
                            </tr>
                            <tr>
                                <td class="font-semibold whitespace-nowrap">+ Tambah Semester [Ganjil/Genap]</td>
                                <td>Muncul di baris tahun ajaran yang baru punya 1 semester. Klik untuk langsung membuat semester yang masih kurang.</td>
                            </tr>
                            <tr>
                                <td class="font-semibold whitespace-nowrap"><i class="fa-solid fa-pen mr-1.5"></i> Edit</td>
                                <td>Mengubah nama tahun ajaran, semester, status (Akan Datang/Selesai), serta Tanggal Mulai &amp; Tanggal Selesai periode. Kedua tanggal itu OPSIONAL — kalau dikosongkan sistem memakai Juli–Desember (Ganjil) dan Januari–Juni (Genap). Tidak bisa dipakai untuk mengaktifkan — pakai tombol Aktifkan.</td>
                            </tr>
                            <tr>
                                <td class="font-semibold whitespace-nowrap"><i class="fa-solid fa-clipboard-list mr-1.5"></i> Salin Data</td>
                                <td>Menyalin SELURUH pengaturan periode ini ke periode lain: Mata Pelajaran, Jam Pelajaran, Jenis Pelanggaran, Jenis Surat, Ekstrakurikuler (beserta pembinanya), Kelas &amp; Wali Kelas, Guru Mengajar, Guru BK, dan Jadwal. Menampilkan halaman Preview (daftar lengkap apa yang akan disalin) sebelum benar-benar tersimpan. Data siswa &amp; seluruh data transaksi TIDAK ikut disalin.</td>
                            </tr>
                            <tr>
                                <td class="font-semibold whitespace-nowrap"><i class="fa-solid fa-circle-check mr-1.5"></i> Aktifkan</td>
                                <td>Menjadikan semester ini sebagai Periode Aktif sistem. Semester yang tadinya aktif otomatis berhenti aktif (statusnya jadi Selesai). Kalau target beda Tahun Ajaran, sistem akan MENOLAK jika Tahun Ajaran lama belum ditutup penuh.</td>
                            </tr>
                            <tr>
                                <td class="font-semibold whitespace-nowrap"><i class="fa-solid fa-lock mr-1.5"></i> Tutup Semester</td>
                                <td>Mengunci SEMUA data pada semester ini (jurnal, absensi, jadwal, guru mengajar, BK, dst) — tidak bisa diubah pengguna biasa lagi. Data tetap bisa dilihat & tetap bisa dijadikan sumber Salin Data.</td>
                            </tr>
                            <tr>
                                <td class="font-semibold whitespace-nowrap"><i class="fa-solid fa-lock-open mr-1.5"></i> Buka Kembali</td>
                                <td>KHUSUS ADMIN. Membuka kembali semester yang sudah terkunci supaya bisa diedit lagi. Dicatat siapa & kapan yang membuka.</td>
                            </tr>
                            <tr>
                                <td class="font-semibold whitespace-nowrap"><i class="fa-solid fa-trash mr-1.5"></i> Hapus</td>
                                <td>Menghapus baris tahun ajaran/semester. HANYA bisa dilakukan kalau semester itu Terbuka (belum dikunci) DAN benar-benar belum punya data apa pun — cukup satu nilai, satu prestasi, atau satu mata pelajaran yang terkait, penghapusan langsung ditolak dan pesannya menyebut apa saja yang masih memakainya. <strong>Tidak ada data yang bisa hilang lewat tombol ini.</strong> Gunanya hanya membatalkan tahun ajaran yang baru dibuat karena salah ketik; untuk mengakhiri semester yang sudah berjalan, yang benar adalah <strong>Tutup Semester</strong>.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <p class="font-semibold text-slate-700 mb-2">Langkah-Langkah: Pergantian Semester (Ganjil → Genap, Tahun Ajaran Sama)</p>
                <ol class="text-sm text-slate-600 list-decimal list-inside space-y-1">
                    <li>Pastikan Semester Genap sudah ada (kalau belum, klik "+ Tambah Semester Genap" di baris Semester Ganjil).</li>
                    <li>(Opsional, kalau Guru Mengajar/Jadwal Semester Genap belum ada dan memang sama dengan Semester Ganjil) Klik "<i class="fa-solid fa-clipboard-list mr-1.5"></i> Salin Data" pada baris Semester Ganjil → pilih tujuan Semester Genap → cek Preview → "Salin Sekarang".</li>
                    <li>Klik "<i class="fa-solid fa-lock mr-1.5"></i> Tutup Semester" pada baris Semester Ganjil → konfirmasi.</li>
                    <li>Klik "<i class="fa-solid fa-circle-check mr-1.5"></i> Aktifkan" pada baris Semester Genap.</li>
                </ol>
                <p class="text-xs text-slate-400 mt-2">
                    Selesai — Semester Genap jadi Periode Aktif. Data baru otomatis masuk ke Semester Genap, data Semester Ganjil tetap tersimpan & bisa dilihat kapan saja.
                </p>
            </div>

            <div>
                <p class="font-semibold text-slate-700 mb-2">Langkah-Langkah: Pergantian Tahun Ajaran (Akhir Tahun)</p>
                <ol class="text-sm text-slate-600 list-decimal list-inside space-y-1">
                    <li>Pastikan Semester Genap tahun ajaran LAMA sudah ditutup: klik "<i class="fa-solid fa-lock mr-1.5"></i> Tutup Semester" pada baris itu (kalau belum).</li>
                    <li>Buat Tahun Ajaran BARU: klik "+ Buat Tahun Ajaran [nama]" (otomatis membuat Semester Ganjil & Genap).</li>
                    <li>Klik "<i class="fa-solid fa-clipboard-list mr-1.5"></i> Salin Data" pada baris Semester GENAP tahun LAMA → pilih tujuan Semester GANJIL tahun BARU → cek halaman Preview → "Salin Sekarang". Seluruh master data (Mata Pelajaran, Jam Pelajaran, Jenis Pelanggaran, Jenis Surat, Ekstrakurikuler) ikut tersalin di langkah ini.</li>
                    <li>Klik "<i class="fa-solid fa-circle-check mr-1.5"></i> Aktifkan" pada Semester Ganjil tahun BARU. Mulai detik ini seluruh menu menampilkan data tahun baru.</li>
                    <li>Buka menu Data Kelas → sesuaikan Wali Kelas kalau ada pergantian. Periksa juga Guru Mengajar & Jadwal.</li>
                    <li>Import Excel Data Siswa dengan <span class="font-semibold">kode_kelas kelas barunya</span> (kelas 7 lama → 8, kelas 8 lama → 9, ditambah siswa baru kelas 7). Riwayat kelas tiap siswa otomatis tercatat.</li>
                    <li>Buka menu Ekstrakurikuler → isi kembali daftar anggotanya (pembinanya sudah ikut tersalin).</li>
                </ol>
                <p class="text-xs text-slate-500 mt-2">
                    <i class="fa-solid fa-circle-info mr-1.5"></i>
                    Siswa kelas 9 yang LULUS cukup tidak diikutkan pada file import di langkah 6 — karena setiap menu
                    hanya menampilkan siswa yang kelasnya milik periode aktif, mereka otomatis berhenti muncul.
                    Tidak perlu dinonaktifkan satu per satu, dan seluruh riwayatnya tetap tersimpan.
                </p>
                <p class="text-xs text-amber-600 mt-2">
                    <i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Kalau tombol Aktifkan ditolak: masih ada semester (Ganjil atau Genap) tahun lama yang belum di-"Tutup Semester". Tutup dulu semuanya, baru coba Aktifkan lagi.
                </p>
            </div>

            <div>
                <p class="font-semibold text-slate-700 mb-2">Aturan Penting</p>
                <ul class="text-sm text-slate-600 list-disc list-inside space-y-1">
                    <li>Hanya ada SATU periode aktif dalam satu waktu — dijaga otomatis oleh sistem.</li>
                    <li>Data yang sudah Terkunci hanya bisa dilihat, tidak bisa diubah oleh guru/wali kelas/BK.</li>
                    <li>Hanya Admin yang bisa "<i class="fa-solid fa-lock-open mr-1.5"></i> Buka Kembali" semester yang sudah terkunci.</li>
                    <li>Tahun Ajaran baru tidak bisa diaktifkan sebelum Tahun Ajaran lama ditutup penuh (Ganjil & Genap-nya sama-sama terkunci).</li>
                    <li>"<i class="fa-solid fa-clipboard-list mr-1.5"></i> Salin Data" aman dijalankan berulang kali — data yang sudah pernah tersalin tidak akan dobel.</li>
                </ul>
            </div>

        </div>
    </div>
</div>
@endsection
