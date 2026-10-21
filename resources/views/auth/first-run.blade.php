<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>First-run setup | 84PAD</title>
<style>
:root{color-scheme:dark;font-family:system-ui,sans-serif}*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:grid;place-items:center;color:#f4f8ff;background:radial-gradient(circle at 15% 0%,#294b73,#0b1020 65%)}
main{width:min(520px,94vw);margin:20px;padding:clamp(25px,6vw,40px);background:#0c192dcf;border:1px solid #39638b;border-radius:24px;box-shadow:0 20px 70px #0007}
small{color:#85e0fa;letter-spacing:.16em;font-weight:800}
h1{font-size:clamp(36px,6vw,55px);margin:16px 0}
p{color:#becde0;line-height:1.6}
label{display:block;margin:18px 0 7px;font-weight:650}
input{width:100%;padding:13px;border-radius:11px;background:#081423;color:white;border:1px solid #456486}
button{width:100%;margin-top:24px;padding:14px;border-radius:12px;border:none;background:#76e6db;color:#08272d;font-size:16px;font-weight:800}
.error{color:#ffc7cb}
</style></head>
<body><main><small>PARMTECH · 84PAD CORE</small><h1>Initialize your space.</h1>
<p>This one-time setup requires the secret token you configured on the server. Make sure database settings and APP_KEY are configured before continuing.</p>
@if($errors->any())<p class="error" role="alert">{{ $errors->first() }}</p>@endif
<form method="post" action="{{ route('setup.store') }}" autocomplete="off">@csrf
<label for="token">Private setup token</label>
<input id="token" type="password" name="setup_token" autocomplete="off" required>
<label for="name">Owner display name</label>
<input id="name" name="name" value="{{ old('name') }}" maxlength="120" required>
<label for="email">Owner email</label>
<input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="255" required>
<label for="password">Owner password (12+ characters)</label>
<input id="password" type="password" name="password" autocomplete="new-password" minlength="12" required>
<label for="confirm">Confirm password</label>
<input id="confirm" type="password" name="password_confirmation" autocomplete="new-password" minlength="12" required>
<button type="submit">Install 84PAD Core</button></form>
<p>After setup the installer locks automatically. Never share the setup token.</p>
</main></body></html>
