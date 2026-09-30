@php
    use App\Models\Pemberitahuan;

    $pengguna = auth()->user();

    // Satu query untuk daftarnya, satu untuk hitungan yang belum dibaca.
    // Komponen ini dirender di SETIAP halaman, jadi keduanya dijaga murah
    // (lihat indeks di migrasi pemberitahuans).
    $daftar = Pemberitahuan::milik($pengguna->id)
        ->with('dari:id,name')
        ->orderByDesc('created_at')
        ->limit(Pemberitahuan::TAMPIL)
        ->get();

    $belum = Pemberitahuan::milik($pengguna->id)->belumDibaca()->count();
@endphp

<div class="relative" x-data="{
        buka: false,
        jumlah: {{ $belum }},
        init() {
            // Angkanya disegarkan berkala supaya laporan yang masuk saat
            // halaman dibiarkan terbuka tetap terlihat. Hanya ANGKANYA
            // yang diambil — daftarnya baru dimuat saat halaman berganti.
            setInterval(() => {
                fetch('{{ route('pemberitahuan.jumlah') }}', { headers: { 'Accept': 'application/json' } })
                    .then(r => r.ok ? r.json() : null)
                    .then(d => { if (d) this.jumlah = d.jumlah; })
                    .catch(() => {});
            }, 60000);
        }
     }" @click.outside="buka = false">

    <button @click="buka = !buka"
            class="relative w-10 h-10 flex items-center justify-center rounded-xl hover:bg-slate-100 transition"
            :class="jumlah > 0 ? 'text-rose-600' : 'text-slate-500'"
            :title="jumlah > 0 ? jumlah + ' pemberitahuan belum dibaca' : 'Tidak ada pemberitahuan baru'">
        <i class="fa-solid fa-bell text-lg"></i>

        {{-- Angka jumlahnya, bukan cuma titik: "ada sesuatu" jauh kurang
             berguna daripada "ada 4". --}}
        <span x-show="jumlah > 0" x-cloak
              class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-rose-600 text-white text-[10px] font-bold flex items-center justify-center ring-2 ring-white"
              x-text="jumlah > 99 ? '99+' : jumlah"></span>
    </button>

    <div x-show="buka" x-cloak x-transition.origin.top.right
         class="absolute right-0 mt-2 w-[22rem] sm:w-[26rem] bg-white rounded-xl border border-slate-200 shadow-xl overflow-hidden z-30">

        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-3">
            <div>
                <p class="font-bold text-slate-800">Pemberitahuan</p>
                <p class="text-[11px] text-slate-400" x-text="jumlah > 0 ? jumlah + ' belum dibaca' : 'Semua sudah dibaca'"></p>
            </div>
            @if($belum > 0)
                <form method="POST" action="{{ route('pemberitahuan.baca-semua') }}">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Tandai semua</button>
                </form>
            @endif
        </div>

        <div class="max-h-[26rem] overflow-y-auto">
            @forelse($daftar as $p)
                {{-- POST, bukan tautan biasa: membukanya MENGUBAH keadaan
                     (menandai terbaca). Lihat PemberitahuanController::buka. --}}
                <form method="POST" action="{{ route('pemberitahuan.buka', $p) }}">
                    @csrf
                    <button type="submit"
                            class="w-full text-left px-4 py-3 flex gap-3 hover:bg-slate-50 transition border-b border-slate-50 {{ $p->sudahDibaca() ? '' : 'bg-brand-50/40' }}">
                        <span class="w-9 h-9 rounded-lg {{ $p->warna() }} flex items-center justify-center shrink-0">
                            <i class="fa-solid {{ $p->ikon() }}"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-2">
                                <span class="font-semibold text-sm text-slate-800 truncate">{{ $p->judul }}</span>
                                @unless($p->sudahDibaca())
                                    <span class="w-2 h-2 rounded-full bg-rose-500 shrink-0"></span>
                                @endunless
                            </span>
                            <span class="block text-sm text-slate-600 leading-snug mt-0.5">{{ $p->isi }}</span>
                            <span class="block text-[11px] text-slate-400 mt-1">
                                {{ $p->kapan() }}@if($p->dari) · dari {{ $p->dari->name }}@endif
                            </span>
                        </span>
                    </button>
                </form>
            @empty
                <div class="px-4 py-10 text-center">
                    <i class="fa-regular fa-bell-slash text-2xl text-slate-300"></i>
                    <p class="text-sm text-slate-500 mt-2">Belum ada pemberitahuan.</p>
                    <p class="text-xs text-slate-400 mt-1">Laporan yang masuk untuk Anda akan tampil di sini.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
