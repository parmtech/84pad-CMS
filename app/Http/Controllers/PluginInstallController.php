<?php

namespace App\Http\Controllers;

use App\Support\Plugins\PluginPackageInstaller;
use App\Support\Settings\CoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class PluginInstallController extends Controller
{
    public function __invoke(Request $request, PluginPackageInstaller $installer, CoreSettings $settings): RedirectResponse
    {
        abort_unless($request->user()?->isOwner(), 403);
        if (! $settings->enabled('plugins.signed_uploads_enabled')) {
            return back()->withErrors(['plugin' => 'Signed plugin uploads are disabled in Core Settings.']);
        }

        $input = $request->validate([
            'package' => ['required','file','max:'.(int) floor(config('84pad.plugin_zip_max_bytes', 134217728) / 1024)],
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
