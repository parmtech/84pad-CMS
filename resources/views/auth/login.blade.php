<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Sign in | 84PAD</title>
<style>
:root{color-scheme:dark;font-family:system-ui,sans-serif}*{box-sizing:border-box}
body{background:radial-gradient(circle at top,#253e6a,#090e19 65%);color:#eaf1ff;min-height:100vh;display:grid;place-items:center;margin:0}
main{width:min(430px,94vw);border:1px solid #38547e;border-radius:24px;background:#0d1729ed;padding:34px}
h1{font-size:42px;letter-spacing:.03em;margin:0}.note{color:#aac2da}
label{display:block;margin:18px 0 8px}input{width:100%;padding:14px;border-radius:12px;border:1px solid #4a627c;background:#0c1525;color:white}
button{margin-top:20px;padding:13px;border:0;border-radius:12px;font-weight:800;background:#65c8ed;color:#081320;width:100%;cursor:pointer}
.error{color:#ffb7b7}small{color:#87aad0}
</style></head>
<body><main><small>PARMTECH · CONTROL STUDIO</small><h1>84PAD.</h1>
<p class="note">Sign in to your secure workspace.</p>
<form method="post" action="{{ route('login.submit') }}">@csrf
<label for="email">Owner email</label><input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required>
<label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required>
@if($errors->any())<p class="error">{{ $errors->first() }}</p>@endif
<button type="submit">Sign in</button></form>
</main></body></html>
