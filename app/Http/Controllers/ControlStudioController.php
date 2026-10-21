<?php

namespace App\Http\Controllers;

use App\Support\Hooks\HookBus;
use App\Support\Plugins\PluginRegistry;
use App\Support\Plugins\PluginRuntime;
use Illuminate\Http\Request;
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
        $widgets = $hooks->filter('core.dashboard_widgets', []);

        return view('control-studio', [
            'user' => $request->user(),
            'plugins' => $registry->states(),
            'widgets' => is_array($widgets) ? $widgets : [],
        ]);
    }
}
