<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password - Portal Orang Tua</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans bg-slate-50 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-sm p-8">
            <h2 class="text-lg font-bold text-slate-800 mb-1">Ganti Password</h2>
            <p class="text-sm text-slate-400 mb-6">Disarankan mengganti password default demi keamanan akun.</p>

            @if($errors->any())
                <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">{{ $errors->first() }}</div>
            @endif
            @if(session('success'))
                <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('orangtua.ganti-password') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Password Lama</label>
                    <input type="password" name="password_lama" required
                           class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-600/30 focus:border-brand-600">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Password Baru</label>
                    <input type="password" name="password_baru" required minlength="6"
                           class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-600/30 focus:border-brand-600">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Ulangi Password Baru</label>
                    <input type="password" name="password_baru_confirmation" required minlength="6"
                           class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-600/30 focus:border-brand-600">
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('orangtua.dashboard') }}" class="flex-1 text-center border border-slate-200 rounded-xl py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Kembali</a>
                    <button type="submit" class="flex-1 bg-brand-600 hover:bg-brand-700 text-white font-bold py-2.5 rounded-xl transition">Simpan</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
