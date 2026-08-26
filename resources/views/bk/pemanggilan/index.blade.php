@extends('layouts.app')
@section('title', 'Pemanggilan Orang Tua')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3 no-print">
        <p class="text-sm text-slate-400">Riwayat pemanggilan orang tua/wali.</p>
        <a href="{{ route('bk.pemanggilan.create') }}" class="btn-primary">+ Catat Pemanggilan</a>
    </div>

    <div class="card p-5 no-print">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Kehadiran</label>
                <select name="status" class="input" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="Menunggu Pertemuan" {{ request('status') == 'Menunggu Pertemuan' ? 'selected' : '' }}>Menunggu Pertemuan</option>
                    @foreach(['Hadir','Tidak Hadir'] as $s)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            @if($kelasList->isNotEmpty())
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
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Bulan</label>
                <select name="bulan" class="input" onchange="this.form.submit()">
                    <option value="">Semua Bulan</option>
                    @foreach(range(1,12) as $b)
                        <option value="{{ $b }}" {{ request('bulan') == $b ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($b)->translatedFormat('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 mb-1">Tahun</label>
                <select name="tahun" class="input" onchange="this.form.submit()">
                    <option value="">Semua Tahun</option>
                    @foreach(range(now()->year - 1, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ request('tahun') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="card p-5 print-section" id="print-pemanggilan">
        <x-kop-surat />

        <div class="flex items-center justify-between mb-1 flex-wrap gap-2">
            <p class="font-extrabold text-slate-800 text-lg">Rekap Pemanggilan Orang Tua</p>
            <button type="button" onclick="cetakBagian('print-pemanggilan')" class="btn-outline no-print"><i class="fa-solid fa-print mr-1.5"></i> Cetak / Export PDF</button>
        </div>
        <p class="text-sm text-slate-400 mb-4">
            @if(request('bulan')) Bulan {{ \Carbon\Carbon::create()->month((int) request('bulan'))->translatedFormat('F') }} @endif
            {{ request('tahun') ?: '' }}
            @if(!request('bulan') && !request('tahun')) Seluruh periode (sesuai filter yang dipilih) @endif
        </p>

        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th class="w-10">No</th><th>Tanggal</th><th>Siswa</th><th>Kelas</th><th>Alasan</th><th>Status</th><th>Surat Panggilan</th><th>Petugas</th><th class="th-aksi no-print">Aksi</th></tr></thead>
                <tbody>
                    @forelse($data as $p)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="text-slate-500 whitespace-nowrap">{{ $p->tanggal->translatedFormat('d M Y') }}</td>
                        <td class="font-medium"><a href="{{ route('bk.siswa.show', $p->siswa_id) }}" class="hover:underline">{{ $p->siswa->nama ?? '-' }}</a></td>
                        <td><x-kelas-badge :nama="$p->siswa->kelas->nama_kelas ?? '-'" /></td>
                        <td class="text-slate-500">{{ \Illuminate\Support\Str::limit($p->alasan, 60) }}</td>
                        <td>
                            @if(!$p->sudahAdaHasil())
                                <span class="badge bg-slate-100 text-slate-500"><i class="fa-solid fa-hourglass-half mr-1"></i> Menunggu Pertemuan</span>
                            @else
                                <span class="badge {{ $p->ortu_hadir ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $p->ortu_hadir ? 'Hadir' : 'Tidak Hadir' }}
                                </span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap">
                            @if($p->surat)
                                <a href="{{ route('surat.show', $p->surat) }}" target="_blank" class="text-brand-600 hover:underline text-xs">
                                    <i class="fa-solid fa-envelope mr-1"></i>{{ $p->surat->nomor_surat ?: '(belum ada nomor)' }}
                                </a>
                            @else
                                <span class="text-slate-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="text-slate-500">{{ $p->petugas->name ?? '-' }}</td>
                        <td class="td-aksi no-print">
                            @if(!$p->sudahAdaHasil())
                                <a href="{{ route('bk.pemanggilan.hasil.edit', $p) }}" class="btn-chip btn-chip-edit"><i class="fa-solid fa-pen mr-1.5"></i> Isi Hasil</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-slate-400 py-8">Belum ada pemanggilan orang tua tercatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-blok-tanda-tangan-dua
            jabatan-kanan="Guru BK"
            :nama-kanan="$guruBk->name ?? null"
            :nip-kanan="$guruBk->nip ?? null"
        />
    </div>
</div>
@endsection
