<?php

namespace App\Http\Controllers;

use App\Support\Plugins\PluginUpdateManager;
use App\Support\Settings\CoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

final class PluginPackageReviewController extends Controller
{
    private function authorizeOwner(Request $request, CoreSettings $settings): void
    {
        abort_unless($request->user()?->isOwner(), 403);
        abort_unless($settings->enabled('plugins.signed_uploads_enabled'), 403);
    }

    public function prepare(
        Request $request, PluginUpdateManager $manager, CoreSettings $settings
    ): RedirectResponse {
        $this->authorizeOwner($request, $settings);
        $input = $request->validate([
            'package' => ['required', 'file', 'max:'.(int) floor(config('84pad.plugin_zip_max_bytes', 134217728) / 1024)],
            'signature' => ['required', 'string', 'max:128'],
        ]);
        try {
            $review = $manager->prepare($input['package'], $input['signature'], (string) $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['plugin' => $e->getMessage()]);
        }
        return redirect()->route('plugins.review', ['ticket' => $review['ticket']]);
    }

    public function show(
        Request $request, string $ticket, PluginUpdateManager $manager, CoreSettings $settings
    ): View {
        $this->authorizeOwner($request, $settings);
        try {
            $review = $manager->review($ticket, (string) $request->user()->id);
        } catch (RuntimeException $e) {
            abort(404, 'Plugin review is unavailable or expired.');
        }
        return view('plugins.review', ['review' => $review]);
    }

    public function confirm(
        Request $request, string $ticket, PluginUpdateManager $manager, CoreSettings $settings
    ): RedirectResponse {
        $this->authorizeOwner($request, $settings);
        $input = $request->validate([
            'confirm_slug' => ['required', 'string', 'max:64'],
            'confirm_version' => ['required', 'string', 'max:32'],
        ]);
        try {
            $result = $manager->apply(
                $ticket,
                (string) $request->user()->id,
                $input['confirm_slug'],
                $input['confirm_version']
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['plugin' => $e->getMessage()]);
        }
        return redirect()->route('dashboard')->with('notice',
            'Plugin '.$result['slug'].' v'.$result['version'].' '.($result['action'] === 'update' ? 'updated' : 'installed').
            ' and disabled for review. Signed archive saved as '.$result['filename'].'.'
        );
    }
}
