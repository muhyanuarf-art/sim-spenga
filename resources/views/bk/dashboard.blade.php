@extends('layouts.app')
@section('title', 'Pantau Pelanggaran')

@section('content')
<div class="space-y-6">
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-rose-600 via-rose-600 to-orange-500 text-white px-5 py-4 shadow-lg shadow-rose-500/20">
        <p class="font-bold flex items-center gap-2">🧭 Pantau Pelanggaran</p>
        <p class="text-sm text-white/80">Pantau kasus, pembinaan, dan perkembangan perilaku siswa.</p>
        <div class="absolute -right-6 -bottom-10 w-40 h-40 rounded-full bg-white/10 blur-2xl"></div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <x-stat-card color="rose" icon="📁" label="Total Kasus Bulan Ini" :value="$totalKasusBulanIni" />
        <x-stat-card color="amber" icon="⚠️" label="Siswa Kasus Aktif" :value="$siswaKasusAktifIds->count()" />
        <x-stat-card color="violet" icon="🤝" label="Sedang Dalam Pembinaan" :value="$siswaDalamPembinaan->count()" />
        <x-stat-card color="sky" icon="📞" label="Perlu Pemanggilan Ortu" :value="$butuhPemanggilanOrtu->count()" />
    </div>

    <div class="card p-5">
        <p class="font-bold text-slate-800 mb-4">Sebaran Tahap Pembinaan (siswa aktif)</p>
        <div class="grid grid-cols-3 sm:grid-cols-6 gap-3">
            @for($t = 1; $t <= 6; $t++)
            <div class="rounded-xl bg-slate-50 border border-slate-100 p-3 text-center">
                <p class="text-xl font-extrabold text-slate-700">{{ $sebaranTahap[$t] ?? 0 }}</p>
                <p class="text-xs text-slate-400">Tahap {{ $t }}</p>
            </div>
            @endfor
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="card p-5">
            <p class="font-bold text-slate-800 mb-1">🔴 Poin Aktif Tertinggi</p>
            <p class="text-xs text-slate-400 mb-4">Perlu perhatian lebih.</p>
            <div class="space-y-2">
                @forelse($siswaPoinTertinggi as $r)
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
            <p class="font-bold text-slate-800 mb-1">📞 Rekomendasi Pemanggilan Orang Tua</p>
            <p class="text-xs text-slate-400 mb-4">Siswa dengan rekomendasi tahap 4-5 (poin aktif ≥51).</p>
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
                <p class="text-sm text-emerald-600 text-center py-4">🎉 Tidak ada.</p>
                @endforelse
            </div>
        </div>

        <div class="card p-5">
            <p class="font-bold text-slate-800 mb-1">📋 Kasus Belum Selesai</p>
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
                <p class="text-sm text-emerald-600 text-center py-4">🎉 Tidak ada.</p>
                @endforelse
            </div>
        </div>

        <div class="card p-5">
            <p class="font-bold text-slate-800 mb-1">🌱 Menunjukkan Perbaikan (30 hari terakhir)</p>
            <div class="space-y-2">
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
@endsection
