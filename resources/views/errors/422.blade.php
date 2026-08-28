@extends('layouts.app')
@section('title', 'Data Tidak Dapat Diproses')

@section('content')
{{-- abort(422, "...") dipakai untuk penolakan yang alasannya spesifik
     (mis. "Ada siswa pada data absensi yang bukan anggota kelas ini").
     Tanpa view ini pesannya hilang dan pengguna cuma melihat
     "Unprocessable Content". --}}
<div class="max-w-lg mx-auto card p-8 text-center space-y-3">
    <div class="text-4xl text-amber-300"><i class="fa-solid fa-circle-exclamation"></i></div>
    <p class="text-sm text-slate-600 leading-relaxed">
        {{ $exception->getMessage() ?: 'Data yang dikirim tidak dapat diproses. Periksa kembali isian Anda.' }}
    </p>
    <p class="text-xs text-slate-400">
        Data Anda belum tersimpan. Kembali ke halaman sebelumnya, perbaiki isiannya, lalu simpan lagi.
    </p>
    <div class="pt-2 flex justify-center gap-2">
        <a href="{{ url()->previous() }}" class="btn-outline">← Kembali &amp; Perbaiki</a>
        <a href="{{ route('dashboard') }}" class="btn-primary">Ke Dashboard</a>
    </div>
</div>
@endsection
