<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><meta name="color-scheme" content="dark">
<title>Review Plugin · 84PAD Control Studio</title>
<link rel="stylesheet" href="{{ asset('css/84pad-installer.css') }}">
</head><body>
<div class="orbs" aria-hidden="true"><i></i><i></i></div><div class="site">
<header class="site-header"><a class="brand" href="{{ route('dashboard') }}"><span class="brand-icon" aria-hidden="true"></span>84PAD<span class="brand-dot">.</span></a><div class="header-side"><span class="status-pill">SIGNED PACKAGE REVIEW</span></div></header>
<section class="hero"><div class="kicker">84PAD / PACKAGE VERIFICATION</div><h1>Review before <em>{{ $review['action'] === 'update' ? 'updating.' : 'installing.' }}</em></h1>
<p>84PAD read the plugin name and version from the signed plugin.json file. Confirm the detected information before any plugin files are changed.</p></section>
<div class="content"><main class="panel main-panel">
<div class="section-top"><span class="eyebrow">VERIFIED PACKAGE DETAILS</span><span class="section-count">OWNER APPROVAL REQUIRED</span></div>
<h2>{{ $review['name'] }}</h2><p class="intro">Plugin ID: <strong>{{ $review['id'] }}</strong> · New version: <strong>{{ $review['version'] }}</strong></p>
<div class="readiness"><div class="readiness-head"><h3>Release details</h3><span class="health">SIGNED &amp; INSPECTED</span></div>
<ul>
<li class="passed"><span class="dot">✓</span>Name: {{ $review['name'] }}</li>
<li class="passed"><span class="dot">✓</span>Version: {{ $review['version'] }}</li>
<li class="passed"><span class="dot">✓</span>Requires Core: {{ $review['requires_core'] }}</li>
<li class="passed"><span class="dot">✓</span>Type: {{ strtoupper($review['action']) }}</li>
</ul></div>
@if($review['action'] === 'update')
<div class="notice"><strong>Update detected:</strong> {{ $review['previous_version'] }} → {{ $review['version'] }}. Your current plugin files will be moved into private recovery storage. The updated plugin will remain disabled until you reactivate it.</div>
@else
<div class="notice"><strong>New plugin:</strong> This is a first installation. The plugin will remain disabled until you manually activate it.</div>
@endif
<p class="intro">The verified original archive will be retained privately as <code>{{ $review['filename'] }}</code>. The original uploaded filename is never used as a trusted plugin identity.</p>
@if($errors->any())<p class="error" role="alert">{{ $errors->first() }}</p>@endif
<form method="post" action="{{ route('plugins.confirm', ['ticket'=>$review['ticket']]) }}">
@csrf
<div class="field"><label for="confirm_slug">Confirm plugin ID</label><input id="confirm_slug" name="confirm_slug" required autocomplete="off" placeholder="{{ $review['id'] }}" maxlength="64"><small>Type the exact ID shown above.</small></div>
<div class="field"><label for="confirm_version">Confirm target version</label><input id="confirm_version" name="confirm_version" required autocomplete="off" placeholder="{{ $review['version'] }}" maxlength="32"><small>Type the exact detected version.</small></div>
<div class="actions"><a class="btn secondary" href="{{ route('dashboard') }}">Cancel</a><button class="btn primary" type="submit">Confirm {{ $review['action'] === 'update' ? 'safe update' : 'installation' }} →</button></div>
</form><p class="trust">Ed25519 signature verified · No automatic activation · Versions never silently downgraded</p>
</main><aside class="panel sidebar-panel"><div class="sidebar-heading"><span class="sidebar-icon">✦</span><div><h2>Protected update</h2><p>Core and plugins stay independent.</p></div></div>
<ul class="checklist">
<li><span class="check">✓</span><span><strong>Core stays untouched</strong><br>Plugin packages cannot replace Laravel/Core files.</span></li>
<li><span class="check">✓</span><span><strong>Previous version kept</strong><br>Update snapshots are placed in the Recovery Vault.</span></li>
<li><span class="check">✓</span><span><strong>Plugin data retained</strong><br>Database tables and stored user content are not deleted during code updates.</span></li>
<li><span class="check">✓</span><span><strong>Explicit reactivation</strong><br>Review new code and enable it when ready.</span></li>
</ul><div class="notice">This protects plugin code updates, not database migration rollback. Staging and host acceptance testing are required before production use.</div>
</aside></div>
<footer class="footer"><span>© ParmTech · 84PAD Control Studio</span><strong>BUILD ANYTHING. PLUG EVERYTHING.</strong></footer></div></body></html>
