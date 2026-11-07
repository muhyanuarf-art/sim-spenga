{{--
    BELUM DIJURNAL — sesi yang jamnya sudah lewat tapi jurnalnya belum
    ditulis.

    Yang SENGAJA tidak masuk daftar: sesi yang jamnya belum lewat. Sesi
    siang hari yang belum dijurnal pada pukul sembilan pagi bukan
    kelalaian, dan menagihkannya adalah cara tercepat membuat seluruh
    daftar ini berhenti dipercaya. Jumlahnya tetap disebut di kaki kartu,
    supaya tidak ada yang mengira daftarnya kurang.

    Datanya dari $belumDijurnal & $sesiBelumLewatJam yang disusun
    controller.
--}}
@php
    $bolehBukaJurnal = \App\Support\Navigasi::bolehAkses('walikelas.jurnal-kelas', auth()->user());
@endphp

<div class="card">
    <div class="card-head">
        <div class="min-w-0 flex items-start gap-3">
            <span class="aksen-batang" aria-hidden="true"></span>
            <div class="min-w-0">
                <p class="section-title tanpa-aksen">Belum Dijurnal</p>
                <p class="text-xs text-slate-400 mt-0.5">Sesi yang jamnya sudah lewat hari ini.</p>
            </div>
        </div>
        @if($bolehBukaJurnal)
            <a href="{{ route('walikelas.jurnal-kelas') }}" wire:navigate
               class="text-sm font-semibold text-brand-600 hover:text-brand-700 shrink-0">
                Jurnal Mengajar Kelas
            </a>
        @endif
    </div>

    @if($belumDijurnal->isEmpty())
        <p class="empty-state">
            @if($jadwalHariIni === 0)
                Tidak ada jam pelajaran terjadwal hari ini.
            @elseif($sesiBelumLewatJam > 0)
                Belum ada yang tertinggal. {{ $sesiBelumLewatJam }} sesi lainnya belum lewat jamnya.
            @else
                Seluruh sesi hari ini sudah dijurnalkan.
            @endif
        </p>
    @else
        <ul class="divide-y divide-slate-100">
            @foreach($belumDijurnal as $s)
                <li class="flex items-center gap-3 px-5 py-3">
                    <span class="w-7 h-7 rounded-lg bg-brand-50 text-brand-700 text-xs font-bold flex items-center justify-center shrink-0"
                          title="Jam ke-{{ $s['jam_ke'] }}">{{ $s['jam_ke'] }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-slate-800 truncate">
                            {{ $s['kelas'] }} · {{ $s['mapel'] }}
                        </p>
                        <p class="text-xs text-slate-400 truncate">{{ $s['guru'] }}</p>
                    </div>
                    <span class="text-xs text-slate-400 shrink-0">selesai {{ $s['selesai'] }}</span>
                </li>
            @endforeach
        </ul>

        @if($sesiBelumLewatJam > 0)
            <p class="catatan-kartu">
                {{ $sesiBelumLewatJam }} sesi lainnya belum lewat jamnya, jadi belum ditagih.
            </p>
        @endif
    @endif
</div>
