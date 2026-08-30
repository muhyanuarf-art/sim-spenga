{{--
    LANGKAH "CARI & PILIH SISWA" — dipakai bersama oleh halaman pencatatan di
    Buku Catatan BK (Pemanggilan Orang Tua, Pembinaan, Pengurangan Poin).

    Ketiganya dulu punya jalannya sendiri-sendiri: Pemanggilan lewat halaman
    tersendiri, sedangkan Pembinaan & Pengurangan Poin HANYA lewat modal di
    halaman Profil Perilaku Siswa. Sekarang semuanya memakai langkah yang
    persis sama supaya pengguna tidak perlu belajar dua cara berbeda.

    ================================================================
    HANYA SISWA YANG PUNYA KASUS BELUM SELESAI
    ================================================================
    Ketiga pencatatan ini adalah TINDAK LANJUT atas sebuah kasus, jadi
    daftarnya sengaja dibatasi (lihat Siswa::scopePunyaKasusTerbuka).
    Daftarnya juga langsung tampil tanpa perlu mengetik dulu — jumlahnya
    sedikit, dan menyuruh orang menebak nama sebelum melihat apa pun
    adalah hambatan yang tidak perlu. Kotak pencarian tetap ada untuk
    mempersempit bila daftarnya panjang.

    Props:
    - rute   : nama route halaman pencatatan (mis. 'bk.pembinaan.create')
    - siswa  : siswa yang sudah dipilih (null kalau belum)
    - hasil  : daftar siswa yang boleh dipilih (koleksi Siswa)
    - nomor  : nomor langkah yang ditampilkan (default 1)
--}}
@props(['rute', 'siswa' => null, 'hasil' => null, 'nomor' => 1])

@php $daftar = $hasil ?? collect(); @endphp

<div class="card p-5">
    <p class="font-bold text-slate-800 mb-1 text-sm">{{ $nomor }}. Pilih Siswa</p>
    <p class="text-xs text-slate-500 mb-3 leading-relaxed">
        Yang tampil hanya siswa yang <strong>punya kasus belum selesai</strong> —
        karena catatan ini adalah tindak lanjut atas sebuah kasus.
    </p>

    @if($siswa)
        <div class="flex items-center justify-between bg-brand-50/60 border border-brand-100 rounded-lg px-3 py-2">
            <div>
                <p class="font-semibold text-sm">{{ $siswa->nama }}</p>
                <p class="text-xs text-slate-400">{{ $siswa->nis }} &middot; {{ $siswa->kelas->nama_kelas ?? '-' }}</p>
            </div>
            <a href="{{ route($rute) }}" class="text-xs text-red-500 font-semibold">Ganti siswa</a>
        </div>
    @else
        @if($daftar->isNotEmpty() || request()->filled('cari'))
            <form method="GET" class="flex gap-2 mb-3">
                <input type="text" name="cari" value="{{ request('cari') }}"
                       placeholder="Persempit: ketik nama / NIS..." class="input flex-1">
                <button type="submit" class="btn-outline">Cari</button>
                @if(request()->filled('cari'))
                    <a href="{{ route($rute) }}" class="btn-outline">Semua</a>
                @endif
            </form>
        @endif

        <div class="border border-slate-200 rounded-lg divide-y divide-slate-100">
            @forelse($daftar as $s)
                <a href="{{ route($rute, ['siswa_id' => $s->id]) }}"
                   class="flex items-center justify-between gap-3 px-3 py-2.5 hover:bg-slate-50">
                    <div class="min-w-0">
                        <p class="font-semibold text-sm">{{ $s->nama }}</p>
                        <p class="text-xs text-slate-400">{{ $s->nis }} &middot; {{ $s->kelas->nama_kelas ?? '-' }}</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if(! is_null($s->kasus_terbuka_count ?? null))
                            <span class="badge bg-amber-50 text-amber-700">
                                {{ $s->kasus_terbuka_count }} kasus belum selesai
                            </span>
                        @endif
                        <span class="text-brand-600 text-xs font-semibold">Pilih</span>
                    </div>
                </a>
            @empty
                {{-- Keadaan kosong dijelaskan, bukan sekadar "tidak ada".
                     Tanpa penjelasan, pengguna akan mengira siswanya belum
                     terdaftar atau aplikasinya rusak — padahal sebabnya
                     memang belum ada kasus yang perlu ditindaklanjuti. --}}
                <div class="px-4 py-6 text-center">
                    @if(request()->filled('cari'))
                        <p class="text-sm text-slate-500">
                            Tidak ada siswa berkasus terbuka yang cocok dengan
                            &ldquo;<strong>{{ request('cari') }}</strong>&rdquo;.
                        </p>
                        <a href="{{ route($rute) }}" class="text-xs text-brand-600 font-semibold hover:underline mt-1 inline-block">
                            Tampilkan semua
                        </a>
                    @else
                        <i class="fa-solid fa-clipboard-check text-2xl text-slate-300 block mb-2"></i>
                        <p class="text-sm font-semibold text-slate-600">Tidak ada siswa dengan kasus yang belum selesai.</p>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed max-w-md mx-auto">
                            Catatan ini adalah tindak lanjut atas sebuah kasus. Bila siswa yang Anda cari
                            memang bermasalah tetapi belum tercatat, catat kasusnya lebih dulu — setelah itu
                            ia akan muncul di sini.
                        </p>
                        <a href="{{ route('bk.kasus.create') }}" class="btn-primary mt-3">
                            <i class="fa-solid fa-file-circle-plus mr-2"></i> Catat Kasus Dulu
                        </a>
                    @endif
                </div>
            @endforelse
        </div>
    @endif
</div>
