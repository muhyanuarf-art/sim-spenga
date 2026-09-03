@extends('layouts.app')
@section('title', 'Tidak Punya Akses')

{{--
    Halaman 403.

    Sebelum ini belum ada, sehingga setiap penolakan peran di seluruh
    aplikasi — bukan hanya satu fitur — menampilkan halaman bawaan
    Laravel: layar putih bertuliskan "403 Forbidden", tanpa jalan
    kembali dan tanpa keterangan apa pun yang berguna bagi guru.

    Pesan yang dikirim abort(403, '...') ditampilkan apa adanya, karena
    di aplikasi ini pesan itu selalu ditulis untuk dibaca pengguna,
    bukan untuk programmer.
--}}
@section('content')
<div class="max-w-lg mx-auto card p-8 text-center space-y-3">
    <div class="text-4xl text-slate-300"><i class="fa-solid fa-hand"></i></div>

    <p class="font-bold text-slate-800">Halaman ini bukan bagian Anda</p>

    <p class="text-sm text-slate-500 leading-relaxed">
        {{ $exception->getMessage() ?: 'Peran akun Anda tidak memiliki akses ke halaman ini.' }}
    </p>

    <p class="text-xs text-slate-400">
        Kalau menurut Anda ini keliru, hubungi Admin sekolah — merekalah yang
        mengatur peran setiap akun.
    </p>

    <div class="pt-2 flex items-center justify-center gap-2">
        <a href="{{ url()->previous() }}" class="btn-outline">← Kembali</a>
        <a href="{{ route('dashboard') }}" class="btn-primary">Ke Beranda</a>
    </div>
</div>
@endsection
