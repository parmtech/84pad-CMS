<?php

namespace App\Http\Controllers;

use App\Support\Hooks\HookBus;
use App\Support\Plugins\PluginRegistry;
use App\Support\Plugins\PluginRuntime;
use App\Support\Plugins\PluginQuarantine;
use App\Support\Settings\CoreSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\View\View;

class ControlStudioController extends Controller
{
    public function __invoke(
        Request $request,
        PluginRegistry $registry,
        PluginRuntime $runtime,
        PluginQuarantine $quarantine,
        CoreSettings $settings,
        HookBus $hooks
    ): View {
        abort_unless($request->user()?->can('core.view-dashboard'), 403);

        if ($settings->enabled('plugins.runtime_enabled')) {
            $runtime->boot($hooks, $registry);
        }
        try {
            $widgets = $hooks->filter('core.dashboard_widgets', []);
        } catch (Throwable $e) {
            // A misbehaving plugin must not lock the owner out of Control Studio.
            Log::error('84PAD dashboard hook failed');
            $widgets = [];
        } finally {
            // Avoid leaked plugin callbacks in long-lived PHP workers and tests.
            $hooks->clear();
        }

        return view('control-studio', [
            'user' => $request->user(),
            'plugins' => $registry->states(),
            'quarantined' => $quarantine->snapshots(),
            'widgets' => is_array($widgets) ? $widgets : [],
            'signedUploadsEnabled' => $settings->enabled('plugins.signed_uploads_enabled'),
            'animationsEnabled' => $settings->enabled('ui.animations_enabled'),
            'tourAuto' => $request->user()->isOwner() && (
                $request->query('tour') === '1'
                || ! (bool) DB::table('core_user_preferences')
                    ->where('user_id', $request->user()->id)->value('tour_completed')
            ),
        ]);
    }
}
