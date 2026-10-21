<?php

namespace App\Http\Controllers;

use App\Support\Hooks\HookBus;
use App\Support\Plugins\PluginRegistry;
use App\Support\Plugins\PluginRuntime;
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
        HookBus $hooks
    ): View {
        abort_unless($request->user()?->can('core.view-dashboard'), 403);

        $runtime->boot($hooks, $registry);
        try {
            $widgets = $hooks->filter('core.dashboard_widgets', []);
        } catch (Throwable $e) {
            // A misbehaving plugin must not lock the owner out of Control Studio.
            Log::error('84PAD dashboard hook failed');
            $widgets = [];
        }

        return view('control-studio', [
            'user' => $request->user(),
            'plugins' => $registry->states(),
            'widgets' => is_array($widgets) ? $widgets : [],
        ]);
    }
}
