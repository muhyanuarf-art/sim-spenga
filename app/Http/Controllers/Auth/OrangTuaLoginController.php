<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrangTuaLoginController extends Controller
{
    public function create()
    {
        if (Auth::guard('orangtua')->check()) {
            return redirect()->route('orangtua.dashboard');
        }
        return view('orangtua.auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'nis' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('orangtua')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'nis' => 'NIS atau password salah. Hubungi Wali Kelas/Admin jika belum punya akun.',
            ])->onlyInput('nis');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('orangtua.dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::guard('orangtua')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('orangtua.login');
    }
}
