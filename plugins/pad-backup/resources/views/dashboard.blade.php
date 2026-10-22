<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>84PAD Backup · Control Studio</title>
<style>
:root{color-scheme:dark;font-family:Inter,system-ui,-apple-system,sans-serif;background:#090b1b;color:#e8f5ff}
*{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(ellipse at 8% 3%,#271765 0,transparent 39%),radial-gradient(ellipse at 90% 0%,#063955 0,transparent 35%),#090b1b}
main{max-width:1340px;margin:auto;padding:26px clamp(16px,4vw,60px) 65px}
a{color:#80edff}header{display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap}
.brand{letter-spacing:.14em;font-weight:900;font-size:13px;color:#89eaff}
h1{font-size:clamp(35px,5.5vw,66px);letter-spacing:-.045em;margin:32px 0 4px;line-height:1.06}
h1 span{color:#86edff;text-shadow:0 0 35px #09c6fe70}p{line-height:1.6;color:#b3c5df}
.tag{display:inline-flex;padding:6px 12px;border:1px solid #6be7ff58;color:#9df2ff;border-radius:99px;background:#4deaff0e;font-size:12px}
.grid{display:grid;grid-template-columns:repeat(12,1fr);gap:18px;margin-top:24px}.panel{grid-column:span 6;border:1px solid #788ff540;border-radius:22px;background:linear-gradient(145deg,#151a39ed,#11172adf);box-shadow:0 18px 60px #0006;padding:24px;min-width:0}
.wide{grid-column:span 12}h2{margin:0 0 14px;font-size:21px}label{display:block;margin:12px 0 6px;font-size:13px;color:#cfdef0}
button,select,input[type=file]{background:#151e3d;border:1px solid #687db4;color:#e6f5ff;border-radius:11px;padding:12px 13px;font:inherit;max-width:100%}
button{cursor:pointer;font-weight:700;background:linear-gradient(110deg,#2063b7,#6c35ae);border:1px solid #8eabef;box-shadow:0 8px 20px #080b238c}
button:hover{filter:brightness(1.18)}button:focus-visible,a:focus-visible,input:focus-visible,select:focus-visible{outline:3px solid #78edff;outline-offset:3px}
.danger{background:#4b1834;border-color:#9d4268}.ghost{background:#151d38;border-color:#4a608d}
.actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
form{margin:0}.hint{font-size:13px;color:#9bafd0}.notice{margin-top:15px;padding:14px 17px;border-radius:13px;border:1px solid #4fc4d5;background:#09334a;color:#d2fbff}
.error{border-color:#c06f91;background:#42233d}.backups{width:100%;border-collapse:collapse}th,td{padding:13px 8px;border-bottom:1px solid #34436e;text-align:left;font-size:13px}th{color:#82e9fa}td{color:#dfedff}.scroll{overflow-x:auto}
small{color:#8facce}.warning{border-left:3px solid #ecb66f;padding:12px 18px;background:#45311836}
@media(max-width:820px){.panel{grid-column:span 12}.backups td{min-width:100px}.actions button{padding:9px}}
@media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important;transition:none!important}}
</style>
</head>
<body>
<main>
<header><div class="brand">84PAD / PARMTECH · CONTROL STUDIO</div><a href="/dashboard">← Back to Control Studio</a></header>
<div style="margin-top:30px" class="tag">PLUGIN v0.1 · DEVELOPMENT PREVIEW</div>
<h1>Backup <span>everything.</span></h1>
<p>Encrypted snapshots of 84PAD application files, installed plugins, media and MySQL/MariaDB tables. Flexible schedules, built-in 60-day cleanup and verified portable ZIP transfers.</p>
@if(session('backup_notice'))<div class="notice" role="status">{{ session('backup_notice') }}</div>@endif
@if(session('backup_error'))<div class="notice error" role="alert">{{ session('backup_error') }}</div>@endif
@if($errors->any())<div class="notice error" role="alert">{{ $errors->first() }}</div>@endif
<div class="grid">
<section class="panel"><h2>Manual snapshot</h2><p>Capture your current installation now. Contents are encrypted with a key derived from this installation's APP_KEY.</p>
<form method="post" action="/dashboard/extensions/pad-backup/run">@csrf
<button type="submit">✦ Create backup now</button></form>
<p class="hint">Large websites need temporary free disk space for the SQL dump, ZIP and encrypted archive.</p>
</section>
<section class="panel"><h2>Automatic schedule</h2><form method="post" action="/dashboard/extensions/pad-backup/settings">@csrf
<label for="frequency">Run backups</label>
<select id="frequency" name="frequency">
@foreach(['off'=>'Disabled','hourly'=>'Every hour','daily'=>'Every day','weekly'=>'Every week','monthly'=>'Every month'] as $value => $label)
<option value="{{ $value }}" @selected($settings['frequency']===$value)>{{ $label }}</option>
@endforeach
</select>
<p class="hint">Retention: exactly 60 days. Cleanup runs on scheduled maintenance ticks even when automatic backups are disabled.</p>
<button type="submit">Save schedule</button></form>
</section>
<section class="panel"><h2>Import backup</h2><p>Upload a previously exported 84PAD ZIP. The archive is validated before being encrypted in private storage.</p>
<form method="post" enctype="multipart/form-data" action="/dashboard/extensions/pad-backup/import">@csrf
<label for="archive">Portable backup .zip</label><input type="file" name="archive" id="archive" accept=".zip,application/zip" required>
<div style="height:12px"></div><button type="submit">↑ Verify and import</button></form>
<p class="hint">Upload size depends on your host's PHP configuration. Import does not overwrite the live website.</p></section>
<section class="panel"><h2>Recovery safety</h2>
<p>All stored backup containers are encrypted and verified. Portable exports contain credentials in their decrypted ZIP, so download only over HTTPS and store securely.</p>
<div class="warning"><strong>Full restore is not yet enabled.</strong><p class="hint">The next development phase adds an isolated maintenance-mode restore with database/file rollback and independent recovery testing.</p></div>
<p class="hint">Automatic jobs require a working hosting cron that runs Laravel schedule:run every minute. The Core's cPanel API token is never required.</p>
</section>
<section class="panel wide"><h2>Stored snapshots</h2>
@if(count($backups)===0)<p>No backups stored. Create the first encrypted snapshot.</p>
@else
<div class="scroll"><table class="backups"><thead><tr><th>Created (UTC)</th><th>Type</th><th>Size</th><th>Stored until (UTC)</th><th>Actions</th></tr></thead><tbody>
@foreach($backups as $backup)
<tr><td>{{ gmdate('Y-m-d H:i', (int)$backup['created_at']) }}</td>
<td>{{ ucfirst($backup['reason']) }}</td>
<td>{{ number_format(($backup['bytes'] ?? 0)/1048576,1) }} MB</td>
<td>{{ gmdate('Y-m-d H:i', (int)$backup['created_at']+60*86400) }}</td>
<td><div class="actions">
<a href="/dashboard/extensions/pad-backup/export?id={{ urlencode($backup['id']) }}">Export ZIP</a>
<form method="post" action="/dashboard/extensions/pad-backup/verify">@csrf
<input type="hidden" name="id" value="{{ $backup['id'] }}"><button class="ghost" type="submit">Verify</button></form>
<form method="post" action="/dashboard/extensions/pad-backup/delete" onsubmit="return confirm('Permanently delete this backup?')">@csrf
<input type="hidden" name="id" value="{{ $backup['id'] }}"><button class="danger" type="submit">Delete</button></form>
</div></td></tr>
@endforeach
</tbody></table></div>
@endif
</section></div>
<footer><p class="hint">84PAD Backup · Built by ParmTech · Stored under non-public storage/app/private/84pad-backups · No external API required.</p></footer>
</main></body></html>
