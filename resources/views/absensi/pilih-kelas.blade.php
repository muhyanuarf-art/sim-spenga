@extends('layouts.app')
@section('title', 'Absensi & Jurnal Mengajar')

@section('content')
<div class="space-y-6">
    @if($isAdmin)
        <div class="card p-5">
            <p class="font-bold text-slate-800 mb-3">Wakili Guru</p>
            <p class="text-xs text-slate-400 -mt-1 mb-3">Pilih guru untuk melihat &amp; membantu mengisi jurnal/absensi atas nama guru tersebut (mis. guru lupa mengisi &amp; sedang berhalangan).</p>
            <form method="GET" action="{{ route('mengajar.index') }}" class="flex flex-wrap items-center gap-2">
                <select name="guru_id" onchange="this.form.submit()" class="input max-w-xs">
                    <option value="">— Pilih Guru —</option>
                    @foreach($guruList as $g)
                        <option value="{{ $g->id }}" {{ $guru && $guru->id === $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="btn-outline">Tampilkan</button></noscript>
            </form>
        </div>
    @endif

    @if($periodeList->count() > 1)
        <div class="card p-5">
            <p class="font-bold text-slate-800 mb-3">Periode</p>
            <div class="flex flex-wrap gap-2">
                @foreach($periodeList as $p)
                    <a href="{{ route('mengajar.index', ['hari' => $hari, 'periode' => $p->id, 'guru_id' => $guru->id ?? null]) }}"
                       class="px-4 py-2 rounded-lg text-sm font-semibold border {{ $tahunAjaran && $p->id === $tahunAjaran->id ? 'bg-brand-600 text-white border-brand-600' : 'border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                        {{ $p->labelSingkat() }}
                        @if($tahunAjaranAktif && $p->id === $tahunAjaranAktif->id)
                            <span class="ml-1 text-xs {{ $tahunAjaran && $p->id === $tahunAjaran->id ? 'text-brand-100' : 'text-emerald-500' }}">(Aktif)</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-3">Pilih Hari</p>
        <div class="flex flex-wrap gap-2">
            @foreach($hariList as $h)
                <a href="{{ route('mengajar.index', ['hari' => $h, 'periode' => $tahunAjaran->id ?? null, 'guru_id' => $guru->id ?? null]) }}"
                   class="px-4 py-2 rounded-lg text-sm font-semibold border {{ $h === $hari ? 'bg-brand-600 text-white border-brand-600' : 'border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                    {{ $h }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-4">
            Jadwal Mengajar - {{ $hari }}
            @if($isAdmin && $guru)
                <span class="text-sm font-normal text-slate-400">&middot; atas nama {{ $guru->name }}</span>
            @endif
        </p>
        <p class="text-xs text-slate-400 -mt-3 mb-4">Jam yang berurutan untuk kelas & mapel yang sama otomatis digabung jadi 1 sesi — cukup isi absensi & jurnal 1x.</p>

        @if($isAdmin && $guru)
            <div class="rounded-xl bg-sky-50 border border-sky-200 text-sky-700 px-4 py-3 text-sm mb-4">
                <i class="fa-solid fa-user-shield mr-1.5"></i> Anda sedang mengisi jurnal/absensi sebagai <strong>Admin</strong>, mewakili guru <strong>{{ $guru->name }}</strong>.
            </div>
        @endif

        @if($tahunAjaran && $tahunAjaranAktif && $tahunAjaran->id !== $tahunAjaranAktif->id)
            <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm mb-4">
                <i class="fa-solid fa-clock-rotate-left mr-1.5"></i> Anda sedang mengisi jurnal/absensi untuk periode <strong>{{ $tahunAjaran->labelPeriode() }}</strong> (bukan periode aktif saat ini: {{ $tahunAjaranAktif->labelPeriode() }}). Periode ini sedang dibuka sementara oleh Admin agar data yang tertinggal bisa dilengkapi.
            </div>
        @endif

        @if($tahunAjaran && $tahunAjaran->isTerkunci())
            <div class="rounded-xl bg-slate-100 border border-slate-200 text-slate-600 px-4 py-3 text-sm mb-4">
                <i class="fa-solid fa-lock mr-1.5"></i> Periode {{ $tahunAjaran->labelPeriode() }} sudah ditutup dan terkunci. Jurnal & absensi pada periode ini hanya dapat dilihat, tidak dapat diisi/diubah.
            </div>
        @endif

        @if($isAdmin && !$guru)
            <p class="text-sm text-slate-400 py-8 text-center">Pilih guru terlebih dahulu di atas untuk melihat jadwalnya.</p>
        @elseif(!$tahunAjaran)
            <p class="text-sm text-amber-600">Tidak ada Tahun Ajaran aktif.</p>
        @elseif($sesiList->isEmpty())
            <p class="text-sm text-slate-400 py-8 text-center">Tidak ada jadwal mengajar pada hari {{ $hari }}.</p>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($sesiList as $sesi)
                <a href="{{ route('mengajar.form', $sesi['ids']) }}"
                   class="border rounded-xl p-4 transition block {{ ($sesi['sudah_diisi'] ?? false) ? 'border-emerald-200 bg-emerald-50/60' : 'border-slate-200 hover:border-brand-400 hover:bg-brand-50/40' }}">
                    <div class="flex items-center justify-between mb-1 gap-2">
                        <p class="text-xs font-bold text-brand-600">
                            @if($sesi['jam_awal']->id === $sesi['jam_akhir']->id)
                                {{ $sesi['jam_awal']->label }}
                            @else
                                Jam ke-{{ $sesi['jam_awal']->jam_ke }} s.d ke-{{ $sesi['jam_akhir']->jam_ke }}
                                ({{ substr($sesi['jam_awal']->jam_mulai, 0, 5) }} - {{ substr($sesi['jam_akhir']->jam_selesai, 0, 5) }})
                            @endif
                        </p>
                        @if($sesi['sudah_diisi'] ?? false)
                            <span class="badge bg-emerald-100 text-emerald-700 shrink-0">Terisi</span>
                        @endif
                    </div>
                    <p class="font-semibold text-slate-800">Kelas {{ $sesi['kelas']->nama_kelas }}</p>
                    <p class="text-sm text-slate-500">{{ $sesi['mapel']->nama_mapel }}</p>
                    @if($sesi['slots']->count() > 1)
                        <p class="text-xs text-slate-400 mt-1">{{ $sesi['slots']->count() }} jam pelajaran &middot; 1x isi absensi</p>
                    @endif
                </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
