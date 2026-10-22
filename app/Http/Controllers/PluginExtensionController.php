<?php

namespace App\Http\Controllers;

use App\Support\Hooks\HookBus;
use App\Support\Plugins\PluginRegistry;
use App\Support\Plugins\PluginRuntime;
use App\Support\Settings\CoreSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Version 0.1 generic plugin request bridge. No plugin-specific code in Core.
 * Activated plugins may handle owner-only GET/POST extension actions via hooks.
 */
final class PluginExtensionController extends Controller
{
    public function __invoke(
        Request $request,
        PluginRegistry $registry,
        PluginRuntime $runtime,
        CoreSettings $settings,
        HookBus $hooks,
        string $slug,
        ?string $action = null
    ): Response {
        abort_unless($request->user()?->isOwner(), 403);
        abort_unless($settings->enabled('plugins.runtime_enabled'), 404);
        $plugin = $registry->states()[$slug] ?? null;
        abort_unless($plugin && $plugin['enabled'] && $plugin['compatible'], 404);

        try {
            $runtime->boot($hooks, $registry);
            $payload = $hooks->filter('core.plugin_request', [
                'slug' => $slug,
                'action' => $action ?? 'index',
                'request' => $request,
                'response' => null,
            ]);
            $response = is_array($payload) ? ($payload['response'] ?? null) : null;
            abort_unless($response instanceof Response, 404);
            return $response;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('84PAD extension request failed', ['slug' => $slug]);
            abort(503, 'Plugin is temporarily unavailable.');
        } finally {
            $hooks->clear();
        }
    }
}
