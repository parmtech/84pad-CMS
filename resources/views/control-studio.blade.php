<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Control Studio · 84PAD</title>
<style>
:root{color-scheme:dark;--ink:#dfeeff;--muted:#93a8bd;--cyan:#70ecfa;--pink:#ff70bd;--orange:#ffc17b;--indigo:#887cff;--line:#ffffff1e;--panel:#15203b}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;min-height:100vh;font-family:Inter,ui-sans-serif,system-ui,-apple-system,sans-serif;color:var(--ink);background:#070b17;overflow-x:hidden}
body:before{content:"";position:fixed;inset:0;pointer-events:none;background:radial-gradient(ellipse at 12% 20%,#9a4bff26,transparent 38%),radial-gradient(ellipse at 90% 2%,#3ddfe321,transparent 34%),radial-gradient(ellipse at 68% 100%,#ff589e10,transparent 38%);z-index:-1}
a{color:inherit;text-decoration:none}button{font:inherit;cursor:pointer}button:focus-visible,a:focus-visible{outline:2px solid var(--cyan);outline-offset:4px}
.layout{display:grid;grid-template-columns:250px minmax(0,1fr);min-height:100vh}
aside{padding:30px 18px;background:linear-gradient(165deg,#11192fcc,#0b1022ed);border-right:1px solid var(--line);backdrop-filter:blur(22px);position:sticky;top:0;height:100vh;display:flex;flex-direction:column;gap:30px}
.brand{font-weight:950;font-size:32px;letter-spacing:-.075em;line-height:1;padding:7px 13px;color:#fff}.brand span{color:var(--cyan);text-shadow:0 0 26px #55dafa80}
.brand small{display:block;font-size:10px;font-weight:650;letter-spacing:.24em;color:var(--muted);margin-top:9px}
.nav-title{font-size:10px;color:#607c98;letter-spacing:.2em;text-transform:uppercase;margin:12px 14px}
nav{display:grid;gap:7px}
nav a{border:1px solid transparent;padding:13px 15px;border-radius:14px;color:#a4b8ce;font-size:13px;font-weight:700;transition:background .2s,transform .2s,color .2s}
nav a:hover{background:#ffffff0d;transform:translateX(3px);color:#fff}
nav a.active{background:linear-gradient(115deg,#6f74ff33,#2de0e72b);border-color:#82abff54;color:white}
nav a span{margin-right:13px;display:inline-block;min-width:17px;color:#83d5fc}
.nav-footer{margin-top:auto;border-radius:17px;border:1px solid #92c4ff28;background:#ffffff08;padding:17px 15px}
.nav-footer strong{font-size:12px}.nav-footer p{font-size:11px;line-height:1.55;color:var(--muted);margin:10px 0 0}
.workspace{min-width:0;max-width:1750px;width:100%;margin:auto;padding:28px clamp(22px,4vw,70px) 80px}
.topbar{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:18px;padding-bottom:25px;border-bottom:1px solid var(--line)}
.eyebrow{text-transform:uppercase;letter-spacing:.23em;color:var(--cyan);font-weight:850;font-size:10px}.topbar b{font-size:14px;font-weight:780}
.top-actions{display:flex;gap:10px;align-items:center}
.pill{font-size:11px;color:#a3d0c9;border:1px solid #45e8c75c;padding:9px 13px;border-radius:100px;background:#22dbbd10}
.signout{border:1px solid #ffffff36;color:#f3f8ff;background:#ffffff0a;border-radius:12px;padding:10px 14px;font-weight:700;font-size:12px}
.signout:hover{background:#ffffff24}
.hero{position:relative;isolation:isolate;overflow:hidden;margin-top:32px;border:1px solid #877bff69;border-radius:29px;min-height:300px;background:linear-gradient(115deg,#222554 0%,#172546 49%,#122934 100%);box-shadow:0 20px 70px #0005}
.hero:before{content:"";position:absolute;right:-60px;top:-130px;width:460px;height:460px;border-radius:50%;border:42px solid #81a1ff32;box-shadow:0 0 0 45px #f870f014,0 0 0 90px #80f8e60a,inset 0 0 60px #8defff3d;animation:orbit 9s ease-in-out infinite alternate;z-index:-1}
.hero:after{content:"";position:absolute;width:300px;height:300px;right:70px;top:5px;background:radial-gradient(circle,#70eefa55,transparent 68%);filter:blur(22px);z-index:-1}
@keyframes orbit{from{transform:translate(0,-14px) rotate(-12deg)}to{transform:translate(-25px,23px) rotate(19deg)}}
.hero-content{padding:clamp(27px,4vw,52px);max-width:670px}
.hero h1{font-size:clamp(35px,4.7vw,68px);line-height:1.04;letter-spacing:-.065em;margin:16px 0 17px;font-weight:920}
.hero h1 em{font-style:normal;background:linear-gradient(90deg,#8ee9ff,#ffa0d5);-webkit-background-clip:text;background-clip:text;color:transparent}
.hero p{color:#bed1e9;line-height:1.7;max-width:560px;font-size:14px}
.hero-tags{display:flex;flex-wrap:wrap;gap:10px;margin-top:29px}
.tag{border:1px solid #a0dfff42;padding:9px 13px;border-radius:11px;background:#080e2142;color:#d7edff;font-size:11px;font-weight:800}
section{margin-top:42px}
.section-head{display:flex;justify-content:space-between;align-items:baseline;gap:15px;margin-bottom:19px;flex-wrap:wrap}
.section-head h2{font-size:24px;letter-spacing:-.05em;margin:0}.section-head p{font-size:12px;color:var(--muted);margin:0}
.cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px}
.card{position:relative;min-width:0;overflow:hidden;border:1px solid var(--line);border-radius:22px;background:linear-gradient(145deg,#1c2541c9,#11182ba8);padding:22px;min-height:178px;transition:transform .2s,border-color .2s,box-shadow .2s}
.card:hover{transform:translateY(-4px);border-color:#85b8ff5c;box-shadow:0 20px 45px #0006}
.card:before{content:"";position:absolute;height:4px;width:62px;top:0;left:23px;border-radius:0 0 9px 9px;background:var(--accent,var(--cyan));box-shadow:0 0 22px var(--accent,var(--cyan))}
.glyph{display:inline-grid;place-items:center;width:41px;height:41px;border-radius:13px;border:1px solid color-mix(in srgb,var(--accent,var(--cyan)) 40%,transparent);background:color-mix(in srgb,var(--accent,var(--cyan)) 12%,transparent);color:var(--accent,var(--cyan));font-size:19px;font-weight:900}
.card h3{font-size:17px;margin:16px 0 6px;letter-spacing:-.02em}.card p{color:#9fb5cd;font-size:12px;line-height:1.6;margin:0 0 15px}
.status{color:var(--accent,var(--cyan));text-transform:uppercase;font-size:9px;font-weight:900;letter-spacing:.13em}
.panel{border:1px solid var(--line);border-radius:23px;background:#101b31bb;padding:clamp(21px,3vw,32px)}
.notice{border-left:3px solid #63efd8;background:#1d3034;color:#cff7ef;border-radius:9px;padding:13px 15px;margin-top:22px;font-size:13px}
.notice.error{border-left-color:#ff739d;background:#3e182d}
.plugins{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px}
.plugin{border:1px solid #6783a63d;border-radius:17px;background:linear-gradient(140deg,#1b2b47,#141d33);padding:22px}
.plugin h3{margin:0 0 7px;font-size:17px}.plugin p{color:#a8bed4;font-size:12px;line-height:1.6}
.plugin .meta{font-size:10px;color:#8dd6e8;letter-spacing:.08em}
.plugin button{background:linear-gradient(100deg,#79dced,#aa8efc);color:#08152d;font-size:12px;font-weight:900;padding:11px 15px;border-radius:11px;border:0;margin-top:12px}
.plugin button:disabled{opacity:.45;cursor:not-allowed}
.widgets{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:15px}
footer{margin-top:45px;text-align:center;font-size:11px;color:#697f9b}
@media(max-width:1100px){.cards{grid-template-columns:repeat(2,minmax(0,1fr))}.layout{grid-template-columns:210px minmax(0,1fr)}}
@media(max-width:740px){.layout{display:block}aside{position:relative;height:auto;padding:16px 15px;gap:10px;border-right:0;border-bottom:1px solid var(--line)}.brand{font-size:25px}.nav-title,.nav-footer{display:none}nav{display:flex;overflow-x:auto;padding-bottom:3px}nav a{white-space:nowrap;padding:10px 14px;font-size:12px}.workspace{padding:20px 15px 55px}.hero{min-height:270px}.hero:before{right:-210px;opacity:.65}.hero-content{position:relative}.cards{gap:10px}.card{min-height:150px;padding:17px}.section-head h2{font-size:22px}}
@media(max-width:400px){.cards{grid-template-columns:1fr}.pill{display:none}}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}.hero:before{animation:none}nav a,.card{transition:none}}
</style>
</head><body>
<div class="layout">
<aside aria-label="Control Studio navigation">
<a class="brand" href="{{ route('dashboard') }}">84PAD<span>.</span><small>CONTROL STUDIO / CORE 0.1</small></a>
<div><p class="nav-title">Navigation</p>
<nav aria-label="Main">
<a class="active" href="{{ route('dashboard') }}"><span>◆</span>Overview</a>
<a href="#modules"><span>◈</span>Systems</a>
<a href="#plugins"><span>✳</span>Plugins</a>
@can('core.manage-security')<a href="{{ route('password.edit') }}"><span>◉</span>Security</a>@endcan
</nav></div>
<div class="nav-footer"><strong>Built by ParmTech</strong><p>Built for possibility. Powered by a small, extensible Core. Extra website features live in plugins.</p></div>
</aside>
<div class="workspace">
<header class="topbar">
<div><div class="eyebrow">84PAD / SECURE ADMIN</div><b>Welcome, {{ $user->name }}</b></div>
<div class="top-actions"><span class="pill">● DEVELOPMENT BUILD</span>
<form method="post" action="{{ route('logout') }}">@csrf<button class="signout" type="submit">Sign out ↗</button></form></div>
</header>
@if(session('notice'))<div class="notice" role="status">{{ session('notice') }}</div>@endif
@if($errors->any())<div class="notice error" role="alert">{{ $errors->first() }}</div>@endif
<section class="hero" aria-label="Control Studio welcome">
<div class="hero-content">
<div class="eyebrow">PLATFORM ENGINE / V 0.1</div>
<h1>Control the <em>possible.</em></h1>
<p>Welcome to your command centre. Your 84PAD Core handles the essentials; every extra capability can be added through independent plugins.</p>
<div class="hero-tags"><span class="tag">Laravel 12</span><span class="tag">Plugin-first architecture</span><span class="tag">Private build · Testing</span></div>
</div></section>
<section id="modules">
<div class="section-head"><h2>System universe</h2><p>Optional features stay modular</p></div>
<div class="cards">
<article class="card" style="--accent:#74edf2"><span class="glyph">⌘</span><h3>Core Engine</h3><p>Secure sign-in, permissions and plugin management foundation.</p><div class="status">Development verified in CI</div></article>
<article class="card" style="--accent:#ff91b7"><span class="glyph">◇</span><h3>SSL God</h3><p>Certificate detection, installation and renewal.</p><div class="status">Future optional plugin</div></article>
<article class="card" style="--accent:#af97ff"><span class="glyph">@</span><h3>Domain Email</h3><p>Manage same-domain mailboxes and inboxes.</p><div class="status">Future optional plugin</div></article>
<article class="card" style="--accent:#ffc17b"><span class="glyph">▣</span><h3>File Manager</h3><p>Manage website files from one place.</p><div class="status">Future optional plugin</div></article>
<article class="card" style="--accent:#71eca5"><span class="glyph">↺</span><h3>Backups</h3><p>Scheduled off-host backups and safe restore.</p><div class="status">Future optional plugin</div></article>
</div></section>
@if(count($widgets))
<section><div class="section-head"><h2>Active plugin widgets</h2><p>Content delivered through 84PAD hooks</p></div>
<div class="widgets">
@foreach($widgets as $widget)
@if(is_array($widget) && is_string($widget['title'] ?? null) && is_string($widget['description'] ?? null))
<article class="plugin"><h3>{{ $widget['title'] }}</h3><p>{{ $widget['description'] }}</p></article>
@endif
@endforeach
</div></section>
@endif
<section id="plugins"><div class="section-head"><h2>Your plugins</h2><p>Local plugin discovery</p></div>
<div class="panel"><p style="color:#a2bed5;font-size:12px;margin-top:0">Security note: active PHP plugins run with the hosting account's privileges. Install and activate only code you trust. Central signed distribution is not available yet.</p>
<div class="plugins">
@forelse($plugins as $plugin)
<article class="plugin">
<h3>{{ $plugin['name'] }}</h3><p class="meta">{{ $plugin['id'] }} / v{{ $plugin['version'] }}</p>
<p>{{ $plugin['compatible'] ? 'Compatible with this Core' : 'Requires newer Core' }} · {{ $plugin['enabled'] ? 'Active' : 'Disabled' }}</p>
@can('core.manage-plugins')
<form method="post" action="{{ route('plugins.state', ['slug'=>$plugin['id']]) }}">@csrf
<input type="hidden" name="action" value="{{ $plugin['enabled'] ? 'deactivate' : 'activate' }}">
<button type="submit" @disabled(!$plugin['compatible'])>{{ $plugin['enabled'] ? 'Deactivate' : 'Activate' }}</button>
</form>
@endcan
</article>
@empty
<article class="plugin"><h3>No plugins discovered</h3><p>Add a compatible local plugin manifest to begin.</p></article>
@endforelse
</div></div></section>
<footer>84PAD CORE • DEVELOPED BY PARMTECH • NOT YET PRODUCTION READY</footer>
</div></div>
</body></html>
