<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
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
                && Schema::hasTable('users')
                && DB::table('core_installations')
                    ->join('users', 'users.id', '=', 'core_installations.owner_user_id')
                    ->where('core_installations.id', 1)
                    ->where('users.role', 'owner')
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

        // The first Owner is saved with normalized lowercase email.
        // Normalize input without ever trimming or rewriting the password.
        $credentials['email'] = strtolower(trim($credentials['email']));

        try {
            if (! Auth::attempt($credentials)) {
                return back()->withErrors([
                    'email' => 'These credentials do not match our records.',
                ])->onlyInput('email');
            }
            // Session fixation protection; persistent APP_KEY and writable
            // session storage are required on shared hosting.
            $request->session()->regenerate();
        } catch (Throwable $e) {
            // Log only the error type, never the submitted email, password,
            // database connection string or low-level exception message.
            Log::error('84PAD login infrastructure unavailable', ['type' => get_class($e)]);
            abort(503, 'Sign-in is temporarily unavailable. Check session storage and database connectivity.');
        }

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
