@extends('layouts.app')
@section('title', 'Notifikasi WhatsApp Ortu')

@section('content')
<div class="space-y-6">
    <div class="card p-5">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Bulan</label>
                <select name="bulan" class="input" onchange="this.form.submit()">
                    @foreach(range(1,12) as $b)
                        <option value="{{ $b }}" {{ $b === $bulan ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($b)->translatedFormat('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tahun</label>
                <select name="tahun" class="input" onchange="this.form.submit()">
                    @foreach(range(now()->year - 1, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ $y === $tahun ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            @if($bisaFilterKelas)
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Kelas</label>
                <select name="kelas_id" class="input" onchange="this.form.submit()">
                    <option value="">Semua Kelas</option>
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </form>
        <p class="text-xs text-slate-400 mt-3">
            <i class="fa-solid fa-calendar-days mr-1.5"></i> Hari ini: <b class="text-slate-500">{{ now()->translatedFormat('l, d F Y') }}</b>
            &middot; bulan &amp; tahun di atas otomatis mengikuti tanggal server saat halaman ini dibuka.
        </p>
    </div>

    @if($tanpaAksesData)
        <div class="rounded-xl bg-slate-50 border border-slate-200 text-slate-500 px-5 py-6 text-sm text-center">
            <i class="fa-solid fa-mobile-screen mr-1.5"></i> Menu ini menampilkan histori notifikasi WA per KELAS (bukan per mapel), jadi hanya relevan untuk
            <b>Wali Kelas</b> atau <b>Guru BK</b> yang sudah di-mapping ke kelas. Anda saat ini belum ditetapkan
            sebagai wali kelas manapun / belum di-mapping ke kelas manapun oleh Kurikulum.
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <x-stat-card color="emerald" icon="fa-circle-check" label="Terkirim" :value="$ringkasan['terkirim']" />
            <x-stat-card color="amber" icon="fa-hourglass-half" label="Menunggu Diproses" :value="$ringkasan['pending']" />
            <x-stat-card color="rose" icon="fa-triangle-exclamation" label="Gagal Terkirim" :value="$ringkasan['gagal']" />
            <x-stat-card color="sky" icon="fa-forward" label="Dilewati (Isi Terlambat)" :value="$ringkasan['dilewati']" />
        </div>

        @if($ringkasan['dilewati'] > 0)
            <div class="rounded-xl bg-sky-50 border border-sky-200 text-sky-700 px-4 py-3 text-sm">
                <i class="fa-solid fa-forward mr-1.5"></i> Ada {{ $ringkasan['dilewati'] }} kejadian Alfa yang tercatat tapi WA sengaja TIDAK dikirim, karena jurnal/absensinya baru diisi setelah tanggal kejadian lewat (bukan hari yang sama).
            </div>
        @endif

        @if($ringkasan['pending'] > 0)
            <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 text-sm">
                <i class="fa-solid fa-hourglass-half mr-1.5"></i> Ada {{ $ringkasan['pending'] }} notifikasi yang masih menunggu diproses queue worker.
                Pastikan <code class="bg-amber-100 px-1 rounded">php artisan queue:work</code> sedang berjalan di server.
            </div>
        @endif
        @if($ringkasan['gagal'] > 0)
            <div class="rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm">
                <i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Ada {{ $ringkasan['gagal'] }} notifikasi gagal terkirim — kemungkinan nomor WA ortu kosong/salah format,
                atau gateway WA sedang bermasalah.
            </div>
        @endif

        <div class="card p-5">
            <p class="font-bold text-slate-800 mb-1">Histori Notifikasi WA — {{ \Carbon\Carbon::create()->month($bulan)->translatedFormat('F') }} {{ $tahun }}</p>
            @if($kelasWali)
                <p class="text-sm text-slate-400 mb-4">Menampilkan siswa kelas {{ $kelasWali->nama_kelas }} (kelas wali Anda).</p>
            @elseif($kelasBkList->isNotEmpty())
                <p class="text-sm text-slate-400 mb-4">Menampilkan siswa kelas: {{ $kelasBkList->pluck('nama_kelas')->join(', ') }} (kelas mapping BK Anda).</p>
            @else
                <p class="text-sm text-slate-400 mb-4">Diurutkan dari tanggal terbaru.</p>
            @endif

            <div class="overflow-x-auto -mx-5">
                <table class="table-clean w-full">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Menurut Mapel</th>
                            <th>Status</th>
                            <th>Waktu Terkirim</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $n)
                        <tr>
                            <td class="text-slate-500 whitespace-nowrap">{{ $n->tanggal->translatedFormat('d M Y') }}</td>
                            <td class="font-medium">
                                <div class="flex items-center gap-2">
                                    <x-initial-avatar :nama="$n->siswa->nama ?? '-'" />
                                    {{ $n->siswa->nama ?? '(siswa dihapus)' }}
                                </div>
                            </td>
                            <td><x-kelas-badge :nama="$n->siswa->kelas->nama_kelas ?? '-'" /></td>
                            <td class="text-slate-500">
                                {{ $n->mapel->nama_mapel ?? '-' }}
                                @if($n->jam_ke) <span class="text-slate-400">(jam ke-{{ $n->jam_ke }})</span> @endif
                            </td>
                            <td>
                                @if($n->status_kirim === 'terkirim')
                                    <span class="badge bg-emerald-50 text-emerald-700"><i class="fa-solid fa-circle-check mr-1.5"></i> Terkirim</span>
                                @elseif($n->status_kirim === 'gagal')
                                    <span class="badge bg-rose-50 text-rose-700"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i> Gagal</span>
                                @elseif($n->status_kirim === 'dilewati')
                                    <span class="badge bg-sky-50 text-sky-700" title="{{ $n->keterangan_gagal }}"><i class="fa-solid fa-forward mr-1.5"></i> Dilewati (Isi Terlambat)</span>
                                @else
                                    <span class="badge bg-amber-50 text-amber-700"><i class="fa-solid fa-hourglass-half mr-1.5"></i> Menunggu</span>
                                @endif
                            </td>
                            <td class="text-slate-500 whitespace-nowrap">
                                {{ $n->dikirim_at ? $n->dikirim_at->translatedFormat('d M Y, H:i') : '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-emerald-600 py-8"><i class="fa-solid fa-circle-check mr-1.5"></i> Tidak ada notifikasi Alfa bulan ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $data->links() }}</div>
        </div>
    @endif
</div>
@endsection
