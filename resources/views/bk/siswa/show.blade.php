@extends('layouts.app')
@section('title', 'Profil Perilaku Siswa')

@section('content')
@php
    $user = auth()->user();
    $bisaKelolaPoin = in_array($user->role, ['guru_bk', 'admin']);
    $tahapLabel = fn ($t) => $t ? "Tahap {$t}" : 'Belum ada';
@endphp
<div class="space-y-6">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <a href="{{ url()->previous() }}" class="text-sm text-slate-400 hover:text-slate-600">&larr; Kembali</a>
            <div class="flex items-center gap-3 mt-1">
                <x-initial-avatar :nama="$siswa->nama" size="w-12 h-12 text-lg" />
                <div>
                    <p class="text-xl font-extrabold text-slate-800">{{ $siswa->nama }}</p>
                    <div class="flex items-center gap-2 text-sm text-slate-400">
                        <span>{{ $siswa->nis }}</span> &middot; <x-kelas-badge :nama="$siswa->kelas->nama_kelas ?? '-'" />
                    </div>
                </div>
            </div>
        </div>
        {{-- Tombol pencatatan (Catat Pelanggaran / Pembinaan / Kurangi Poin /
             Panggil Ortu) SENGAJA tidak ada lagi di sini. Dulu halaman ini
             merangkap dua peran — tempat mencatat sekaligus tempat membaca
             riwayat — dan tombolnya juga muncul di menu lain, sehingga
             pengguna bingung harus mencatat dari mana.

             Sekarang halaman ini MURNI untuk membaca rekam jejak seorang
             siswa. Seluruh pencatatan berpangkal dari satu tempat: menu
             Buku Catatan BK. --}}
        <a href="{{ route('bk.kasus.index') }}" class="btn-outline">
            <i class="fa-solid fa-book mr-1.5"></i> Buku Catatan BK
        </a>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <x-stat-card color="rose" icon="fa-scale-balanced" label="Poin Aktif" :value="$ringkasan['poin_aktif']" />
        <x-stat-card color="violet" icon="fa-compass" label="Tahap Saat Ini" :value="$tahapLabel($ringkasan['tahap_saat_ini'])" />
        <x-stat-card color="amber" icon="fa-clipboard-list" label="Rekomendasi" :value="$ringkasan['rekomendasi_tahap'] ? 'Tahap '.$ringkasan['rekomendasi_tahap'] : 'Normal'" />
        <x-stat-card color="sky" icon="fa-folder-open" label="Jumlah Kasus" :value="$ringkasan['jumlah_kasus']" />
        <x-stat-card color="emerald" icon="fa-handshake" label="Pembinaan" :value="$ringkasan['jumlah_pembinaan']" />
    </div>

    @php
        $statusBoxClass = match ($ringkasan['status']) {
            'Normal' => 'bg-emerald-50 text-emerald-700 border border-emerald-100',
            'Dalam Pembinaan' => 'bg-amber-50 text-amber-700 border border-amber-100',
            'Selesai' => 'bg-teal-50 text-teal-700 border border-teal-100',
            default => 'bg-sky-50 text-sky-700 border border-sky-100', // Menunggu Pembinaan
        };
    @endphp
    <div class="rounded-xl px-4 py-3 text-sm {{ $statusBoxClass }}">
        Status: <b>{{ $ringkasan['status'] }}</b>
        <span class="text-xs opacity-70">
            (Total pelanggaran historis: +{{ $ringkasan['total_pelanggaran'] }}, total pengurangan: -{{ $ringkasan['total_pengurangan'] }})
        </span>
    </div>

    {{-- ===== RIWAYAT PERKEMBANGAN (tabel + pagination) =====
         Dulu berupa timeline kartu: satu kejadian = satu kartu besar, dan
         SELURUH riwayat dirender sekaligus (filter jenisnya hanya
         menyembunyikan di browser). Untuk siswa dengan riwayat panjang,
         halamannya jadi berat dan sulit dibaca sebagai laporan.

         Sekarang: tabel ringkas, filter diproses di server, dan
         dipaginasi — jadi yang dikirim ke browser hanya baris yang benar
         benar tampil. --}}
    <div class="card print-section" id="print-riwayat-siswa">
        {{-- KOP surat hanya muncul saat benar-benar dicetak (lihat .cetak-saja) --}}
        <x-kop-surat />
        <div class="cetak-saja px-5 pt-4">
            <p class="font-bold text-slate-800">RIWAYAT PERKEMBANGAN PERILAKU SISWA</p>
            <p class="text-sm">{{ $siswa->nama }} &middot; NIS {{ $siswa->nis }} &middot; Kelas {{ $siswa->kelas->nama_kelas ?? '-' }}</p>
        </div>

        <div class="flex items-start justify-between flex-wrap gap-3 px-5 py-4 border-b border-slate-100 no-print">
            <div>
                <p class="section-title">Riwayat Perkembangan</p>
                <p class="text-xs text-slate-400 mt-0.5">
                    Diurutkan kronologis dari catatan paling awal ke paling baru
                    &middot; {{ $jumlahPerJenis['semua'] }} catatan
                    @if($jenisFilter !== 'semua') &middot; disaring: {{ $timeline->total() }} catatan @endif
                </p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                {{-- Filter jenis (link biasa, diproses di server) --}}
                <div class="flex flex-wrap gap-1.5">
                    @foreach([
                        'semua' => [null, 'Semua'],
                        'kasus' => ['fa-folder-open', 'Kasus'],
                        'pembinaan' => ['fa-handshake', 'Pembinaan'],
                        'pengurangan' => ['fa-circle-check', 'Pengurangan'],
                        'pemanggilan' => ['fa-phone', 'Panggil Ortu'],
                    ] as $jenisKey => [$ikonFilter, $labelFilter])
                        @if($jumlahPerJenis[$jenisKey] > 0 || $jenisKey === 'semua')
                            <a href="{{ route('bk.siswa.show', [$siswa, 'jenis' => $jenisKey, 'per_page' => $perPage]) }}"
                               class="text-xs font-semibold px-3 py-1.5 rounded-full transition
                                    {{ $jenisFilter === $jenisKey ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                                @if($ikonFilter)<i class="fa-solid {{ $ikonFilter }}"></i>@endif
                                {{ $labelFilter }} ({{ $jumlahPerJenis[$jenisKey] }})
                            </a>
                        @endif
                    @endforeach
                </div>

                <button type="button" onclick="cetakBagian('print-riwayat-siswa')" class="btn-outline">
                    <i class="fa-solid fa-print"></i> Cetak
                </button>

                <form method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="jenis" value="{{ $jenisFilter }}">
                    <label class="text-xs text-slate-400">Tampil</label>
                    <select name="per_page" class="input py-1 w-auto text-xs" onchange="this.form.submit()">
                        @foreach([15, 30, 50, 100] as $n)
                            <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }} baris</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        @if($timeline->isEmpty())
            <p class="empty-state">
                @if($jenisFilter === 'semua')
                    Belum ada riwayat perkembangan untuk siswa ini.
                @else
                    Tidak ada catatan pada filter ini.
                @endif
            </p>
        @else
        <div class="overflow-x-auto">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th class="w-12 text-center">No</th>
                        <th class="whitespace-nowrap">Tanggal</th>
                        <th>Jenis</th>
                        <th class="min-w-[260px]">Uraian</th>
                        <th class="text-center whitespace-nowrap">Poin</th>
                        <th>Petugas</th>
                        <th>Status</th>
                        <th class="th-aksi no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($timeline as $item)
                    @php
                        $d = $item['data'];
                        [$ikon, $kelasBadge, $labelJenis] = match ($item['jenis']) {
                            'kasus' => ['fa-folder-open', 'bg-rose-50 text-rose-700', 'Kasus'],
                            'pembinaan' => ['fa-handshake', 'bg-violet-50 text-violet-700', 'Pembinaan'],
                            'pengurangan' => ['fa-circle-check', 'bg-emerald-50 text-emerald-700', 'Pengurangan'],
                            default => ['fa-phone', 'bg-sky-50 text-sky-700', 'Panggil Ortu'],
                        };
                        $dibatalkan = ! empty($d->dibatalkan_at);
                    @endphp
                    <tr class="{{ $dibatalkan ? 'opacity-60' : '' }}">
                        <td class="text-center text-slate-400">{{ $timeline->firstItem() + $loop->index }}</td>

                        <td class="whitespace-nowrap">
                            <span class="font-medium text-slate-700">{{ $item['tanggal']->translatedFormat('d M Y') }}</span>
                            <span class="block text-xs text-slate-400">{{ $item['tanggal']->diffForHumans() }}</span>
                        </td>

                        <td class="whitespace-nowrap">
                            <span class="badge {{ $kelasBadge }}"><i class="fa-solid {{ $ikon }} mr-1.5"></i>{{ $labelJenis }}</span>
                        </td>

                        <td>
                            @if($item['jenis'] === 'kasus')
                                <p class="font-semibold text-slate-800">{{ $d->nama_pelanggaran }}</p>
                                <p class="text-xs text-slate-400">Kategori {{ $d->kategori }}</p>
                                @if($d->bukti_catatan)<p class="text-xs text-slate-400 italic">{{ \Illuminate\Support\Str::limit($d->bukti_catatan, 80) }}</p>@endif
                                @if($dibatalkan)<p class="text-xs text-slate-400 italic">Alasan batal: {{ $d->alasan_pembatalan }}</p>@endif

                            @elseif($item['jenis'] === 'pembinaan')
                                <p class="font-semibold text-slate-800">{{ $d->jenis_pembinaan }}</p>
                                <p class="text-xs text-slate-400">{{ \Illuminate\Support\Str::limit($d->catatan_bk, 80) }}</p>
                                @if($d->hasil_pembinaan)<p class="text-xs text-slate-400 italic">Hasil: {{ \Illuminate\Support\Str::limit($d->hasil_pembinaan, 80) }}</p>@endif

                            @elseif($item['jenis'] === 'pengurangan')
                                <p class="font-semibold text-slate-800">Perubahan Perilaku</p>
                                <p class="text-xs text-slate-400">{{ \Illuminate\Support\Str::limit($d->alasan, 80) }}</p>

                            @else
                                <p class="font-semibold text-slate-800">Pemanggilan Orang Tua</p>
                                <p class="text-xs text-slate-400">{{ \Illuminate\Support\Str::limit($d->alasan, 80) }}</p>
                                @if($d->hasil_pertemuan)<p class="text-xs text-slate-400 italic">Hasil: {{ \Illuminate\Support\Str::limit($d->hasil_pertemuan, 80) }}</p>@endif
                                @if($d->kesepakatan)<p class="text-xs text-slate-400 italic">Kesepakatan: {{ \Illuminate\Support\Str::limit($d->kesepakatan, 80) }}</p>@endif
                            @endif
                        </td>

                        <td class="text-center whitespace-nowrap">
                            @if($item['jenis'] === 'kasus')
                                <span class="badge bg-rose-50 text-rose-700">+{{ $d->poin }}</span>
                            @elseif($item['jenis'] === 'pengurangan')
                                <span class="badge bg-emerald-50 text-emerald-700">-{{ $d->jumlah }}</span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>

                        <td class="text-slate-500 text-xs">
                            {{ $item['jenis'] === 'kasus' ? ($d->guruPelapor->name ?? '-') : ($d->petugas->name ?? '-') }}
                        </td>

                        <td class="whitespace-nowrap">
                            @if($dibatalkan)
                                <span class="badge bg-slate-100 text-slate-400">Dibatalkan</span>

                            {{-- Dulu berupa dropdown 4 pilihan status yang harus
                                 dipahami dulu. Sekarang cukup badge keadaan +
                                 satu tombol, sama persis dengan yang ada di
                                 daftar Kasus & Pembinaan. --}}
                            @elseif($item['jenis'] === 'kasus' && $bisaKelolaPoin)
                                <span class="badge {{ $d->badgeStatusRingkas() }}">{{ $d->labelStatusRingkas() }}</span>

                            @elseif($item['jenis'] === 'pembinaan' && $bisaKelolaPoin)
                                <span class="badge {{ $d->badgeStatusRingkas() }}">{{ $d->labelStatusRingkas() }}</span>

                            @elseif($item['jenis'] === 'pemanggilan')
                                @if(! $d->sudahAdaHasil())
                                    <span class="badge bg-slate-100 text-slate-500"><i class="fa-solid fa-hourglass-half mr-1"></i> Menunggu</span>
                                @else
                                    <span class="badge {{ $d->ortu_hadir ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                        {{ $d->ortu_hadir ? 'Ortu Hadir' : 'Ortu Tidak Hadir' }}
                                    </span>
                                @endif

                            @elseif($item['jenis'] === 'pengurangan')
                                <span class="badge bg-emerald-50 text-emerald-700">Berlaku</span>

                            @else
                                <span class="badge bg-slate-100 text-slate-600">{{ $d->status }}</span>
                            @endif
                        </td>

                        <td class="td-aksi no-print">
                            <div class="action-buttons">
                                @if($item['jenis'] === 'kasus' && $bisaKelolaPoin && ! $dibatalkan)
                                    <x-bk-tombol-selesai
                                        :action="route('bk.kasus.update-status', $d)"
                                        metode="PATCH"
                                        :selesai="$d->isSelesai()"
                                        :status-buka="$d->statusSaatDibukaKembali()" />
                                @endif

                                @if($item['jenis'] === 'pembinaan' && $bisaKelolaPoin)
                                    <x-bk-tombol-selesai
                                        :action="route('bk.pembinaan.update', $d)"
                                        metode="PUT"
                                        :selesai="$d->isSelesai()"
                                        status-buka="Pembinaan">
                                        <input type="hidden" name="hasil_pembinaan" value="{{ $d->hasil_pembinaan }}">
                                    </x-bk-tombol-selesai>
                                @endif

                                @if($item['jenis'] === 'pemanggilan' && $d->surat)
                                    <a href="{{ route('surat.show', $d->surat) }}" target="_blank" class="btn-chip btn-chip-edit"
                                       title="Surat {{ $d->surat->nomor_surat ?: 'belum bernomor' }}">
                                        <i class="fa-solid fa-envelope"></i> Surat
                                    </a>
                                @endif

                                @if(! empty($d->bukti_file_url))
                                    <a href="{{ $d->bukti_file_url }}" target="_blank" class="btn-chip btn-chip-cancel"
                                       title="Bukti {{ strtoupper(pathinfo($d->bukti_file, PATHINFO_EXTENSION)) }}">
                                        <i class="fa-solid fa-paperclip"></i> Bukti
                                    </a>
                                @endif

                                @if($item['jenis'] === 'pemanggilan' && ! $d->sudahAdaHasil())
                                    <a href="{{ route('bk.pemanggilan.hasil.edit', $d) }}" class="btn-chip btn-chip-success">
                                        <i class="fa-solid fa-pen"></i> Isi Hasil
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-between flex-wrap gap-3">
            <p class="text-xs text-slate-400">
                Menampilkan {{ $timeline->firstItem() }}–{{ $timeline->lastItem() }} dari {{ $timeline->total() }} catatan.
                <span class="no-print">Tombol Cetak mencetak baris yang sedang tampil — pilih "100 baris" dulu bila ingin seluruh riwayat dalam satu lembar.</span>
            </p>
            <div class="no-print">{{ $timeline->links() }}</div>
        </div>
        @endif

        @if($bisaKelolaPoin && $timeline->isNotEmpty())
            <p class="px-5 pb-4 text-[11px] text-slate-400">
                Menandai kasus atau pembinaan sebagai "Selesai" otomatis menyelesaikan pasangannya juga.
            </p>
        @endif
    </div>

</div>
@endsection
