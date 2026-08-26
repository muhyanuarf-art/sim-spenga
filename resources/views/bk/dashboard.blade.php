@extends('layouts.app')
@section('title', 'Pantau Pelanggaran')

@section('content')
<div class="space-y-6">
    <div>
        <p class="text-xl font-extrabold text-slate-800">Dashboard Pelanggaran</p>
        <p class="text-sm text-slate-500">Pantau kasus, pembinaan, dan perkembangan perilaku siswa.</p>
    </div>

    @php
        $deltaKasus = $totalKasusBulanLalu > 0
            ? round((($totalKasusBulanIni - $totalKasusBulanLalu) / $totalKasusBulanLalu) * 100)
            : ($totalKasusBulanIni > 0 ? 100 : 0);
        $kasusTurun = $totalKasusBulanIni <= $totalKasusBulanLalu;
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-5">
            <div class="w-10 h-10 rounded-xl bg-rose-500 text-white flex items-center justify-center mb-3"><i class="fa-solid fa-folder-open"></i></div>
            <p class="text-xs font-bold text-rose-600 uppercase tracking-wide">Total Kasus Bulan Ini</p>
            <p class="text-2xl font-extrabold text-slate-800">{{ $totalKasusBulanIni }}</p>
            <p class="text-xs text-slate-400 mt-0.5">Dari {{ $totalKasusBulanLalu }} kasus bulan lalu
                @if($totalKasusBulanLalu > 0)
                    <span class="{{ $kasusTurun ? 'text-emerald-600' : 'text-red-600' }} font-semibold">
                        {{ $kasusTurun ? '↓' : '↑' }} {{ abs($deltaKasus) }}%
                    </span>
                @endif
            </p>
        </div>
        <div class="card p-5">
            <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center mb-3"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <p class="text-xs font-bold text-amber-600 uppercase tracking-wide">Siswa Kasus Aktif</p>
            <p class="text-2xl font-extrabold text-slate-800">{{ $siswaKasusAktifIds->count() }}</p>
            <p class="text-xs text-slate-400 mt-0.5">Kondisi saat ini</p>
        </div>
        <div class="card p-5">
            <div class="w-10 h-10 rounded-xl bg-violet-500 text-white flex items-center justify-center mb-3"><i class="fa-solid fa-handshake"></i></div>
            <p class="text-xs font-bold text-violet-600 uppercase tracking-wide">Sedang Dalam Pembinaan</p>
            <p class="text-2xl font-extrabold text-slate-800">{{ $siswaDalamPembinaan->count() }}</p>
            <p class="text-xs text-slate-400 mt-0.5">Kondisi saat ini</p>
        </div>
        <div class="card p-5">
            <div class="w-10 h-10 rounded-xl bg-sky-500 text-white flex items-center justify-center mb-3"><i class="fa-solid fa-phone"></i></div>
            <p class="text-xs font-bold text-sky-600 uppercase tracking-wide">Perlu Pemanggilan Ortu</p>
            <p class="text-2xl font-extrabold text-slate-800">{{ $butuhPemanggilanOrtu->count() }}</p>
            <p class="text-xs text-slate-400 mt-0.5">Kondisi saat ini</p>
        </div>
    </div>

    @php $totalPembinaanAktif = array_sum($sebaranTahap->toArray()) ?: 1; @endphp
    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-1">Sebaran Tahap Pembinaan (Siswa Aktif)</p>
        <div class="grid grid-cols-3 sm:grid-cols-6 gap-3 mt-3">
            @for($t = 1; $t <= 6; $t++)
                @php $jml = $sebaranTahap[$t] ?? 0; $pct = round($jml / $totalPembinaanAktif * 100, 1); @endphp
                <div class="rounded-xl bg-slate-50 border border-slate-100 p-3 text-center">
                    <p class="text-xs font-semibold text-slate-500">Tahap {{ $t }}</p>
                    <p class="text-xl font-extrabold text-slate-700 mt-1">{{ $jml }}</p>
                    <p class="text-xs text-slate-400 mb-2">{{ $pct }}%</p>
                    <div class="w-full h-1.5 rounded-full bg-slate-200 overflow-hidden">
                        <div class="h-full bg-brand-500" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endfor
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Grafik Tren Kasus — SVG polos, tanpa library JS. --}}
            <div class="card p-5">
                <p class="font-bold text-slate-800 mb-4">Grafik Tren Kasus (6 Bulan Terakhir)</p>
                <div class="flex items-center gap-3 mb-3 text-xs flex-wrap">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Total Kasus</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Kasus Aktif</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Selesai</span>
                </div>
                @php
                    $maxNilai = max(1, $statistikTren->flatMap(fn ($b) => [$b['total'], $b['aktif'], $b['selesai']])->max());
                    $lebar = 600; $tinggi = 160; $n = $statistikTren->count();
                    $jarakX = $n > 1 ? $lebar / ($n - 1) : $lebar;
                    $titik = function (string $kunci) use ($statistikTren, $maxNilai, $tinggi, $jarakX) {
                        return $statistikTren->values()->map(fn ($b, $i) => round($i * $jarakX, 1) . ',' . round($tinggi - ($b[$kunci] / $maxNilai) * $tinggi, 1))->implode(' ');
                    };
                @endphp
                <svg viewBox="0 0 {{ $lebar }} {{ $tinggi + 20 }}" class="w-full" preserveAspectRatio="none" style="height:180px">
                    <polyline points="{{ $titik('total') }}" fill="none" stroke="#f43f5e" stroke-width="2.5" />
                    <polyline points="{{ $titik('aktif') }}" fill="none" stroke="#f59e0b" stroke-width="2.5" />
                    <polyline points="{{ $titik('selesai') }}" fill="none" stroke="#10b981" stroke-width="2.5" />
                </svg>
                <div class="flex justify-between text-xs text-slate-400 mt-1">
                    @foreach($statistikTren as $b)<span>{{ $b['label'] }}</span>@endforeach
                </div>
            </div>

            <div class="card p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="font-bold text-slate-800">Kasus Terbaru</p>
                    <a href="{{ route('bk.kasus.index') }}" class="text-xs text-brand-600 font-semibold">Lihat Semua</a>
                </div>
                <div class="overflow-x-auto -mx-5">
                    <table class="table-clean w-full">
                        <thead><tr><th>Tanggal</th><th>Siswa</th><th>Jenis Pelanggaran</th><th>Poin</th><th>Tahap</th><th>Status</th><th class="th-aksi">Tindakan</th></tr></thead>
                        <tbody>
                        @forelse($kasusTerbaru as $k)
                            <tr>
                                <td class="text-slate-500 whitespace-nowrap">{{ $k->tanggal_kejadian->translatedFormat('d M Y') }}</td>
                                <td>
                                    <p class="font-medium">{{ $k->siswa->nama ?? '-' }}</p>
                                    <p class="text-xs text-slate-400">{{ $k->siswa->kelas->nama_kelas ?? '-' }}</p>
                                </td>
                                <td>{{ $k->nama_pelanggaran }}</td>
                                <td class="font-semibold">{{ $k->poin }}</td>
                                <td>
                                    @if($k->pembinaanTerbaru)
                                        <span class="badge bg-emerald-50 text-emerald-700">Tahap {{ $k->pembinaanTerbaru->tahap }}</span>
                                    @else
                                        <span class="text-slate-400 text-xs">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge
                                        @if($k->status === 'Selesai') bg-emerald-50 text-emerald-700
                                        @elseif($k->status === 'Diproses') bg-amber-50 text-amber-700
                                        @else bg-slate-100 text-slate-500
                                        @endif">{{ $k->status }}</span>
                                </td>
                                <td class="td-aksi">
                                    <a href="{{ route('bk.siswa.show', $k->siswa) }}" class="btn-chip btn-chip-edit"><i class="fa-solid fa-eye"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-slate-400 py-8">Belum ada kasus tercatat.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card p-5">
                <div class="flex items-center justify-between mb-1">
                    <p class="font-bold text-slate-800"><i class="fa-solid fa-circle mr-1.5"></i> Siswa dengan Poin Tertinggi</p>
                    <a href="{{ route('bk.siswa.index') }}" class="text-xs text-brand-600 font-semibold">Lihat Semua</a>
                </div>
                <div class="space-y-2 mt-3">
                    @forelse($siswaPoinTertinggi->take(5) as $r)
                    <a href="{{ route('bk.siswa.show', $r['siswa']) }}" class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50">
                        <div class="flex items-center gap-2">
                            <x-initial-avatar :nama="$r['siswa']->nama" />
                            <div>
                                <p class="font-medium text-sm text-slate-800">{{ $r['siswa']->nama }}</p>
                                <p class="text-xs text-slate-400">{{ $r['siswa']->kelas->nama_kelas ?? '-' }}</p>
                            </div>
                        </div>
                        <span class="badge bg-rose-50 text-rose-700 font-bold">{{ $r['poin_aktif'] }} poin</span>
                    </a>
                    @empty
                    <p class="text-sm text-slate-400 text-center py-4">Tidak ada data.</p>
                    @endforelse
                </div>
            </div>

            <div class="card p-5">
                <p class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-phone mr-1.5"></i> Rekomendasi Pemanggilan Orang Tua</p>
                <p class="text-xs text-slate-400 mb-3">Siswa dengan rekomendasi tahap 4-5 (poin aktif &ge;51).</p>
                <div class="space-y-2">
                    @forelse($butuhPemanggilanOrtu as $r)
                    <a href="{{ route('bk.siswa.show', $r['siswa']) }}" class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50">
                        <div class="flex items-center gap-2">
                            <x-initial-avatar :nama="$r['siswa']->nama" />
                            <div>
                                <p class="font-medium text-sm text-slate-800">{{ $r['siswa']->nama }}</p>
                                <p class="text-xs text-slate-400">{{ $r['siswa']->kelas->nama_kelas ?? '-' }}</p>
                            </div>
                        </div>
                        <span class="badge bg-amber-50 text-amber-700 font-bold">Tahap {{ $r['rekomendasi_tahap'] }}</span>
                    </a>
                    @empty
                    <p class="text-sm text-emerald-600 text-center py-4"><i class="fa-solid fa-circle-check mr-1.5"></i> Tidak ada rekomendasi saat ini.</p>
                    @endforelse
                </div>
            </div>

            <div class="card p-5">
                <p class="font-bold text-slate-800 mb-3"><i class="fa-solid fa-clipboard-list mr-1.5"></i> Kasus Belum Selesai</p>
                <div class="space-y-2">
                    @forelse($siswaKasusBelumSelesai as $r)
                    <a href="{{ route('bk.siswa.show', $r['siswa']) }}" class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50">
                        <div class="flex items-center gap-2">
                            <x-initial-avatar :nama="$r['siswa']->nama" />
                            <p class="font-medium text-sm text-slate-800">{{ $r['siswa']->nama }}</p>
                        </div>
                        <span class="badge bg-slate-100 text-slate-600">{{ $r['jumlah_kasus'] }} kasus</span>
                    </a>
                    @empty
                    <p class="text-sm text-emerald-600 text-center py-4"><i class="fa-solid fa-circle-check mr-1.5"></i> Tidak ada kasus yang belum selesai.</p>
                    @endforelse
                </div>
            </div>

            <div class="card p-5">
                <p class="font-bold text-slate-800 mb-3">Menu Cepat</p>
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('bk.kasus.create') }}" class="flex flex-col items-center justify-center gap-1.5 py-4 rounded-xl bg-blue-50 hover:bg-blue-100 transition">
                        <i class="fa-solid fa-file-pen text-blue-600"></i>
                        <span class="text-xs font-semibold text-blue-700">Tambah Kasus</span>
                    </a>
                    <a href="{{ route('bk.pemanggilan.index') }}" class="flex flex-col items-center justify-center gap-1.5 py-4 rounded-xl bg-emerald-50 hover:bg-emerald-100 transition">
                        <i class="fa-solid fa-phone text-emerald-600"></i>
                        <span class="text-xs font-semibold text-emerald-700">Panggilan Ortu</span>
                    </a>
                    <a href="{{ route('bk.siswa.index') }}" class="flex flex-col items-center justify-center gap-1.5 py-4 rounded-xl bg-violet-50 hover:bg-violet-100 transition">
                        <i class="fa-solid fa-user-group text-violet-600"></i>
                        <span class="text-xs font-semibold text-violet-700">Siswa Aktif</span>
                    </a>
                    <a href="{{ route('bk.kasus.index') }}" class="flex flex-col items-center justify-center gap-1.5 py-4 rounded-xl bg-amber-50 hover:bg-amber-100 transition">
                        <i class="fa-solid fa-file-lines text-amber-600"></i>
                        <span class="text-xs font-semibold text-amber-700">Laporan Bulanan</span>
                    </a>
                </div>
            </div>

            <div class="card p-5">
                <p class="font-bold text-slate-800 mb-1"><i class="fa-solid fa-seedling mr-1.5"></i> Menunjukkan Perbaikan (30 hari terakhir)</p>
                <div class="space-y-2 mt-3">
                    @forelse($siswaMembaik as $r)
                    <a href="{{ route('bk.siswa.show', $r['siswa']) }}" class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50">
                        <div class="flex items-center gap-2">
                            <x-initial-avatar :nama="$r['siswa']->nama" />
                            <p class="font-medium text-sm text-slate-800">{{ $r['siswa']->nama }}</p>
                        </div>
                        <span class="badge bg-emerald-50 text-emerald-700">Poin aktif: {{ $r['poin_aktif'] }}</span>
                    </a>
                    @empty
                    <p class="text-sm text-slate-400 text-center py-4">Belum ada data.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
