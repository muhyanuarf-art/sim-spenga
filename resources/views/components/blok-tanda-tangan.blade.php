{{--
    Blok tanda tangan yang dipakai di halaman-halaman Cetak.
    Kota & tanggal sudah otomatis terisi dari Pengaturan Sekolah / tanggal
    hari ini, tapi operator bisa mengetik ulang isinya langsung sebelum
    menekan Cetak — nilai yang sedang tampil di layar itulah yang ikut
    tercetak, jadi tidak perlu menu/tombol simpan terpisah.

    Props:
    - jabatan (string, wajib)  : mis. "Wali Kelas 7A", "Guru Mata Pelajaran Matematika", "Kepala Sekolah"
    - nama    (string, opsional): nama penanda tangan
    - nip     (string, opsional): NIP penanda tangan
--}}
@props(['jabatan', 'nama' => null, 'nip' => null])

@php
    $kotaDefault = $pengaturanSekolahGlobal->lokasiTtd();
    $tanggalDefault = now()->translatedFormat('d F Y');
@endphp

<div class="flex justify-end mt-8 print:mt-10">
    <div class="text-sm text-slate-700 text-center w-72">
        <p class="mb-1">
            <input type="text" value="{{ $kotaDefault }}"
                   class="text-center bg-transparent border-b border-dashed border-slate-300 focus:outline-none focus:border-brand-500 w-28 print:border-b-0"
                   aria-label="Kota tanda tangan (bisa diubah sebelum cetak)">,
            <input type="text" value="{{ $tanggalDefault }}"
                   class="text-center bg-transparent border-b border-dashed border-slate-300 focus:outline-none focus:border-brand-500 w-36 print:border-b-0"
                   aria-label="Tanggal tanda tangan (bisa diubah sebelum cetak)">
        </p>
        <p class="text-xs text-slate-400 mb-1 print:hidden"><i class="fa-solid fa-pen mr-1.5"></i> Kota & tanggal bisa diketik ulang di atas sebelum Cetak</p>
        <p>{{ $jabatan }}</p>
        <div class="h-16"></div>
        <p class="font-bold underline underline-offset-2">{{ $nama ?: '............................' }}</p>
        <p>NIP. {{ $nip ?: '............................' }}</p>
    </div>
</div>
