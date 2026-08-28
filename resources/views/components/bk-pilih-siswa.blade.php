{{--
    LANGKAH "CARI & PILIH SISWA" — dipakai bersama oleh halaman pencatatan di
    Buku Catatan BK (Pemanggilan Orang Tua, Pembinaan, Pengurangan Poin).

    Ketiganya dulu punya jalannya sendiri-sendiri: Pemanggilan lewat halaman
    tersendiri, sedangkan Pembinaan & Pengurangan Poin HANYA lewat modal di
    halaman Profil Perilaku Siswa. Sekarang semuanya memakai langkah yang
    persis sama supaya pengguna tidak perlu belajar dua cara berbeda.

    Props:
    - rute   : nama route halaman pencatatan (mis. 'bk.pembinaan.create')
    - siswa  : siswa yang sudah dipilih (null kalau belum)
    - hasil  : hasil pencarian (koleksi Siswa)
    - nomor  : nomor langkah yang ditampilkan (default 1)
--}}
@props(['rute', 'siswa' => null, 'hasil' => null, 'nomor' => 1])

<div class="card p-5">
    <p class="font-bold text-slate-800 mb-3 text-sm">{{ $nomor }}. Cari &amp; Pilih Siswa</p>

    <form method="GET" class="flex gap-2 mb-3">
        <input type="text" name="cari" value="{{ request('cari') }}"
               placeholder="Cari nama / NIS siswa..." class="input flex-1">
        <button type="submit" class="btn-outline">Cari</button>
    </form>

    @if($siswa)
        <div class="flex items-center justify-between bg-brand-50/60 border border-brand-100 rounded-lg px-3 py-2">
            <div>
                <p class="font-semibold text-sm">{{ $siswa->nama }}</p>
                <p class="text-xs text-slate-400">{{ $siswa->nis }} &middot; {{ $siswa->kelas->nama_kelas ?? '-' }}</p>
            </div>
            <a href="{{ route($rute) }}" class="text-xs text-red-500 font-semibold">Ganti siswa</a>
        </div>
    @elseif(request()->filled('cari'))
        <div class="border border-slate-200 rounded-lg divide-y divide-slate-100">
            @forelse($hasil ?? collect() as $s)
                <a href="{{ route($rute, ['siswa_id' => $s->id]) }}"
                   class="flex items-center justify-between px-3 py-2 hover:bg-slate-50">
                    <div>
                        <p class="font-semibold text-sm">{{ $s->nama }}</p>
                        <p class="text-xs text-slate-400">{{ $s->nis }} &middot; {{ $s->kelas->nama_kelas ?? '-' }}</p>
                    </div>
                    <span class="text-brand-600 text-xs font-semibold">Pilih</span>
                </a>
            @empty
                <p class="text-xs text-slate-400 px-3 py-3">Tidak ada siswa yang cocok.</p>
            @endforelse
        </div>
    @else
        <p class="text-xs text-slate-400">Ketik nama atau NIS siswa di atas, lalu tekan Cari.</p>
    @endif
</div>
