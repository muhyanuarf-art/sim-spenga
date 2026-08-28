{{--
    BAR TAB "BUKU CATATAN BK".

    Empat daftar BK (Kasus, Pembinaan, Pengurangan Poin, Pemanggilan Orang
    Tua) dulu berdiri sendiri-sendiri sebagai empat menu terpisah di sidebar.
    Keempatnya sebenarnya laporan dari data yang sama dan jarang dipakai
    berpindah-pindah — deretan menunya justru membuat pengguna bingung harus
    mulai dari mana.

    Sekarang keempatnya jadi SATU menu ("Buku Catatan BK") dengan tab di sini.
    Rutenya sengaja TIDAK diubah, jadi semua tautan lama, tombol cetak, dan
    penyaring di tiap halaman tetap bekerja apa adanya.

    Tab yang tampil mengikuti hak akses yang berlaku sebelumnya: Guru mapel
    hanya berkepentingan pada Kasus (untuk melaporkan pelanggaran), sedangkan
    Pembinaan/Pengurangan/Pemanggilan adalah ranah Guru BK & pimpinan.
--}}
@php
    $peran = auth()->user()->role;

    $semuaTab = [
        [
            'route' => 'bk.kasus.index',
            'label' => 'Kasus & Pelanggaran',
            'ikon' => 'fa-folder-open',
            'cocok' => 'bk.kasus.*',
            'roles' => ['guru', 'guru_bk', 'kurikulum', 'kepala_sekolah', 'kesiswaan', 'admin'],
        ],
        [
            'route' => 'bk.pembinaan.index',
            'label' => 'Pembinaan',
            'ikon' => 'fa-handshake',
            'cocok' => 'bk.pembinaan.*',
            'roles' => ['guru_bk', 'kurikulum', 'kepala_sekolah', 'kesiswaan', 'admin'],
        ],
        [
            'route' => 'bk.pengurangan.index',
            'label' => 'Pengurangan Poin',
            'ikon' => 'fa-circle-check',
            'cocok' => 'bk.pengurangan.*',
            'roles' => ['guru_bk', 'kurikulum', 'kepala_sekolah', 'kesiswaan', 'admin'],
        ],
        [
            'route' => 'bk.pemanggilan.index',
            'label' => 'Pemanggilan Orang Tua',
            'ikon' => 'fa-phone',
            'cocok' => 'bk.pemanggilan.*',
            'roles' => ['guru_bk', 'kurikulum', 'kepala_sekolah', 'kesiswaan', 'admin'],
        ],
        [
            // Rekap sebulan penuh dari keempat catatan di atas — diletakkan
            // sebagai tab, bukan menu baru, supaya jumlah menu BK tetap tiga.
            'route' => 'bk.laporan-bulanan',
            'label' => 'Laporan Bulanan',
            'ikon' => 'fa-file-lines',
            'cocok' => 'bk.laporan-bulanan',
            'roles' => ['guru_bk', 'kurikulum', 'kepala_sekolah', 'kesiswaan', 'admin'],
        ],
    ];

    // Admin selalu lolos, seperti App\Http\Middleware\EnsureRole.
    $tab = collect($semuaTab)->filter(
        fn ($t) => $peran === 'admin' || in_array($peran, $t['roles'], true)
    )->values();
@endphp

{{-- Kalau hanya satu tab yang boleh dilihat, bar-nya tidak berguna. --}}
@if($tab->count() > 1)
    <div class="card p-2 no-print">
        <div class="flex flex-wrap gap-1">
            @foreach($tab as $t)
                @php $aktif = request()->routeIs($t['cocok']); @endphp
                <a href="{{ route($t['route']) }}"
                   class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold transition
                          {{ $aktif ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100' }}">
                    <i class="fa-solid {{ $t['ikon'] }}"></i>
                    <span>{{ $t['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif
