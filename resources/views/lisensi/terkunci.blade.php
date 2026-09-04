<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masa Aktif Berakhir — SIM-SPENGA</title>
    <x-ikon-aplikasi />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

{{--
    HALAMAN TERKUNCI — mode lisensi 'server'.

    SENGAJA TIDAK ADA SATU PUN ISIAN di halaman ini. Nomor seri sudah
    ditinggalkan, dan perpanjangan sepenuhnya dikerjakan FF Production
    dari sisinya. Menyodorkan kotak isian yang mustahil diisi hanya
    membuat guru mengira dirinya yang salah, lalu mencoba mengetik apa
    saja sampai lelah.

    Hurufnya besar mengikuti seluruh pemberitahuan lain di aplikasi ini —
    penggunanya guru sampai usia 60-an.
--}}
<body class="font-sans bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-lg">
    <div class="text-center mb-7">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-400/20 backdrop-blur flex items-center justify-center text-amber-300 text-2xl mb-4 ring-1 ring-amber-300/30">
            <i class="fa-solid fa-hourglass-end"></i>
        </div>
        <h1 class="text-white text-2xl font-extrabold tracking-tight">Masa Aktif Telah Berakhir</h1>
        <p class="text-white/70 text-base mt-2">{{ $sekolah }}</p>
    </div>

    <div class="bg-white rounded-2xl shadow-2xl p-7 sm:p-8">

        @if(session('error'))
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                <span class="flex-1">{{ session('error') }}</span>
            </div>
        @endif

        <p class="text-slate-700 text-lg leading-relaxed">
            Masa berlangganan SIM-SPENGA untuk sekolah ini sudah habis, sehingga
            aplikasi tidak dapat digunakan untuk sementara.
        </p>

        <p class="text-slate-600 text-base leading-relaxed mt-4">
            <strong class="text-slate-800">Seluruh data sekolah tetap aman dan utuh.</strong>
            Tidak ada yang hilang. Begitu masa aktifnya diperpanjang, aplikasi
            terbuka kembali dengan sendirinya beserta seluruh isinya.
        </p>

        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">
            <p class="font-bold text-amber-900 text-base">Yang perlu dilakukan</p>
            <p class="text-amber-800 text-base leading-relaxed mt-1">
                Hubungi <strong>FF Production</strong> untuk memperpanjang masa berlangganan.
                Tidak ada nomor seri atau kode apa pun yang perlu Anda cari maupun ketik
                di halaman ini.
            </p>
        </div>

        @if($berakhirPada)
            <p class="text-sm text-slate-400 mt-5">
                Masa aktif tercatat berakhir pada
                <strong class="text-slate-600">{{ \Carbon\Carbon::createFromTimestamp($berakhirPada)->translatedFormat('d F Y, H:i') }}</strong>.
            </p>
        @endif

        {{-- Sesudah FF Production memperpanjang, aplikasi membuka sendiri pada
             sapaan berkala berikutnya. Tombol ini memaksanya terjadi sekarang,
             supaya sekolah yang sudah membayar tidak menunggu tanpa kepastian. --}}
        <form method="POST" action="{{ route('lisensi.periksa-ulang') }}" class="mt-7">
            @csrf
            <button type="submit" class="btn-primary w-full py-3 text-base">
                <i class="fa-solid fa-rotate"></i> Sudah Diperpanjang? Periksa Sekarang
            </button>
        </form>

        <p class="text-xs text-slate-400 text-center mt-3">
            Aplikasi juga memeriksanya sendiri secara berkala, jadi tombol ini
            hanya mempercepat.
        </p>
    </div>

    @if($disapaTerakhir)
        <p class="text-center text-white/40 text-xs mt-6">
            Terakhir menghubungi server: {{ \Carbon\Carbon::createFromTimestamp($disapaTerakhir)->diffForHumans() }}
        </p>
    @endif

    <p class="text-center text-white/40 text-xs mt-2">&copy; 2026 FF Production</p>
</div>

</body>
</html>
