<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>Settings · 84PAD Control Studio</title>
<style>
:root{color-scheme:dark;font-family:Inter,system-ui,sans-serif;--line:#ffffff24;--cyan:#77e6f2;--muted:#a4b7ce}
*{box-sizing:border-box}body{margin:0;min-height:100vh;color:#ecf5ff;background:radial-gradient(circle at 10% 5%,#2a2857,#090f21 64%);padding:clamp(16px,4vw,45px)}
a{color:#8fdef6}main{max-width:1150px;margin:auto}.top{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px;padding-bottom:22px;border-bottom:1px solid var(--line)}
.logo{font-weight:950;font-size:27px;letter-spacing:-.07em}.logo b{color:var(--cyan)}.logo small{display:block;font-size:10px;letter-spacing:.16em;color:var(--muted)}
h1{font-size:clamp(34px,5vw,58px);letter-spacing:-.05em;margin:32px 0 6px}h2{margin:0 0 14px;font-size:23px;letter-spacing:-.025em}h3{font-size:17px}p{color:var(--muted);line-height:1.6;font-size:13px}
.layout{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;margin-top:25px}
.panel{background:#141f39ed;border:1px solid var(--line);border-radius:22px;padding:clamp(18px,3vw,26px);min-width:0}
.full{grid-column:1/-1}.state{font-size:12px;font-weight:750;padding:8px 12px;border-radius:18px;background:#123b40;color:#81f8e5;display:inline-block}
label{font-size:12px;font-weight:700;display:block;margin:14px 0 7px}
input[type=text],input[type=password],input[type=url]{width:100%;min-width:0;padding:12px;border:1px solid #47617e;border-radius:10px;background:#0a1529;color:#f0f5ff}
select{width:100%;min-width:0;padding:12px;border:1px solid #47617e;border-radius:10px;background:#0a1529;color:#f0f5ff}
button,.button{font:inherit;cursor:pointer;border:1px solid #5f86a2;border-radius:10px;background:#253754;color:white;padding:10px 14px;font-size:12px;font-weight:850}
button.primary{color:#061c2b;background:linear-gradient(90deg,#78ede0,#ab9afb);border:0}button:hover,.button:hover{filter:brightness(1.1)}
.actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.row{display:flex;gap:13px;justify-content:space-between;align-items:center;flex-wrap:wrap;padding:14px 0;border-top:1px solid #ffffff16}
.row p{margin:4px 0 0}.row strong{font-size:13px}
.notice{border-left:3px solid #6ce5d5;padding:12px 15px;background:#182e3f;margin-top:20px;border-radius:8px}
.error{border-left-color:#ff83aa;background:#392034}
.scopes{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.scope{border:1px solid #436082;background:#101b32;border-radius:14px;padding:14px}.scope strong{display:block;font-size:13px;margin-bottom:5px}
.scope p{margin:0 0 10px}.scope form{margin:0}.danger{border-color:#b65c80;background:#42233b}
.caption{font-size:11px;color:#99a9bb}
@media(max-width:770px){.layout,.scopes{grid-template-columns:1fr}.top{align-items:flex-start}.panel{padding:18px}}
</style>
</head>
<body data-tour-page="settings" data-tour-auto="{{ request()->query('tour') === '1' ? '1' : '0' }}"
 data-tour-complete-url="{{ route('tour.complete') }}">
<main>
<header class="top">
<a href="{{ route('dashboard') }}" class="logo" style="text-decoration:none;color:white">84PAD<b>.</b><small>CORE SETTINGS / PARMTECH</small></a>
<div class="actions"><a href="{{ route('dashboard') }}">← Back to dashboard</a><button type="button" id="tour-start-settings">Start guided tutorial</button></div>
</header>
<div id="settings-intro"><h1>Your Core, your control.</h1><p>Choose only the features you need. cPanel connection is optional: your site, login and unrelated plugins continue working without a cPanel API token.</p></div>
@if(session('notice'))<div class="notice" role="status">{{ session('notice') }}</div>@endif
@if($errors->any())<div class="notice error" role="alert">{{ $errors->first() }}</div>@endif
<div class="layout">
<section class="panel full" id="settings-site">
<h2>Site identity</h2>
<p>Set the name and public address used by published pages. The canonical address is metadata only; Core does not fetch it or use it to make outbound requests.</p>
<form method="post" action="{{ route('settings.site') }}">@csrf
<label for="site-title">Site title</label><input id="site-title" name="site_title" type="text" maxlength="120" value="{{ old('site_title', $site['title']) }}" required>
<label for="canonical-url">Canonical site URL</label><input id="canonical-url" name="canonical_url" type="url" maxlength="2048" value="{{ old('canonical_url', $site['canonical_url']) }}" required><p class="caption">HTTPS is required outside local development. Do not include credentials, query parameters, or a fragment.</p>
<div class="scopes"><div><label for="site-locale">Site language</label><input id="site-locale" name="locale" type="text" maxlength="8" pattern="[a-z]{2,3}(-[A-Z]{2})?" value="{{ old('locale', $site['locale']) }}" required></div><div><label for="site-timezone">Site time zone</label><input id="site-timezone" name="timezone" type="text" maxlength="64" value="{{ old('timezone', $site['timezone']) }}" placeholder="Australia/Sydney" required></div></div>
<label for="homepage-page">Published homepage</label><select id="homepage-page" name="homepage_page_id"><option value="">Use the default Core welcome page</option>@foreach($publishedPages as $publishedPage)<option value="{{ $publishedPage->id }}" @selected((string) old('homepage_page_id', $site['homepage_page_id']) === (string) $publishedPage->id)>{{ $publishedPage->title }} (/{{ $publishedPage->slug }})</option>@endforeach</select><p class="caption">Only published pages can be selected. Unpublishing the selected page automatically returns the site root to the default welcome page.</p>
<button class="primary" type="submit" style="margin-top:16px">Save site identity</button>
</form>
</section>
<section class="panel" id="settings-cpanel">
<h2>cPanel connection</h2>
<p>One central, encrypted API token. Kept private from views, plugin manifests and client-side JavaScript. Use a dedicated token with the least permissions your hosting provider supports.</p>
<span class="state">{{ $cpanel['configured'] ? 'Token stored securely' : 'Optional · Not configured' }}</span>
<form method="post" action="{{ route('settings.cpanel') }}" autocomplete="off">@csrf
<label for="host">cPanel server hostname</label>
<input id="host" name="host" type="text" value="{{ $cpanel['host'] }}" placeholder="server.example.com" required>
<label for="username">cPanel username</label>
<input id="username" name="username" type="text" value="{{ $cpanel['username'] }}" required>
<label for="api-token">cPanel API token</label>
<input id="api-token" name="api_token" type="password" autocomplete="new-password" placeholder="{{ $cpanel['configured'] ? 'Leave empty to keep saved token' : 'Paste API token once' }}">
<p class="caption">Encrypted at rest with APP_KEY. The saved token is never displayed again. Changing APP_KEY without migrating secrets will make it unreadable.</p>
<button class="primary" type="submit">Save connection</button>
</form>
</section>
<section class="panel" id="settings-features">
<h2>Core feature switches</h2>
<p>Each control is independent. Disabling cPanel access will not disable 84PAD itself.</p>
@foreach($switches as $key => $isEnabled)
<div class="row">
<div><strong>{{ match($key) { 'cpanel.integration_enabled' => 'cPanel integration', 'plugins.runtime_enabled' => 'Plugin execution', 'plugins.signed_uploads_enabled' => 'Signed plugin uploads', 'ui.animations_enabled' => 'Interface animations', default => $key } }}</strong>
<p>{{ $key === 'cpanel.integration_enabled' ? 'Globally allow or block cPanel operations' : ($key === 'plugins.runtime_enabled' ? 'Control active plugin PHP execution on the dashboard' : ($key === 'plugins.signed_uploads_enabled' ? 'Allow signed plugin installation from Control Studio' : 'Motion and decorative animation effects')) }}</p></div>
<form method="post" action="{{ route('settings.switch') }}">@csrf
<input type="hidden" name="setting" value="{{ $key }}"><input type="hidden" name="enabled" value="{{ $isEnabled ? '0' : '1' }}">
<button type="submit" aria-label="{{ $key }} {{ $isEnabled ? 'turn off' : 'turn on' }}">{{ $isEnabled ? 'ON ✓' : 'OFF' }}</button>
</form>
</div>
@endforeach
</section>
<section class="panel full" id="settings-permissions">
<h2>Plugin-by-plugin cPanel permissions</h2>
<p>Everything is <strong>denied by default</strong>. Installed plugins get access to individual capabilities only when you explicitly allow them. Global cPanel switch, saved token and plugin activation must also be enabled.</p>
@forelse($installedPlugins as $slug => $plugin)
<h3>{{ $plugin['name'] }} <span class="caption">({{ $slug }})</span></h3>
@if(empty($plugin['cpanel_capabilities']))<p>This plugin does not request cPanel access.</p>@else
<div class="scopes">
@foreach($plugin['cpanel_capabilities'] as $capability)
@php($title = $capabilities[$capability])
@php($active = $grants[$slug][$capability] ?? false)
<div class="scope"><strong>{{ $title }}</strong>
<p>{{ $capability }} · {{ $active ? 'Allowed' : 'Blocked' }}</p>
<form method="post" action="{{ route('settings.permission') }}">@csrf
<input type="hidden" name="plugin_slug" value="{{ $slug }}">
<input type="hidden" name="capability" value="{{ $capability }}">
<input type="hidden" name="enabled" value="{{ $active ? '0' : '1' }}">
<button type="submit">{{ $active ? 'Revoke' : 'Allow' }}</button>
</form></div>
@endforeach
</div>
@endif
@empty
<p>Install a plugin first; its cPanel permissions will appear here.</p>
@endforelse
<p class="caption">These grants control access through Core's fixed cPanel gateway only. PHP plugins themselves are trusted server-side code, not an operating-system sandbox.</p>
</section>
<section class="panel" id="settings-tutorial">
<h2>Training & guided tour</h2>
<p>Step-by-step arrows introduce your dashboard, plugins, cPanel API controls, security and optional features. Start it again whenever you like.</p>
<div class="actions"><button type="button" id="tour-restart-settings" class="primary">Replay guided tour</button><span class="caption">{{ $tourCompleted ? 'Tutorial already completed' : 'Tutorial not completed yet' }}</span></div>
</section>
<section class="panel" id="settings-disconnect">
<h2>Disconnect cPanel</h2>
<p>Remove the encrypted token and revoke all cPanel grants. Regular Core functions continue working.</p>
<form method="post" action="{{ route('settings.disconnect') }}">@csrf
<label for="confirm-disconnect">Type DISCONNECT to remove cPanel credentials</label><input type="text" name="confirm" id="confirm-disconnect" autocomplete="off" required>
<button class="danger" type="submit" style="margin-top:12px">Disconnect & revoke access</button>
</form>
</section>
</div>
<footer style="text-align:center;padding:38px;color:#8da4bd;font-size:12px">84PAD CORE · DEVELOPED BY PARMTECH · DEVELOPMENT BUILD</footer>
</main>
<script defer src="{{ asset('js/84pad-tour.js') }}"></script>
</body></html>

