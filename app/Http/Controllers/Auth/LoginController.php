<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class LoginController extends Controller
{
    private function requireInstalled(): void
    {
        // A fresh or temporarily unavailable database must produce a safe
        // unavailable response, not a raw connection exception.
        try {
            $installed = Schema::hasTable('core_installations')
                && DB::table('core_installations')
                    ->where('id', 1)
                    ->whereNotNull('owner_user_id')
                    ->exists();
        } catch (Throwable $e) {
            $installed = false;
        }

        abort_unless($installed, 503, '84PAD is not ready for sign-in.');
    }

    public function show(): View
    {
        $this->requireInstalled();
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $this->requireInstalled();
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
