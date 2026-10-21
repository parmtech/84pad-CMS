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

        // Serialize the entire migrate + owner-creation phase. Two browser
        // requests must never run Laravel's DDL migrations concurrently.
        $lockPath = storage_path('framework/84pad-first-run.lock');
        $lock = @fopen($lockPath, 'c');
        if ($lock === false) {
            return back()->withErrors([
                'setup' => 'Setup lock could not be opened. Check private storage permissions.',
            ])->onlyInput('name', 'email');
        }
        if (! flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            return back()->withErrors([
                'setup' => 'Installation is already running. Please wait and retry.',
            ])->onlyInput('name', 'email');
        }

        try {
            // Re-check inside the exclusive lock to close the time-of-check
            // race between initially loading the form and applying DDL.
            $this->assertAvailable($request);

            if (Artisan::call('84pad:doctor') !== 0) {
                throw new \RuntimeException('Installer preflight rejected the host.');
            }

            // Laravel commands may report a failing exit code without throwing.
            // Never create an Owner if any migration returned failure.
            if (Artisan::call('migrate', [
                '--force' => true, '--no-interaction' => true,
            ]) !== 0) {
                throw new \RuntimeException('Required migrations did not succeed.');
            }

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
                'setup' => 'Installation could not be completed. Check server configuration.',
            ])->onlyInput('name', 'email');
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
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
