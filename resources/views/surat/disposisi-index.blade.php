@extends('layouts.app')
@section('title', 'Disposisi Masuk')

@section('content')
<div class="space-y-6">
    <p class="text-sm text-slate-500">Surat yang didisposisikan/diteruskan kepada Anda.</p>

    <div class="space-y-3">
        @forelse($disposisi as $d)
        <div class="card p-5" x-data="{ tindak: false }">
            <div class="flex items-start justify-between gap-3 flex-wrap">
                <div>
                    <p class="font-bold text-slate-800">{{ $d->surat->jenisSurat->nama_jenis ?? '-' }}</p>
                    <p class="text-sm text-slate-500">Siswa: {{ $d->surat->siswa->nama ?? '-' }} &middot; Nomor: {{ $d->surat->nomor_surat ?: '-' }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">Dari {{ $d->dariUser->name ?? '-' }} &middot; {{ $d->created_at->translatedFormat('d M Y, H:i') }}</p>
                    @if($d->instruksi)<p class="text-sm text-slate-600 mt-2">{{ $d->instruksi }}</p>@endif
                    @if($d->batas_waktu)<p class="text-xs text-amber-600 mt-1"><i class="fa-solid fa-clock mr-1"></i> Batas waktu: {{ $d->batas_waktu->translatedFormat('d M Y') }}</p>@endif
                </div>
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold shrink-0
                    @if($d->status === 'menunggu') bg-slate-100 text-slate-500
                    @elseif($d->status === 'dibaca') bg-blue-50 text-blue-700
                    @elseif($d->status === 'diproses') bg-amber-50 text-amber-700
                    @elseif($d->status === 'selesai') bg-emerald-50 text-emerald-700
                    @else bg-red-50 text-red-700
                    @endif">
                    {{ ucfirst($d->status) }}
                </span>
            </div>

            <div class="flex flex-wrap gap-2 mt-4">
                <a href="{{ route('surat.show', $d->surat) }}" class="btn-outline"><i class="fa-solid fa-eye mr-1.5"></i> Lihat Surat</a>
                @if($d->status === 'menunggu')
                    <form method="POST" action="{{ route('disposisi.baca', $d) }}">
                        @csrf @method('PATCH')
                        <button class="btn-outline"><i class="fa-solid fa-check mr-1.5"></i> Tandai Dibaca</button>
                    </form>
                @endif
                @if(!in_array($d->status, ['selesai', 'ditolak']))
                    <button type="button" @click="tindak = !tindak" class="btn-primary"><i class="fa-solid fa-reply mr-1.5"></i> Tindak Lanjut</button>
                @endif
            </div>

            <div x-show="tindak" x-cloak class="mt-4 border-t border-slate-100 pt-4">
                <form method="POST" action="{{ route('disposisi.tindak-lanjut', $d) }}" class="space-y-3">
                    @csrf @method('PATCH')
                    <select name="status" required class="input">
                        <option value="diproses">Sedang Diproses</option>
                        <option value="selesai">Selesai</option>
                        <option value="ditolak">Ditolak</option>
                    </select>
                    <textarea name="catatan_penyelesaian" rows="2" placeholder="Catatan (opsional)..." class="input"></textarea>
                    <button type="submit" class="btn-primary h-[38px]">Simpan</button>
                </form>
            </div>
        </div>
        @empty
        <div class="card p-5 text-center text-slate-400 py-8">Belum ada disposisi untuk Anda.</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $disposisi->links() }}</div>
</div>
@endsection
