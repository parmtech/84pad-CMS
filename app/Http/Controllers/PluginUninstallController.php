<?php

namespace App\Http\Controllers;

use App\Support\Plugins\PluginQuarantine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class PluginUninstallController extends Controller
{
    public function __invoke(
        Request $request,
        string $slug,
        PluginQuarantine $quarantine
    ): RedirectResponse {
        abort_unless($request->user()?->isOwner(), 403);

        $data = $request->validate([
            'confirm_slug' => ['required','string','max:64'],
        ]);
        if (! hash_equals($slug, $data['confirm_slug'])) {
            return back()->withErrors(['plugin' => 'Type the exact plugin ID to uninstall.']);
        }

        try {
            $quarantine->uninstall($slug);
        } catch (RuntimeException $e) {
            return back()->withErrors([
                'plugin' => 'Cannot uninstall this plugin. Core/bundled plugins are protected.',
            ]);
        }

        return redirect()->route('dashboard')->with(
            'notice',
            'Plugin disabled and moved to private quarantine. Plugin database data was not deleted.'
        );
    }
}
