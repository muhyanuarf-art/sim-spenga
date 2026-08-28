@php
    use App\Support\KonteksPeriode;

    $pilihan = KonteksPeriode::pilihan();
    $daftar = KonteksPeriode::daftarPilihan(auth()->user()?->role);
    $lihatSaja = KonteksPeriode::modeLihatSaja();
    $terkunci = $pilihan?->isTerkunci();
@endphp

{{-- PEMILIH PERIODE — mengganti Tahun Ajaran + Semester yang DILIHAT
     pengguna ini saja. Periode aktif sekolah hanya bisa diubah admin
     lewat menu Tahun Ajaran. Lihat App\Support\KonteksPeriode. --}}
@if($pilihan && $daftar->count() > 1)
    <form method="POST" action="{{ route('konteks-periode.ganti') }}" class="no-print"
          x-data @change="$el.submit()">
        @csrf
        <label class="sr-only" for="pemilih-periode">Periode yang dilihat</label>
        <div class="relative">
            <i class="fa-solid {{ $lihatSaja ? 'fa-clock-rotate-left' : ($terkunci ? 'fa-lock' : 'fa-calendar-days') }}
                      absolute left-2.5 top-1/2 -translate-y-1/2 text-xs pointer-events-none
                      {{ $lihatSaja ? 'text-amber-600' : ($terkunci ? 'text-rose-600' : 'text-brand-600') }}"></i>
            <select id="pemilih-periode" name="tahun_ajaran_id"
                    class="appearance-none rounded-lg border pl-7 pr-7 py-1.5 text-xs font-semibold cursor-pointer
                           focus:outline-none focus:ring-2 focus:ring-brand-200
                           {{ $lihatSaja
                                ? 'border-amber-200 bg-amber-50 text-amber-800'
                                : ($terkunci ? 'border-rose-200 bg-rose-50 text-rose-800' : 'border-brand-100 bg-brand-50 text-brand-700') }}"
                    title="{{ $lihatSaja ? 'Sedang melihat periode lampau — mode lihat saja' : 'Periode yang sedang dilihat' }}">
                @foreach($daftar as $t)
                    <option value="{{ $t->id }}" {{ $t->id === $pilihan->id ? 'selected' : '' }}>
                        {{ $t->labelSingkat() }}{{ $t->is_active ? ' (berjalan)' : ($t->isTerkunci() ? ' (terkunci)' : '') }}
                    </option>
                @endforeach
            </select>
            <i class="fa-solid fa-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] opacity-60 pointer-events-none"></i>
        </div>
    </form>
@elseif($pilihan)
    <span class="badge {{ $terkunci ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-100' : 'bg-brand-50 text-brand-700 ring-1 ring-brand-100' }}"
          title="{{ $terkunci ? 'Periode terkunci — data tidak bisa diubah' : 'Periode akademik aktif' }}">
        <i class="fa-solid {{ $terkunci ? 'fa-lock' : 'fa-calendar-days' }} mr-1.5"></i>
        <span class="hidden sm:inline">{{ $pilihan->labelSingkat() }}</span>
        <span class="sm:hidden">{{ $pilihan->nama }}</span>
    </span>
@else
    <a href="{{ \App\Support\Navigasi::bolehAkses('tahun-ajaran.index', auth()->user()) ? route('tahun-ajaran.index') : route('dashboard') }}"
       class="badge bg-amber-50 text-amber-700 ring-1 ring-amber-100">
        <i class="fa-solid fa-triangle-exclamation mr-1.5"></i>
        <span class="hidden sm:inline">Belum ada periode aktif</span>
        <span class="sm:hidden">Periode?</span>
    </a>
@endif
