{{--
    PAPAN HARI INI — satu kotak untuk satu jam pelajaran.

    =====================================================================
    KENAPA BERBENTUK PAPAN, BUKAN ANGKA PERSEN
    =====================================================================
    "Kepatuhan 83%" tidak memberi tahu apa pun yang bisa ditindaklanjuti.
    Delapan puluh tiga persen bisa berarti setiap kelas kehilangan satu
    jam di sana-sini — atau berarti dua kelas tidak terjurnal sama sekali
    sepanjang hari. Dua keadaan itu butuh tindakan yang sama sekali
    berbeda, tapi angkanya sama.

    Disusun sebagai papan, keduanya langsung terbedakan: yang pertama
    tampak sebagai bintik-bintik yang tersebar, yang kedua sebagai dua
    baris kosong yang mencolok.

    =====================================================================
    DIBUAT SEBAGAI TABEL, BUKAN GRID CSS
    =====================================================================
    Bentuk kisi memang bisa dibuat dengan CSS Grid, tapi tabel adalah apa
    yang benda ini sebenarnya: baris berjudul kelas, kolom berjudul jam.
    Pembaca layar pun membacanya dengan benar tanpa tambahan apa-apa.

    Datanya dari $papanHariIni yang disusun controller — tidak ada query
    di dalam berkas tampilan ini.
--}}
@php
    $jumlahJam = max($jamTerjauh, 1);
@endphp

<div class="card">
    <div class="card-head">
        <div class="min-w-0 flex items-start gap-3">
            <span class="aksen-batang" aria-hidden="true"></span>
            <div class="min-w-0">
                <p class="section-title tanpa-aksen">Papan Hari Ini</p>
                <p class="text-xs text-slate-400 mt-0.5">Tiap kotak satu jam pelajaran.</p>
            </div>
        </div>
        <p class="text-right leading-none shrink-0">
            <span class="text-2xl font-extrabold text-slate-800 tracking-tight">{{ $jurnalHariIni }}</span><span
                class="text-sm font-semibold text-slate-400">/{{ $jadwalHariIni }}</span>
            <span class="block text-[11px] text-slate-400 mt-1">sesi terjurnal</span>
        </p>
    </div>

    @if($papanHariIni->isEmpty())
        <p class="empty-state">Tidak ada jam pelajaran terjadwal hari ini.</p>
    @else
        <div class="p-5 overflow-x-auto">
            <table class="border-separate border-spacing-1">
                <thead>
                    <tr>
                        <th class="w-9"><span class="sr-only">Kelas</span></th>
                        @foreach(range(1, $jumlahJam) as $jamKe)
                            <th class="text-[11px] font-semibold text-slate-400 w-6 pb-0.5">{{ $jamKe }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($papanHariIni as $baris)
                        <tr>
                            <th class="text-[11px] font-bold text-slate-500 text-right pr-1.5 whitespace-nowrap">
                                {{ $baris['kelas'] }}
                            </th>
                            @foreach($baris['jam'] as $i => $keadaan)
                                <td>
                                    @if($keadaan === null)
                                        {{-- Tidak ada jam pelajaran di slot ini. --}}
                                        <div class="w-6 h-5 rounded bg-slate-100" title="{{ $baris['kelas'] }} jam ke-{{ $i + 1 }}: tidak ada jam pelajaran"></div>
                                    @elseif($keadaan)
                                        <div class="w-6 h-5 rounded bg-brand-600" title="{{ $baris['kelas'] }} jam ke-{{ $i + 1 }}: jurnalnya sudah ditulis"></div>
                                    @else
                                        <div class="w-6 h-5 rounded bg-white ring-1 ring-inset ring-brand-300" title="{{ $baris['kelas'] }} jam ke-{{ $i + 1 }}: belum dijurnal"></div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="catatan-kartu space-y-1.5">
            <p class="flex items-center gap-2"><span class="w-5 h-3.5 rounded bg-brand-600 shrink-0"></span> jurnalnya sudah ditulis</p>
            <p class="flex items-center gap-2"><span class="w-5 h-3.5 rounded bg-white ring-1 ring-inset ring-brand-300 shrink-0"></span> belum ditulis</p>
            <p class="flex items-center gap-2"><span class="w-5 h-3.5 rounded bg-slate-100 shrink-0"></span> tidak ada jam pelajaran</p>
        </div>
    @endif
</div>
