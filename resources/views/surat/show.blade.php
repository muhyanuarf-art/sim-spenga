@extends('layouts.app')
@section('title', 'Surat — ' . ($surat->siswa->nama ?? '-'))

@section('content')
<div class="space-y-6">
    <div class="card p-5 no-print flex flex-wrap items-center justify-between gap-3">
        @if(in_array(auth()->user()->role, ['kesiswaan', 'guru_bk', 'admin']))
            <a href="{{ route('surat.index') }}" class="btn-outline">&larr; Kembali ke Daftar Surat</a>
        @else
            <a href="{{ route('disposisi.index') }}" class="btn-outline">&larr; Kembali ke Disposisi Masuk</a>
        @endif
        <div class="flex gap-2">
            @if(in_array(auth()->user()->role, ['kesiswaan', 'guru_bk', 'admin']))
                <a href="{{ route('surat.edit', $surat) }}" class="btn-outline"><i class="fa-solid fa-pen mr-1.5"></i> Edit</a>
            @endif
            <button type="button" onclick="cetakBagian('print-surat')" class="btn-primary"><i class="fa-solid fa-print mr-1.5"></i> Cetak / Export PDF</button>
            @if(in_array(auth()->user()->role, ['kesiswaan', 'guru_bk', 'admin']))
                <form method="POST" action="{{ route('surat.destroy', $surat) }}" onsubmit="return confirm('Hapus surat ini?')">
                    @csrf @method('DELETE')
                    <button class="btn-chip btn-chip-delete"><i class="fa-solid fa-trash mr-1.5"></i> Hapus</button>
                </form>
            @endif
        </div>
    </div>

    <div class="card p-8 print-section max-w-3xl mx-auto" id="print-surat">
        <x-kop-surat />

        <div class="flex justify-between items-start mb-6 text-sm">
            <div>
                <p><span class="text-slate-500">Nomor</span> : {{ $surat->nomor_surat ?: '-' }}</p>
                <p><span class="text-slate-500">Perihal</span> : {{ $surat->jenisSurat->nama_jenis ?? '-' }}</p>
            </div>
            <p>{{ $surat->tanggal->translatedFormat('d F Y') }}</p>
        </div>

        <div class="mb-6 text-sm">
            <p>Kepada Yth.</p>
            <p class="font-semibold">Orang Tua/Wali dari {{ $surat->siswa->nama ?? '-' }}</p>
            <p class="text-slate-500">Kelas {{ $surat->siswa->kelas->nama_kelas ?? '-' }}</p>
        </div>

        <div class="text-sm leading-relaxed whitespace-pre-line mb-4">{{ $surat->isi }}</div>

        <x-blok-tanda-tangan
            :jabatan="$surat->dibuatOleh->roleLabel() ?? '-'"
            :nama="$surat->dibuatOleh->name ?? null"
            :nip="$surat->dibuatOleh->nip ?? null"
        />
    </div>

    @if($surat->keterangan)
        <div class="card p-5 no-print">
            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Keterangan (internal, tidak ikut tercetak)</p>
            <p class="text-sm text-slate-600">{{ $surat->keterangan }}</p>
        </div>
    @endif

    <div class="card p-5 no-print">
        <p class="font-bold text-slate-800 mb-3 text-sm">Lampiran</p>
        <div class="space-y-2 mb-3">
            @forelse($surat->attachments as $a)
                <div class="flex items-center justify-between border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    <a href="{{ $a->url() }}" target="_blank" class="text-brand-600 font-medium truncate">
                        <i class="fa-solid fa-paperclip mr-1.5"></i>{{ $a->nama_file }}
                    </a>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-xs text-slate-400">{{ $a->ukuranReadable() }} &middot; {{ $a->user->name ?? '-' }}</span>
                        @if(in_array(auth()->user()->role, ['kesiswaan', 'guru_bk', 'admin']))
                        <form method="POST" action="{{ route('surat.lampiran.destroy', $a) }}" onsubmit="return confirm('Hapus lampiran ini?')">
                            @csrf @method('DELETE')
                            <button class="text-red-500"><i class="fa-solid fa-trash"></i></button>
                        </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-xs text-slate-400">Belum ada lampiran.</p>
            @endforelse
        </div>

        @if(in_array(auth()->user()->role, ['kesiswaan', 'guru_bk', 'admin']))
        <form method="POST" action="{{ route('surat.lampiran.store', $surat) }}" enctype="multipart/form-data" class="flex gap-2">
            @csrf
            <input type="file" name="file" required class="input flex-1" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
            <button type="submit" class="btn-outline shrink-0">Unggah</button>
        </form>
        <p class="text-xs text-slate-400 mt-1">JPG/PNG/PDF/DOC/DOCX, maks 5MB.</p>
        @endif
    </div>

    @if(in_array(auth()->user()->role, ['kesiswaan', 'guru_bk', 'admin']))
    <div class="card p-5 no-print">
        <p class="font-bold text-slate-800 mb-3 text-sm">Kirim Disposisi</p>
        <form method="POST" action="{{ route('surat.disposisi.store', $surat) }}" class="grid sm:grid-cols-3 gap-3 items-end">
            @csrf
            <div class="sm:col-span-1">
                <label class="block text-xs font-semibold text-slate-500 mb-1">Kepada</label>
                <select name="kepada_user_id" required class="input">
                    <option value="">— Pilih penerima —</option>
                    @foreach($calonPenerima as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->roleLabel() }})</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-1">
                <label class="block text-xs font-semibold text-slate-500 mb-1">Batas Waktu <span class="text-slate-400 font-normal">(opsional)</span></label>
                <input type="date" name="batas_waktu" class="input">
            </div>
            <div class="sm:col-span-1">
                <button type="submit" class="btn-primary h-[38px] w-full">Kirim Disposisi</button>
            </div>
            <div class="sm:col-span-3">
                <label class="block text-xs font-semibold text-slate-500 mb-1">Instruksi <span class="text-slate-400 font-normal">(opsional)</span></label>
                <input type="text" name="instruksi" placeholder="Contoh: Mohon ditindaklanjuti dan dilaporkan kembali." class="input">
            </div>
        </form>
    </div>
    @endif

    <div class="card p-5 no-print">
        <p class="font-bold text-slate-800 mb-3 text-sm">Riwayat Disposisi</p>
        <div class="space-y-2">
            @forelse($surat->disposisi as $d)
                <div class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <p><span class="font-semibold">{{ $d->dariUser->name ?? '-' }}</span> &rarr; <span class="font-semibold">{{ $d->kepadaUser->name ?? '-' }}</span></p>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                            @if($d->status === 'menunggu') bg-slate-100 text-slate-500
                            @elseif($d->status === 'dibaca') bg-blue-50 text-blue-700
                            @elseif($d->status === 'diproses') bg-amber-50 text-amber-700
                            @elseif($d->status === 'selesai') bg-emerald-50 text-emerald-700
                            @else bg-red-50 text-red-700
                            @endif">
                            {{ ucfirst($d->status) }}
                        </span>
                    </div>
                    @if($d->instruksi)<p class="text-slate-600 mt-1">{{ $d->instruksi }}</p>@endif
                    @if($d->batas_waktu)<p class="text-xs text-slate-400 mt-0.5">Batas waktu: {{ $d->batas_waktu->translatedFormat('d M Y') }}</p>@endif
                    @if($d->catatan_penyelesaian)<p class="text-xs text-slate-500 mt-1 italic">Catatan: {{ $d->catatan_penyelesaian }}</p>@endif
                    <p class="text-xs text-slate-400 mt-1">{{ $d->created_at->translatedFormat('d M Y, H:i') }}</p>
                </div>
            @empty
                <p class="text-xs text-slate-400">Belum ada disposisi untuk surat ini.</p>
            @endforelse
        </div>
    </div>

    <div class="card p-5 no-print">
        <p class="font-bold text-slate-800 mb-3 text-sm">Riwayat Aktivitas</p>
        <div class="space-y-3">
            @forelse($surat->activities as $act)
                <div class="flex gap-3 text-sm">
                    <div class="w-1.5 h-1.5 rounded-full bg-brand-500 mt-1.5 shrink-0"></div>
                    <div>
                        <p><span class="font-semibold">{{ $act->aktivitas }}</span> — {{ $act->user->name ?? 'Sistem' }}</p>
                        @if($act->keterangan)<p class="text-slate-500">{{ $act->keterangan }}</p>@endif
                        <p class="text-xs text-slate-400">{{ $act->created_at->translatedFormat('d M Y, H:i') }}</p>
                    </div>
                </div>
            @empty
                <p class="text-xs text-slate-400">Belum ada riwayat aktivitas.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
