<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // "Ingat saya" AMAN DIPAKAI di sini — tetapi hanya karena
        // remember_token disimpan sebagai SIDIK, bukan nilai polos.
        //
        // Pada perilaku bawaan Laravel, cookie remember berisi
        // `id|remember_token|awalan hash sandi` dan ketiganya ada di dalam
        // dump database. Siapa pun yang memegang salinan database beserta
        // APP_KEY karena itu bisa merakit sendiri cookie yang sah untuk
        // akun mana pun — masuk tanpa tahu kata sandinya.
        //
        // Yang menutup jalan itu ada di App\Auth\PenyediaPenggunaTokenTersidik.
        // Jangan mengembalikan driver 'eloquent' bawaan di config/auth.php
        // tanpa membaca berkas tersebut lebih dulu.
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Email atau password salah.',
            ])->onlyInput('email');
        }

        if (! Auth::user()->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi Admin.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
