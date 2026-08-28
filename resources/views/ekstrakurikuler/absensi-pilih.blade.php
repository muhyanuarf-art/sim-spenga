@extends('layouts.app')
@section('title', 'Absensi Ekstrakurikuler')

@section('content')
<div class="space-y-6">
    <p class="text-sm text-slate-500">
        @if(in_array(auth()->user()->role, ['kesiswaan', 'admin']))
            Semua kegiatan ekstrakurikuler aktif. Pilih kegiatan untuk mengisi/mengedit absensinya.
        @else
            Kegiatan ekstrakurikuler yang Anda bina. Pilih kegiatan untuk mengisi absensinya.
        @endif
    </p>

    <div class="card p-5">
        <div class="overflow-x-auto -mx-5">
            <table class="table-clean w-full">
                <thead><tr><th class="w-12 text-center">No</th><th>Nama Kegiatan</th><th>Pembina</th><th class="th-aksi">Aksi</th></tr></thead>
                <tbody>
                @forelse($kegiatan as $k)
                    <tr>
                        <td class="text-center text-slate-400">{{ $loop->iteration }}</td>
                        <td class="font-semibold">{{ $k->nama_ekstrakurikuler }}</td>
                        <td class="text-slate-600">{{ $k->daftarNamaPembina() }}</td>
                        <td class="td-aksi">
                            <a href="{{ route('ekstrakurikuler.absensi.form', $k) }}" class="btn-chip btn-chip-edit">
                                <i class="fa-solid fa-clipboard-check mr-1.5"></i> Isi Absensi
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-slate-400 py-8">
                        @if(in_array(auth()->user()->role, ['kesiswaan', 'admin']))
                            Belum ada kegiatan ekstrakurikuler aktif.
                        @else
                            Anda belum ditugaskan sebagai pembina kegiatan ekstrakurikuler apa pun.
                        @endif
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
