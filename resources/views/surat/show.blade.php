@extends('layouts.app')
@section('title', 'Surat — ' . ($surat->siswa->nama ?? '-'))

@section('content')
@php
    $bisaKelola = in_array(auth()->user()->role, ['guru_bk', 'admin']);
    $tipe = $surat->jenisSurat->tipe_formulir ?? 'bebas';
    $f = $surat->data_formulir ?? [];
@endphp
<div class="space-y-6">
    <div class="card p-5 no-print flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('surat.index') }}" class="btn-outline">&larr; Kembali ke Daftar Surat</a>
        <div class="flex gap-2">
            @if($bisaKelola)
                <a href="{{ route('surat.edit', $surat) }}" class="btn-outline"><i class="fa-solid fa-pen mr-1.5"></i> Edit</a>
            @endif
            <button type="button" onclick="cetakBagian('print-surat')" class="btn-primary"><i class="fa-solid fa-print mr-1.5"></i> Cetak / Export PDF</button>
            @if($bisaKelola)
                {{-- Memakai .btn-danger, bukan .btn-chip — chip itu untuk tombol
                     mungil di dalam baris tabel (lihat daftar siswa/kelas/dll),
                     sedangkan di toolbar ini tetangganya .btn-outline &
                     .btn-primary yang ukurannya penuh. Ketiganya didefinisikan
                     dalam satu aturan yang sama di resources/css/app.css, jadi
                     tinggi, padding, sudut, dan ukuran hurufnya persis sama. --}}
                <form method="POST" action="{{ route('surat.destroy', $surat) }}" data-konfirmasi="Hapus surat ini?">
                    @csrf @method('DELETE')
                    <button class="btn-danger"><i class="fa-solid fa-trash mr-1.5"></i> Hapus</button>
                </form>
            @endif
        </div>
    </div>

    <div class="card p-8 print-section max-w-3xl mx-auto" id="print-surat">
        <x-kop-surat />

        @if($tipe === 'bebas')
            {{-- Surat Panggilan Orang Tua/Wali — format bebas seperti sebelumnya. --}}
            <div class="flex justify-between items-start mb-6 text-sm">
                <div>
                    <p><span class="text-slate-500">Nomor</span> : {{ $surat->nomor_surat ?: '-' }}</p>
                    <p><span class="text-slate-500">Perihal</span> : {{ $surat->jenisSurat->nama_jenis ?? '-' }}</p>
                </div>
                <p>{{ $surat->tanggal->translatedFormat('d F Y') }}</p>
            </div>
            <div class="mb-6 text-sm">
                <p>Kepada Yth.</p>
                <p class="font-semibold">Orang Tua/Wali dari {{ $surat->siswa->nama ?? '-' }}</p>
                <p class="text-slate-500">Kelas {{ $surat->siswa->kelas->nama_kelas ?? '-' }}</p>
            </div>
            <div class="text-sm leading-relaxed whitespace-pre-line mb-4">{{ $surat->isi }}</div>

            {{-- (2026-08-26) — tanda tangan ditambah jadi 2: Kepala Sekolah
                 (kiri) + Guru BK pembuat surat (kanan), sesuai instruksi. --}}
            <x-blok-tanda-tangan-dua
                jabatan-kanan="Guru BK"
                :nama-kanan="$surat->dibuatOleh->name ?? null"
                :nip-kanan="$surat->dibuatOleh->nip ?? null"
            />

        @elseif($tipe === 'izin_meninggalkan_pelajaran')
            <p class="text-center font-bold underline mb-1">SURAT IJIN MENINGGALKAN PELAJARAN</p>
            <p class="text-center text-sm mb-6">Nomor : {{ $surat->nomor_surat ?: '-' }}</p>
            <table class="text-sm w-full mb-6">
                <tr><td class="w-48 py-1 align-top">Nama</td><td class="w-4 align-top">:</td><td class="align-top">{{ $f['nama'] ?? $surat->siswa->nama }}</td></tr>
                <tr><td class="py-1 align-top">Kelas</td><td class="align-top">:</td><td class="align-top">{{ $f['kelas'] ?? $surat->siswa->kelas->nama_kelas ?? '-' }}</td></tr>
                <tr><td class="py-1 align-top">Alamat</td><td class="align-top">:</td><td class="align-top">{{ $f['alamat'] ?? '-' }}</td></tr>
                <tr><td class="py-1 align-top">Diberi ijin meninggalkan pelajaran mulai jam ke</td><td class="align-top">:</td><td class="align-top">{{ $f['jam_ke'] ?? '-' }}</td></tr>
                <tr><td class="py-1 align-top">Keperluan</td><td class="align-top">:</td><td class="align-top">{{ $f['keperluan'] ?? '-' }}</td></tr>
                <tr><td class="py-1 align-top">Keterangan lain</td><td class="align-top">:</td><td class="align-top">{{ $f['keterangan_lain'] ?? '-' }}</td></tr>
            </table>
            {{-- Tanggal menyatu dengan baris kota di atas tanda tangan
                 ("Bumiayu, 26 Agustus 2026") sesuai lazimnya surat resmi.
                 Sebelumnya tanggal berdiri sendiri di kanan bawah bersama
                 nomor surat — nomornya kini pindah ke tengah bawah judul. --}}
            <div class="cetak-utuh flex justify-between gap-6">
                <div class="text-sm text-center w-56">
                    <p>Mengetahui</p>
                    <p>Guru Mata Pelajaran</p>
                    <div class="h-16"></div>
                    <p>............................</p>
                </div>
                <div class="text-sm text-center w-56">
                    <p>{{ $pengaturanSekolahGlobal->lokasiTtd() }}, {{ $surat->tanggal->translatedFormat('d F Y') }}</p>
                    <p>Koordinator/Staf BK</p>
                    <div class="h-16"></div>
                    <p class="font-bold underline underline-offset-2">{{ $surat->dibuatOleh->name ?? '-' }}</p>
                    <p>NIP. {{ $surat->dibuatOleh->nip ?: '............................' }}</p>
                </div>
            </div>

        @elseif($tipe === 'keterangan_terlambat')
            <p class="text-center font-bold underline mb-1">SURAT KETERANGAN TERLAMBAT</p>
            <p class="text-center text-sm mb-6">Nomor : {{ $surat->nomor_surat ?: '-' }}</p>
            <p class="text-sm mb-4">Yang bertanda tangan di bawah ini Koordinator/Staf BK menerangkan bahwa :</p>
            <table class="text-sm w-full mb-4">
                <tr><td class="w-48 py-1 align-top">Nama</td><td class="w-4 align-top">:</td><td class="align-top">{{ $f['nama'] ?? $surat->siswa->nama }}</td></tr>
                <tr><td class="py-1 align-top">Kelas</td><td class="align-top">:</td><td class="align-top">{{ $f['kelas'] ?? $surat->siswa->kelas->nama_kelas ?? '-' }}</td></tr>
                <tr><td class="py-1 align-top">Alamat</td><td class="align-top">:</td><td class="align-top">{{ $f['alamat'] ?? '-' }}</td></tr>
                <tr><td class="py-1 align-top">Terlambat</td><td class="align-top">:</td><td class="align-top">{{ $f['terlambat'] ?? '-' }}</td></tr>
                <tr><td class="py-1 align-top">Alasan terlambat</td><td class="align-top">:</td><td class="align-top">{{ $f['alasan_terlambat'] ?? '-' }}</td></tr>
            </table>
            <p class="text-sm mb-2">Mohon dengan hormat kepada Bapak/Ibu Guru yang mengajar di ruang tersebut agar siswa yang bersangkutan dapat menerima pelajaran yang Bapak/Ibu ampu.</p>
            <p class="text-sm mb-6">Atas perhatiannya kami sampaikan terima kasih.</p>
            <div class="cetak-utuh flex justify-between gap-6">
                <div class="text-sm text-center w-56">
                    <p>Mengetahui</p>
                    <p>Guru Mata Pelajaran</p>
                    <div class="h-16"></div>
                    <p>............................</p>
                </div>
                <div class="text-sm text-center w-56">
                    <p>{{ $pengaturanSekolahGlobal->lokasiTtd() }}, {{ $surat->tanggal->translatedFormat('d F Y') }}</p>
                    <p>Koordinator/Staf BK</p>
                    <div class="h-16"></div>
                    <p class="font-bold underline underline-offset-2">{{ $surat->dibuatOleh->name ?? '-' }}</p>
                    <p>NIP. {{ $surat->dibuatOleh->nip ?: '............................' }}</p>
                </div>
            </div>

        @elseif($tipe === 'pernyataan_pelanggaran')
            <p class="text-center font-bold underline mb-1">SURAT PERNYATAAN PELANGGARAN SISWA</p>
            {{-- Sebelumnya jenis surat ini sama sekali tidak mencantumkan
                 nomor surat — padahal nomornya tetap dicatat di agenda. --}}
            <p class="text-center text-sm">Nomor : {{ $surat->nomor_surat ?: '-' }}</p>
            <p class="text-center text-sm mb-6">PELANGGARAN KE : {{ $f['pelanggaran_ke'] ?? '-' }}</p>
            <p class="text-sm mb-2">Yang bertanda tangan di bawah ini, Saya :</p>
            <p class="text-sm mb-4">Nama / Kelas : {{ $f['nama'] ?? $surat->siswa->nama }} / {{ $f['kelas'] ?? $surat->siswa->kelas->nama_kelas ?? '-' }}</p>
            <p class="text-sm mb-2">Pada hari ini, {{ $surat->tanggal->translatedFormat('l') }}, tanggal {{ $surat->tanggal->translatedFormat('d F Y') }}, Saya telah melakukan pelanggaran disiplin sekolah berupa :</p>
            <p class="text-sm mb-6 pl-4">{{ $f['pelanggaran'] ?? '-' }}</p>
            <p class="text-sm mb-6">Apabila saya melakukan/mengulangi lagi sampai 3 kali pelanggaran, saya bersedia diberikan sanksi disiplin sesuai dengan peraturan yang berlaku di sekolah ini.</p>
            <div class="cetak-utuh flex justify-between gap-6">
                <div class="text-sm text-center w-56">
                    <p>Mengetahui</p>
                    <p>Koordinator/Staf BK</p>
                    <div class="h-16"></div>
                    <p class="font-bold underline underline-offset-2">{{ $surat->dibuatOleh->name ?? '-' }}</p>
                    <p>NIP. {{ $surat->dibuatOleh->nip ?: '............................' }}</p>
                </div>
                <div class="text-sm text-center w-56">
                    <p>&nbsp;</p>
                    <p>Yang membuat pernyataan</p>
                    <div class="h-16"></div>
                    <p>............................</p>
                </div>
            </div>
        @endif
    </div>

    @if($surat->keterangan)
        <div class="card p-5 no-print">
            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Keterangan (internal, tidak ikut tercetak)</p>
            <p class="text-sm text-slate-600">{{ $surat->keterangan }}</p>
        </div>
    @endif
</div>
@endsection
