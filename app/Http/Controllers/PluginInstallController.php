<?php

namespace App\Http\Controllers;

use App\Support\Plugins\PluginPackageInstaller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class PluginInstallController extends Controller
{
    public function __invoke(Request $request, PluginPackageInstaller $installer): RedirectResponse
    {
        abort_unless($request->user()?->isOwner(), 403);

        $input = $request->validate([
            'package' => ['required','file','max:20480'],
            'signature' => ['required','string','max:128'],
        ]);

        try {
            $slug = $installer->install($input['package'], $input['signature']);
        } catch (RuntimeException $e) {
            return back()->withErrors([
                'plugin' => 'Package rejected. Verify publisher signature, compatibility and archive contents.',
            ]);
        }

        return redirect()->route('dashboard')->with(
            'notice',
            'Signed plugin '.$slug.' installed but disabled. Review and activate it explicitly.'
        );
    }
}
