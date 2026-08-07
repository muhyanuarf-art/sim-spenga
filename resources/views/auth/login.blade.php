<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIM-SPENGA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
            colors: { brand: { 50:'#eef7ff',100:'#d9ecff',600:'#1c68f2',700:'#1553de',900:'#193c8c' } } } } };
    </script>
</head>
<body class="font-sans bg-gradient-to-br from-brand-900 via-brand-700 to-brand-600 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-white/15 backdrop-blur flex items-center justify-center text-white font-extrabold text-2xl mb-4">SP</div>
            <h1 class="text-white text-2xl font-extrabold">SIM-SPENGA</h1>
            <p class="text-brand-50/80 text-sm mt-1">Sistem Informasi Manajemen SMP Negeri 3 Bumiayu</p>
            <p class="text-brand-50/80 text-sm mt-1">Aplikasi Monitoring Guru dan Siswa</p>
        </div>

        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <h2 class="text-lg font-bold text-slate-800 mb-1">Masuk ke Akun Anda</h2>
            <p class="text-sm text-slate-400 mb-6">Silakan login menggunakan email dan password.</p>

            @if($errors->any())
                <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-600/30 focus:border-brand-600"
                           placeholder="nama@spenga.sch.id">
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
        </div>

        <p class="text-center text-brand-50/60 text-xs mt-6">© {{ date('Y') }} SIM-SPENGA · SMP Negeri 3 Bumiayu</p>
    </div>

</body>
</html>
