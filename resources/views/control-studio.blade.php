<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title>Control Studio | 84PAD</title>
<style>
:root{color-scheme:dark;font-family:system-ui,sans-serif}*{box-sizing:border-box}
body{margin:0;color:#eaf4ff;min-height:100vh;background:radial-gradient(circle at top left,#193652,#080f1d 70%)}
header{display:flex;align-items:center;justify-content:space-between;padding:22px clamp(20px,5vw,70px);background:#111d32;border-bottom:1px solid #2a4666}
.brand{font-size:30px;font-weight:900;letter-spacing:.06em}.brand span{color:#75d7ee}
button{cursor:pointer;background:#23415c;color:#e9f7ff;padding:10px 17px;border:1px solid #537690;border-radius:12px}
button:disabled{opacity:.5;cursor:not-allowed}
main{margin:auto;padding:clamp(20px,5vw,70px);max-width:1450px}
h1{font-size:clamp(36px,5vw,66px);margin:.3em 0}h2{margin-top:45px}
small{color:#73d6e9;letter-spacing:.18em;text-transform:uppercase}p{color:#b3c5d9}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(225px,1fr));gap:20px;margin-top:28px}
article{background:linear-gradient(135deg,#182b45,#101b2e);border:1px solid #31506e;border-radius:20px;padding:25px;min-height:160px}
article b{font-size:22px}.pending{color:#e7b873;font-size:13px}
.notice{border-left:4px solid #64d8be;padding:12px 16px;background:#1d3038}.error{border-color:#eca083}
</style></head><body>
<header><div class="brand">84PAD<span>.</span></div><div style="display:flex;gap:12px;align-items:center">@can('core.manage-security')<a href="{{ route('password.edit') }}" style="color:#95e9ff">Security</a>@endcan<form method="post" action="{{ route('logout') }}">@csrf<button>Sign out</button></form></div></header>
<main><small>PARMTECH / DEVELOPMENT PREVIEW</small><h1>Control Studio</h1>
<p>Welcome, {{ $user->name }}. This Core is not yet approved for production.</p>
@if(session('notice'))<p class="notice">{{ session('notice') }}</p>@endif
@if($errors->any())<p class="notice error">{{ $errors->first() }}</p>@endif
<div class="grid">
<article><b>Core</b><p>Owner login + dashboard foundation</p><span class="pending">Under testing</span></article>
<article><b>SSL God</b><p>Certificate renewals</p><span class="pending">Future optional plugin</span></article>
<article><b>Domain Email</b><p>Mail accounts and inbox</p><span class="pending">Future optional plugin</span></article>
<article><b>File Manager</b><p>Site files</p><span class="pending">Future optional plugin</span></article>
<article><b>Backup & Restore</b><p>Scheduled backups and rollback</p><span class="pending">Future optional plugin</span></article>
</div>
@if(count($widgets))
<h2>Active plugin widgets</h2>
<div class="grid">
@foreach($widgets as $widget)
@if(is_array($widget) && is_string($widget['title'] ?? null) && is_string($widget['description'] ?? null))
<article><b>{{ $widget['title'] }}</b><p>{{ $widget['description'] }}</p></article>
@endif
@endforeach
</div>
@endif
<h2>Installed plugins</h2>
<p class="pending">Activation can execute third-party PHP with hosting account privileges. Enable only plugins you trust.</p>
<p>Active local plugins with a valid bootstrap may register hooks. Install only trusted code.</p>
<div class="grid">
@forelse($plugins as $plugin)
<article><b>{{ $plugin['name'] }}</b><p>{{ $plugin['id'] }} · v{{ $plugin['version'] }}</p>
<p class="pending">{{ $plugin['compatible'] ? 'Compatible' : 'Requires newer Core' }} · {{ $plugin['enabled'] ? 'Active' : 'Disabled' }}</p>
@can('core.manage-plugins')
<form method="post" action="{{ route('plugins.state', ['slug'=>$plugin['id']]) }}">@csrf
<input type="hidden" name="action" value="{{ $plugin['enabled'] ? 'deactivate' : 'activate' }}">
<button type="submit" @disabled(!$plugin['compatible'])>{{ $plugin['enabled'] ? 'Deactivate' : 'Activate' }}</button>
</form>@endcan</article>
@empty<article><b>No plugins</b><p>Add a validated JSON manifest to discover plugins.</p></article>
@endforelse
</div></main></body></html>
