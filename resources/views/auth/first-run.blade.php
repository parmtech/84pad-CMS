<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><meta name="color-scheme" content="dark">
<title>Owner Setup · 84PAD</title>
<style>
:root{color-scheme:dark;font-family:Inter,ui-sans-serif,system-ui,sans-serif}
*{box-sizing:border-box}body{min-height:100vh;margin:0;background:#070d1b;color:#f3f8ff}
body:before{content:"";position:fixed;inset:0;z-index:-1;background:radial-gradient(circle at 8% 10%,#24477799,transparent 39%),radial-gradient(circle at 90% 87%,#4b2d7799,transparent 48%),linear-gradient(transparent 95%,#7df5f310 95%);background-size:auto,auto,42px 42px}
.wrap{max-width:1020px;margin:auto;padding:30px 20px 70px}
.header{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:35px}
.brand{font-size:22px;font-weight:900;letter-spacing:-.06em}.brand i{font-style:normal;color:#bcff73}
.tag{font-size:11px;letter-spacing:.12em;color:#a5d9e9;border:1px solid #426385;padding:10px 15px;border-radius:30px}
.progress{display:flex;gap:10px;margin-bottom:25px}.progress span{flex:1;background:#172741;border:1px solid #526d87;border-radius:12px;text-align:center;padding:13px 6px;font-size:12px;font-weight:700;color:#b7d6e2}.progress span.active{color:#081d2a;background:linear-gradient(90deg,#7eebdd,#b1a4ff);border-color:transparent}
.columns{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(260px,.7fr);gap:22px}
.panel{background:linear-gradient(145deg,#121f37ed,#11182ae8);border:1px solid #4b6687;border-radius:25px;padding:clamp(22px,4vw,40px);box-shadow:0 20px 70px #0006}
.eyebrow{font-size:11px;font-weight:900;letter-spacing:.2em;color:#95e7ed}h1{font-size:clamp(32px,5vw,56px);line-height:1.08;letter-spacing:-.06em;margin:16px 0}h2{font-size:22px;letter-spacing:-.04em}
p,li{font-size:14px;line-height:1.7;color:#bed0e4}
label{display:block;margin:20px 0 7px;font-size:13px;font-weight:700}input{width:100%;padding:14px 15px;border-radius:12px;background:#0c1527;color:#fff;border:1px solid #577197;font-size:15px}
input:focus{outline:2px solid #88efd9;outline-offset:2px}
button{border:0;width:100%;margin-top:25px;padding:16px;border-radius:13px;background:linear-gradient(100deg,#7bf0dd,#a7a0ff);color:#061b2a;font-size:16px;font-weight:900;cursor:pointer;transition:transform .2s,filter .2s}
button:hover{transform:translateY(-2px);filter:brightness(1.08)}
.error{padding:13px 15px;border-radius:12px;background:#80254533;color:#ffdeea;border:1px solid #ff98b6}
.note{border-radius:14px;padding:15px;background:#3c607d22;border:1px solid #6ae6ec44}
ul{padding-left:20px}.success{color:#acf699}
@media(max-width:800px){.columns{grid-template-columns:1fr}.wrap{padding:18px 12px 45px}.header{margin-bottom:20px}}
@media(prefers-reduced-motion:reduce){*,*:before,*:after{transition:none!important;animation:none!important}}
</style>
</head>
<body><div class="wrap">
<header class="header"><div class="brand">▦ 84PAD<i>.</i></div><div class="tag">PARMTECH · CONTROL STUDIO</div></header>
<div class="progress" aria-label="Installation progress"><span>✓ Hosting</span><span>✓ Database</span><span class="active">03 · Owner account</span></div>
<div class="columns"><main class="panel">
<div class="eyebrow">PRIVATE · FINAL SETUP</div>
<h1>Make it yours.</h1>
<p>Your hosting configuration is complete. Create your first Owner account to unlock the 84PAD Control Studio.</p>
@if($errors->any())<p class="error" role="alert">{{ $errors->first() }}</p>@endif
<form method="post" action="{{ route('setup.store') }}" autocomplete="off">@csrf
<label for="token">Private first-run token</label>
<input id="token" type="password" name="setup_token" autocomplete="off" required>
<label for="name">Owner display name</label>
<input id="name" name="name" value="{{ old('name') }}" maxlength="120" minlength="2" required autocomplete="name">
<label for="email">Owner email</label>
<input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="255" required autocomplete="email">
<label for="password">Owner password (12+ characters)</label>
<input id="password" type="password" name="password" autocomplete="new-password" minlength="12" required>
<label for="confirm">Confirm password</label>
<input id="confirm" type="password" name="password_confirmation" autocomplete="new-password" minlength="12" required>
<button type="submit">Finish secure installation →</button>
</form>
<p style="font-size:12px">The setup wizard closes once your Owner account is created. Keep your private token and password out of chat, source control and screenshots.</p>
</main>
<aside class="panel">
<div class="eyebrow">WHAT HAPPENS NEXT</div><h2>Welcome to your studio.</h2>
<ul><li><strong class="success">Secure Owner login</strong> with access to Core settings.</li>
<li><strong class="success">Guided first-login tour</strong> of Control Studio.</li>
<li><strong class="success">Plugin-first architecture</strong> with individually installed extensions.</li>
<li><strong class="success">Optional cPanel connection</strong> inside Core Settings, stored encrypted with per-plugin permissions.</li></ul>
<div class="note"><p style="margin:0">This installer checks prerequisites, runs database migrations and creates the initial Owner. Do not interrupt it or reuse an existing website database.</p></div>
</aside>
</div></div></body></html>
