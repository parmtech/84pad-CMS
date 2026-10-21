<?php

namespace App\Http\Controllers;

use App\Support\Plugins\PluginQuarantine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class PluginRestoreController extends Controller
{
    public function __invoke(
        Request $request,
        string $snapshot,
        PluginQuarantine $quarantine
    ): RedirectResponse {
        abort_unless($request->user()?->isOwner(), 403);

        $data = $request->validate([
            'confirm_slug' => ['required', 'string', 'max:64'],
        ]);
        $expected = null;
        if (preg_match('/^([a-z][a-z0-9-]{1,63})-\d{14}-[a-f0-9]{16}$/D', $snapshot, $parts)) {
            $expected = $parts[1];
        }
        if ($expected === null || ! hash_equals($expected, $data['confirm_slug'])) {
            return back()->withErrors(['plugin' => 'Confirm the plugin ID to restore.']);
        }

        try {
            $slug = $quarantine->restore($snapshot);
        } catch (RuntimeException $e) {
            return back()->withErrors([
                'plugin' => 'Snapshot unavailable, invalid, or an installed plugin has the same ID.',
            ]);
        }

        return redirect()->route('dashboard')->with(
            'notice',
            'Plugin '.$slug.' restored disabled. Review the files before activation.'
        );
    }
}
