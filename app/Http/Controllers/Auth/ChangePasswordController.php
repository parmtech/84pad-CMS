<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

final class ChangePasswordController extends Controller
{
    public function edit(Request $request): View
    {
        abort_unless($request->user()?->isOwner(), 403);
        return view('auth.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isOwner(), 403);

        $validated = $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(12), 'max:1024'],
        ]);

        $owner = $request->user();
        $owner->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => \Illuminate\Support\Str::random(60),
        ])->save();

        // Prevent fixation; auth.session middleware notices changed password
        // on other browser sessions. Keep only this authenticated session current.
        $request->session()->regenerate();
        $request->session()->put('password_hash_web', $owner->getAuthPassword());

        return redirect()->route('dashboard')->with('notice', 'Password updated.');
    }
}
