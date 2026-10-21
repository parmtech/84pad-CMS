<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Security | 84PAD Control Studio</title>
<style>
:root{color-scheme:dark;font-family:system-ui,sans-serif}
*{box-sizing:border-box}
body{margin:0;background:radial-gradient(circle at 20% 10%,#233768,#090e1a 75%);color:#eef6ff;min-height:100vh;display:grid;place-items:center}
main{width:min(470px,94vw);padding:clamp(24px,5vw,38px);border:1px solid #345777;border-radius:24px;background:#101e33}
small{color:#80dcea;letter-spacing:.16em;font-weight:bold}
h1{font-size:clamp(32px,6vw,46px);margin:16px 0}
label{display:block;margin:20px 0 8px}
input{width:100%;padding:14px;border-radius:12px;border:1px solid #52708d;background:#0c1627;color:white}
button{width:100%;margin-top:25px;padding:14px;background:#76d9f2;color:#062031;border:0;border-radius:12px;font-size:16px;font-weight:800}
a{color:#9ce5ff}.error{color:#ffbdbd}
</style></head>
<body><main><small>84PAD / CONTROL STUDIO</small><h1>Account security</h1>
<p>Update your owner password. Use at least 12 characters.</p>
@if($errors->any())<p class="error" role="alert">{{ $errors->first() }}</p>@endif
<form method="post" action="{{ route('password.update') }}">@csrf
<label for="old">Current password</label><input id="old" name="current_password" type="password" autocomplete="current-password" required>
<label for="new">New password</label><input id="new" name="password" type="password" autocomplete="new-password" minlength="12" required>
<label for="confirm">Confirm new password</label><input id="confirm" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required>
<button type="submit">Update password</button></form>
<p><a href="{{ route('dashboard') }}">Back to Control Studio</a></p></main></body></html>
