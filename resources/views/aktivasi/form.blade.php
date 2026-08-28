<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi — SIM-SPENGA</title>
    <x-ikon-aplikasi />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md">
    <div class="text-center mb-7">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-white/15 backdrop-blur flex items-center justify-center text-white text-2xl mb-4 ring-1 ring-white/20">
            <i class="fa-solid fa-key"></i>
        </div>
        <h1 class="text-white text-2xl font-extrabold tracking-tight">Aktivasi Aplikasi</h1>
        <p class="text-white/70 text-sm mt-1">SIM-SPENGA belum diaktifkan di server ini.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-xl p-7">
        <p class="text-sm text-slate-600 leading-relaxed mb-5">
            Masukkan <strong>nomor seri</strong> aplikasi untuk mengaktifkannya. Nomor seri
            hanya perlu dimasukkan sekali di server ini, dan dilisensikan untuk
            <strong>{{ $pemegang }}</strong>.
        </p>

        @if($errors->any())
            <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('aktivasi.simpan') }}" class="space-y-4">
            @csrf
            <div>
                <label for="nomor_seri" class="block text-sm font-semibold text-slate-600 mb-1">Nomor Seri</label>
                <input type="text" id="nomor_seri" name="nomor_seri" required autofocus
                       autocomplete="off" spellcheck="false"
                       placeholder="SPGA-XXXXX-XXXXX-XXXXX-XXXXX"
                       value="{{ old('nomor_seri') }}"
                       class="input font-mono tracking-wider uppercase">
                <p class="text-xs text-slate-400 mt-1.5">
                    Huruf besar/kecil dan tanda hubung tidak masalah — yang penting urutannya benar.
                </p>
            </div>

            <button type="submit" class="btn-primary w-full justify-center">
                <i class="fa-solid fa-unlock mr-2"></i> Aktifkan Sekarang
            </button>
        </form>

        @if($terikatHost)
            <div class="mt-5 rounded-xl bg-slate-50 border border-slate-200 px-4 py-3 text-xs text-slate-500">
                <p class="font-semibold text-slate-600 mb-1">
                    <i class="fa-solid fa-server mr-1"></i> Aktivasi terikat alamat server
                </p>
                Aktivasi akan dicatat untuk <span class="font-mono text-slate-700">{{ $host }}</span>.
                Bila aplikasi dipindah ke server atau domain lain, nomor seri perlu dimasukkan lagi di sana.
            </div>
        @endif
    </div>

    <p class="text-center text-white/50 text-xs mt-6">
        Nomor seri hilang? Hubungi penyedia aplikasi — nomor seri tidak dapat dipulihkan dari dalam aplikasi.
    </p>
</div>

</body>
</html>
