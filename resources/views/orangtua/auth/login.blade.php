<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Orang Tua - SIM-SPENGA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans bg-gradient-to-br from-brand-900 via-brand-700 to-brand-600 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-white/15 backdrop-blur flex items-center justify-center text-white font-extrabold text-2xl mb-4">SP</div>
            <h1 class="text-white text-2xl font-extrabold">Portal Orang Tua</h1>
            <p class="text-brand-50/80 text-sm mt-1">SIM-SPENGA — SMP Negeri 3 Bumiayu</p>
        </div>

        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-lg font-bold text-slate-800 mb-1">Pantau Perkembangan Anak Anda</h2>
            <p class="text-sm text-slate-400 mb-6">Login menggunakan NIS anak Anda.</p>

            @if($errors->any())
                <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif
            @if(session('success'))
                <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('orangtua.login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">NIS Anak</label>
                    <input type="text" name="nis" value="{{ old('nis') }}" required autofocus
                           class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-600/30 focus:border-brand-600"
                           placeholder="Contoh: 2526001">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Password</label>
                    <input type="password" name="password" required
                           class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-600/30 focus:border-brand-600"
                           placeholder="••••••••">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-500">
                    <input type="checkbox" name="remember" class="rounded border-slate-300">
                    Ingat saya
                </label>
                <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-2.5 rounded-xl transition shadow-lg shadow-brand-600/30">
                    Masuk
                </button>
            </form>

            <p class="text-xs text-slate-400 text-center mt-5">
                Belum punya akun atau lupa password? Hubungi Wali Kelas atau Admin sekolah.
            </p>
        </div>

        <p class="text-center text-brand-50/60 text-xs mt-6">© {{ date('Y') }} SIM-SPENGA · SMP Negeri 3 Bumiayu</p>
    </div>

</body>
</html>
