<?php

namespace App\Http\Controllers;

use App\Support\Settings\CoreSettings;
use App\Support\Settings\CpanelPermissions;
use App\Support\Plugins\PluginCatalog;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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
            'site' => $settings->siteSettings(),
            'publishedPages' => Page::query()->where('status', 'published')->orderBy('title')->get(['id', 'title', 'slug']),
            'cpanel' => $settings->cpanelStatus(),
            'switches' => $switches,
            'capabilities' => CpanelPermissions::CAPABILITIES,
            'grants' => $permissions->grants(),
            'installedPlugins' => $catalog->all(),
            'tourCompleted' => (bool) DB::table('core_user_preferences')
                ->where('user_id', $request->user()->id)->value('tour_completed'),
        ]);
    }

    public function saveSite(Request $request, CoreSettings $settings): RedirectResponse
    {
        abort_unless($request->user()?->isOwner(), 403);

        $data = $request->validate([
            'site_title' => ['required', 'string', 'max:120'],
            'canonical_url' => ['required', 'string', 'url', 'max:2048'],
            'locale' => ['required', 'string', 'regex:/^[a-z]{2,3}(?:-[A-Z]{2})?$/D'],
            'timezone' => ['required', 'string', 'timezone'],
            'homepage_page_id' => ['nullable', 'integer', 'exists:pages,id'],
        ]);
        $homepagePageId = isset($data['homepage_page_id']) ? (int) $data['homepage_page_id'] : null;
        if ($homepagePageId !== null && ! Page::query()->whereKey($homepagePageId)->where('status', 'published')->exists()) {
            return back()->withErrors(['homepage_page_id' => 'Choose a page that is already published.'])->withInput();
        }
        $parts = parse_url($data['canonical_url']);
        $allowedSchemes = app()->environment(['local', 'testing']) ? ['https', 'http'] : ['https'];
        if (! is_array($parts)
            || ! in_array(strtolower((string) ($parts['scheme'] ?? '')), $allowedSchemes, true)
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])) {
            return back()->withErrors(['canonical_url' => 'Enter an HTTPS site URL without credentials, query parameters, or a fragment.'])->withInput();
        }

        $settings->saveSiteSettings(
            trim($data['site_title']),
            rtrim($data['canonical_url'], '/'),
            $data['locale'],
            $data['timezone'],
            $homepagePageId
        );

        return redirect()->route('settings.show')->with('notice', 'Site identity settings saved.');
    }

    public function saveCpanel(Request $request, CoreSettings $settings): RedirectResponse
    {
        abort_unless($request->user()?->isOwner(), 403);
        // Avoid Laravel's default input-flashing on validation errors:
        // the raw API token must NEVER be copied into session flash storage.
        $check = Validator::make($request->all(), [
            'host' => ['required','string','max:253'],
            'username' => ['required','string','max:64'],
            'api_token' => ['nullable','string','min:16','max:4096'],
        ]);
        if ($check->fails()) {
            return back()->withErrors($check)->onlyInput('host', 'username');
        }
        $data = $check->validated();
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

