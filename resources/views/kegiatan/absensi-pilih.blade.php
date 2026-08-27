@extends('layouts.app')
@section('title', 'Absensi Kegiatan Sekolah')

@section('content')
@php $user = auth()->user(); @endphp
<div class="space-y-6">

    {{-- Pemilih tanggal --}}
    <x-panel judul="Pilih Tanggal Kegiatan" ikon="fa-calendar-day"
             deskripsi="Kegiatan yang berlangsung pada tanggal ini dan menjadi tanggung jawab Anda akan muncul di bawah.">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="label">Tanggal</label>
                <input type="date" name="tanggal" value="{{ $tanggal }}" class="input" onchange="this.form.submit()">
            </div>
            @if($user->isAdmin())
                <div class="min-w-[200px]">
                    <label class="label">Kelas (Admin mewakili)</label>
                    <select name="kelas_id" class="input" onchange="this.form.submit()">
                        <option value="">Semua kelas</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k->id }}" @selected(request('kelas_id') == $k->id)>{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <button class="btn-outline"><i class="fa-solid fa-magnifying-glass"></i> Tampilkan</button>
            @if($tanggal !== now()->toDateString())
                <a href="{{ route('kegiatan.absensi.pilih') }}" class="btn-ghost">Kembali ke hari ini</a>
            @endif
        </form>
        <p class="text-xs text-slate-400 mt-3">
            {{ $tanggalCarbon->translatedFormat('l, d F Y') }}
            @if($user->role === 'guru' && $user->kelasWali)
                &middot; Anda wali kelas <b>{{ $user->kelasWali->nama_kelas }}</b>
            @endif
        </p>
    </x-panel>

    @if($kelasList->isEmpty())
        <div class="alert alert-warning">
            <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
            <span class="flex-1">
                Absensi kegiatan sekolah hanya dapat diisi oleh <b>Wali Kelas</b>, sedangkan Anda belum
                tercatat sebagai wali kelas mana pun pada periode aktif. Hubungi Kurikulum/Admin bila ini keliru.
            </span>
        </div>
    @elseif($tugasList->isEmpty())
        <div class="card p-10 text-center">
            <div class="text-3xl text-slate-300 mb-2"><i class="fa-solid fa-calendar-xmark"></i></div>
            <p class="font-semibold text-slate-700">Tidak ada kegiatan pada tanggal ini</p>
            <p class="text-sm text-slate-400 mt-1">
                Absensi hari biasa tetap diisi guru mata pelajaran lewat menu Absensi &amp; Jurnal Mengajar.
            </p>
        </div>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($tugasList as $t)
                @php $k = $t['kegiatan']; @endphp
                <div class="card p-5 flex flex-col gap-3 {{ $t['sudah_diisi'] ? 'border-emerald-200' : '' }}">
                    <div class="flex items-start justify-between gap-2">
                        <span class="badge bg-brand-50 text-brand-700">{{ $k->jenisLabel() }}</span>
                        @if($t['sudah_diisi'])
                            <span class="badge bg-emerald-100 text-emerald-700"><i class="fa-solid fa-check mr-1"></i> Terisi</span>
                        @else
                            <span class="badge bg-amber-100 text-amber-700">Belum diisi</span>
                        @endif
                    </div>

                    <div>
                        <p class="font-bold text-slate-800 leading-tight">{{ $k->nama }}</p>
                        <p class="text-sm text-slate-500">Kelas {{ $t['kelas']->nama_kelas }}</p>
                        @if($k->keterangan)
                            <p class="text-xs text-slate-400 mt-1">{{ \Illuminate\Support\Str::limit($k->keterangan, 90) }}</p>
                        @endif
                    </div>

                    @if($t['sudah_diisi'])
                        <div class="flex flex-wrap gap-1.5 text-xs">
                            <span class="badge bg-emerald-50 text-emerald-700">H {{ $t['absensi']->jumlah_hadir }}</span>
                            <span class="badge bg-amber-50 text-amber-700">S {{ $t['absensi']->jumlah_sakit }}</span>
                            <span class="badge bg-sky-50 text-sky-700">I {{ $t['absensi']->jumlah_izin }}</span>
                            <span class="badge bg-rose-50 text-rose-700">A {{ $t['absensi']->jumlah_alfa }}</span>
                        </div>
                    @endif

                    @unless($k->kirim_wa_alfa)
                        <p class="text-xs text-slate-400"><i class="fa-solid fa-comment-slash mr-1"></i> Tanpa notifikasi WhatsApp</p>
                    @endunless

                    <a href="{{ route('kegiatan.absensi.form', ['kegiatan' => $k, 'kelas' => $t['kelas'], 'tanggal' => $tanggal]) }}"
                       class="{{ $t['sudah_diisi'] ? 'btn-outline' : 'btn-primary' }} mt-auto">
                        <i class="fa-solid {{ $t['sudah_diisi'] ? 'fa-pen' : 'fa-clipboard-check' }}"></i>
                        {{ $t['sudah_diisi'] ? 'Ubah Absensi' : 'Isi Absensi' }}
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    @if($kegiatanMendatang->isNotEmpty())
        <x-panel judul="Kegiatan Berikutnya" ikon="fa-calendar-plus" deskripsi="Agenda yang sudah dijadwalkan sekolah." rapat>
            <div class="overflow-x-auto">
                <table class="table-clean">
                    <thead><tr><th>Kegiatan</th><th>Jenis</th><th>Tanggal</th><th>Sasaran</th></tr></thead>
                    <tbody>
                        @foreach($kegiatanMendatang as $k)
                        <tr>
                            <td class="font-medium text-slate-800">{{ $k->nama }}</td>
                            <td><span class="badge bg-slate-100 text-slate-600">{{ $k->jenisLabel() }}</span></td>
                            <td class="text-slate-500 whitespace-nowrap">{{ $k->rentangLabel() }}</td>
                            <td class="text-slate-500">{{ $k->cakupanLabel() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-panel>
    @endif
</div>
@endsection
