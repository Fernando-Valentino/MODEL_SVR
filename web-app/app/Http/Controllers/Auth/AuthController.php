<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Tampilkan form login.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Proses autentikasi login.
     */
    public function login(Request $request): RedirectResponse
    {
        // Validasi inputan login
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->has('remember');

        // Lakukan autentikasi menggunakan username
        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = Auth::user();

            if ($user->hasRole('operator')) {
                return redirect()->intended(route('operator.dashboard'));
            } elseif ($user->hasRole('kepala_upt')) {
                return redirect()->intended(route('kepala-upt.dashboard'));
            } elseif ($user->hasRole('kepala_dishub')) {
                return redirect()->intended(route('kepala-dishub.dashboard'));
            } else {
                Auth::logout();
                return redirect()->route('login')->withErrors([
                    'username' => 'Role tidak dikenal.',
                ]);
            }
        }

        // Jika autentikasi gagal
        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->onlyInput('username');
    }

    /**
     * Keluar dari sesi login.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
