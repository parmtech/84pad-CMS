<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

final class FirstRunController extends Controller
{
    private function assertAvailable(Request $request): void
    {
        // Never allow setup without an owner-provided out-of-band secret.
        $token = (string) config('84pad.setup_token');
        abort_unless(strlen($token) >= 32, 404);

        if (config('app.env') === 'production') {
            abort_unless($request->isSecure() && ! config('app.debug'), 404);
        }

        try {
            if (Schema::hasTable('core_installations')
                && DB::table('core_installations')->where('id', 1)->exists()) {
                abort(404);
            }
            if (Schema::hasTable('users') && DB::table('users')->exists()) {
                abort(404);
            }
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (Throwable $e) {
            // A broken DB must not show exception data to browser visitors.
            abort(503, '84PAD database is not ready.');
        }
    }

    public function show(Request $request): View
    {
        $this->assertAvailable($request);
        return view('auth.first-run');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->assertAvailable($request);
        $configured = (string) config('84pad.setup_token');
        $submitted = (string) $request->input('setup_token', '');

        if (strlen($submitted) > 256 || ! hash_equals($configured, $submitted)) {
            abort(403);
        }

        $data = $request->validate([
            'name' => ['required','string','min:2','max:120'],
            'email' => ['required','email','max:255'],
            'password' => ['required','confirmed',Password::min(12),'max:1024'],
        ]);

        // Run the same read-only host checks used by the CLI installer.
        try {
            if (Artisan::call('84pad:doctor') !== 0) {
                return back()->withErrors(['setup' => 'Host configuration failed preflight checks.'])
                    ->onlyInput('name','email');
            }
            Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);

            DB::transaction(function () use ($data): void {
                $claimed = DB::table('core_installations')->insertOrIgnore([
                    'id' => 1, 'owner_user_id' => null, 'created_at' => now(),
                ]);
                if ($claimed !== 1 || User::query()->exists()) {
                    throw new \RuntimeException('Already installed');
                }
                $owner = User::query()->create([
                    'name' => $data['name'],
                    'email' => strtolower($data['email']),
                    'password' => Hash::make($data['password']),
                    'role' => 'owner',
                ]);
                DB::table('core_installations')->where('id', 1)
                    ->update(['owner_user_id' => $owner->id]);
            }, 3);
        } catch (Throwable $e) {
            return back()->withErrors([
                'setup' => 'Installation could not be completed. Contact your hosting administrator.',
            ])->onlyInput('name','email');
        }

        // The standalone cPanel bootstrap token is only needed before the
        // Owner exists. Discard its private seed once setup succeeds.
        $tokenFile = storage_path('app/private/first-run-token');
        if (is_file($tokenFile) && ! is_link($tokenFile)) {
            @unlink($tokenFile);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Core installed. Please sign in.');
    }
}
