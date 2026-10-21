<?php

namespace App\Http\Controllers;

use App\Support\Settings\CoreSettings;
use App\Support\Settings\CpanelPermissions;
use App\Support\Plugins\PluginCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;

final class CoreSettingsController extends Controller
{
    public function show(
        Request $request,
        CoreSettings $settings,
        CpanelPermissions $permissions,
        PluginCatalog $catalog
    ): View {
        abort_unless($request->user()?->isOwner(), 403);

        $switches = [];
        foreach (CoreSettings::SWITCHES as $name => $default) {
            $switches[$name] = $settings->enabled($name);
        }

        return view('core-settings', [
            'cpanel' => $settings->cpanelStatus(),
            'switches' => $switches,
            'capabilities' => CpanelPermissions::CAPABILITIES,
            'grants' => $permissions->grants(),
            'installedPlugins' => $catalog->all(),
            'tourCompleted' => (bool) DB::table('core_user_preferences')
                ->where('user_id', $request->user()->id)->value('tour_completed'),
        ]);
    }

    public function saveCpanel(Request $request, CoreSettings $settings): RedirectResponse
    {
        abort_unless($request->user()?->isOwner(), 403);
        $data = $request->validate([
            'host' => ['required','string','max:253'],
            'username' => ['required','string','max:64'],
            'api_token' => ['nullable','string','min:16','max:4096'],
        ]);
        try {
            $settings->saveCpanel(
                $data['host'], $data['username'], ($data['api_token'] ?? '') === ''
                    ? null : $data['api_token']
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['cpanel' => $e->getMessage()]);
        }
        // Credentials never appear in the form, logs or flash/session data.
        return redirect()->route('settings.show')->with('notice', 'cPanel connection details saved. Enable only the access you need.');
    }

    public function switch(Request $request, CoreSettings $settings): RedirectResponse
    {
        abort_unless($request->user()?->isOwner(), 403);
        $data = $request->validate([
            'setting' => ['required', 'string'],
            'enabled' => ['required', 'boolean'],
        ]);
        if (! array_key_exists($data['setting'], CoreSettings::SWITCHES)) {
            return back()->withErrors(['setting' => 'Unknown Core setting.']);
        }
        if ($data['setting'] === 'cpanel.integration_enabled'
            && (bool) $data['enabled']
            && ! $settings->cpanelStatus()['configured']) {
            return back()->withErrors(['setting' => 'Save a cPanel API token first. Other Core features still work without it.']);
        }

        $settings->setSwitch($data['setting'], (bool) $data['enabled']);
        return redirect()->route('settings.show')->with('notice', 'Setting updated.');
    }

    public function permission(Request $request, CpanelPermissions $permissions): RedirectResponse
    {
        abort_unless($request->user()?->isOwner(), 403);
        $data = $request->validate([
            'plugin_slug' => ['required','string','max:64'],
            'capability' => ['required','string','max:32'],
            'enabled' => ['required','boolean'],
        ]);
        try {
            $permissions->grant($data['plugin_slug'], $data['capability'], (bool) $data['enabled']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['permissions' => 'Unknown plugin or capability.']);
        }
        return redirect()->route('settings.show')->with('notice', 'Plugin cPanel permission updated.');
    }

    public function disconnect(Request $request, CoreSettings $settings): RedirectResponse
    {
        abort_unless($request->user()?->isOwner(), 403);
        $request->validate(['confirm' => ['required', 'in:DISCONNECT']]);
        $settings->disconnectCpanel();
        return redirect()->route('settings.show')->with('notice', 'cPanel disconnected and plugin grants disabled.');
    }
}
