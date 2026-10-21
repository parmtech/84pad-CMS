<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title>Control Studio | 84PAD</title>
<style>
:root{color-scheme:dark;font-family:system-ui,sans-serif}*{box-sizing:border-box}body{margin:0;color:#eaf4ff;background:#090f1e;min-height:100vh}
header{padding:18px clamp(20px,4vw,62px);display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #274163;background:#111d32}
.brand{font-size:28px;font-weight:900;letter-spacing:1px}small{color:#86cfe9;text-transform:uppercase;letter-spacing:2px}
button{color:#eaf4ff;background:#213855;border:1px solid #3f6286;border-radius:12px;padding:10px 18px}
main{padding:clamp(20px,5vw,70px);max-width:1500px;margin:auto}h1{font-size:clamp(34px,5vw,66px);margin:12px 0}
p{color:#adc0d7}section{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-top:36px}
article{border:1px solid #2b4865;background:linear-gradient(150deg,#192d47,#0f1a2c);border-radius:22px;padding:28px}
article b{font-size:22px;display:block;margin-bottom:10px}.pending{color:#ecc078;font-size:13px}
</style></head>
<body><header><div class="brand">84PAD<span style="color:#72d7ee">.</span></div>
<form method="post" action="{{ route('logout') }}">@csrf<button type="submit">Sign out</button></form></header>
<main><small>PARMTECH // 84PAD CORE</small><h1>Control Studio</h1>
<p>Welcome, {{ $user->name }}. Core development preview; optional systems arrive as plugins.</p>
<section>
<article><b>Core status</b><p>Owner login foundation</p><span class="pending">Not production-ready</span></article>
<article><b>SSL God</b><p>Certificate monitoring and renewal</p><span class="pending">Optional plugin · not installed</span></article>
<article><b>Domain Email</b><p>Inbox and accounts</p><span class="pending">Optional plugin · not installed</span></article>
<article><b>File Manager</b><p>Safe site file controls</p><span class="pending">Optional plugin · not installed</span></article>
<article><b>Backup & Restore</b><p>Scheduled recovery</p><span class="pending">Optional plugin · not installed</span></article>
</section></main></body></html>
