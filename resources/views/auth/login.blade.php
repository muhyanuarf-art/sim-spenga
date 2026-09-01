<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — SIM-SPENGA</title>
    <x-ikon-aplikasi />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans bg-gradient-to-br from-brand-900 via-brand-800 to-brand-600 min-h-screen flex items-center justify-center p-4">

@php $sekolah = $pengaturanSekolahGlobal ?? null; @endphp

<div class="w-full max-w-md">
    <div class="text-center mb-7">
        {{-- Logo Aplikasi dari Pengaturan Sekolah; selama belum diunggah
             dipakai inisial dari Nama Sekolah (dulu "SP" ditulis mati). --}}
        <div class="w-16 h-16 mx-auto rounded-2xl bg-white/15 backdrop-blur flex items-center justify-center text-white font-extrabold text-2xl mb-4 ring-1 ring-white/20 overflow-hidden">
            @if($sekolah?->logoAplikasiUrl())
                <img src="{{ $sekolah->logoAplikasiUrl() }}" alt="Logo {{ $sekolah->nama_sekolah }}" class="w-full h-full object-contain p-2">
            @else
                {{ $sekolah?->inisialAplikasi() ?? 'SIM' }}
            @endif
        </div>
        <h1 class="text-white text-2xl font-extrabold tracking-tight">SIM-SPENGA</h1>
        <p class="text-blue-100/80 text-sm mt-1">{{ $sekolah->nama_sekolah ?? 'Sistem Informasi Manajemen Sekolah' }}</p>
        <p class="text-blue-100/60 text-xs mt-0.5">Monitoring & manajemen guru serta siswa</p>
    </div>

    <div class="bg-white rounded-2xl shadow-2xl p-7 sm:p-8">
        <h2 class="text-lg font-bold text-slate-800">Masuk ke Akun Anda</h2>
        <p class="text-sm text-slate-400 mb-6">Gunakan email dan kata sandi yang diberikan Admin sekolah.</p>

        @if($errors->any())
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                <span class="flex-1">{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4" x-data="{ lihat: false }">
            @csrf
            <div>
                <label class="label" for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       autocomplete="username" class="input py-2.5" placeholder="nama@sekolah.sch.id">
            </div>

            <div>
                <label class="label" for="password">Kata Sandi</label>
                <div class="relative">
                    <input id="password" :type="lihat ? 'text' : 'password'" name="password" required
                           autocomplete="current-password" class="input py-2.5 pr-10" placeholder="••••••••">
                    <button type="button" @click="lihat = !lihat" tabindex="-1"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                        <i class="fa-solid" :class="lihat ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-500 select-none">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600">
                Ingat saya di perangkat ini
            </label>

            <button type="submit" class="btn-primary w-full py-2.5">
                <i class="fa-solid fa-right-to-bracket"></i> Masuk
            </button>
        </form>
    </div>

    <p class="text-center text-blue-100/80 text-sm mt-6">
        Orang tua/wali siswa?
        <a href="{{ route('orangtua.login') }}" class="underline font-semibold hover:text-white">Masuk di portal orang tua</a>
    </p>
    <p class="text-center text-blue-100/50 text-xs mt-3">
        SIM-SPENGA &middot; {{ $sekolah->nama_sekolah ?? 'SMP Negeri' }}
    </p>
    <p class="text-center text-blue-100/50 text-xs mt-3">
        &copy; {{ date('Y') }} FF Production
    </p>
</div>

</body>
</html>
