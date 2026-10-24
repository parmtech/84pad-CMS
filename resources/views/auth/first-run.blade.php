<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<meta name="color-scheme" content="dark">
<title>Create Owner · 84PAD Control Studio</title>
<link rel="stylesheet" href="/css/84pad-installer.css">
</head>
<body>
<div class="orbs" aria-hidden="true"><i></i><i></i></div>
<div class="site">
  <header class="site-header">
    <div class="brand"><span class="brand-icon" aria-hidden="true"></span><span>84PAD<span class="brand-dot">.</span></span></div>
    <div class="header-side"><span class="header-label">PARMTECH / CONTROL STUDIO</span><span class="status-pill">PRIVATE INSTALLATION</span></div>
  </header>
  <section class="hero" aria-labelledby="owner-title">
    <div class="kicker">YOUR WORKSPACE IS ALMOST READY</div>
    <h1 id="owner-title">Make it <em>yours.</em></h1>
    <p>Your hosting is configured. Set up your first Owner account to unlock the Control Studio and start building with 84PAD.</p>
  </section>
  <nav class="stagebar" aria-label="Setup progress">
    <div class="stage complete"><span class="number">✓</span><span>Hosting connection</span></div>
    <div class="stage complete"><span class="number">✓</span><span>Database setup</span></div>
    <div class="stage active" aria-current="step"><span class="number">03</span><span>Owner account</span></div>
  </nav>
  <div class="content">
    <main class="panel main-panel">
      <div class="section-top"><span class="eyebrow">84PAD / FINAL SETUP</span><span class="section-count">STEP 03 OF 03</span></div>
      <h2>Create your Owner account.</h2>
      <p class="intro">This will be the primary administrator for your installation. Choose a strong, unique password and keep your private setup token secure.</p>
      @if($errors->any())<p role="alert" class="error">{{ $errors->first() }}</p>@endif
      <form class="owner-form" method="post" action="{{ route('setup.store') }}" autocomplete="off">
        @csrf
        <div class="field">
          <label for="token">Private first-run token</label>
          <input id="token" type="password" name="setup_token" autocomplete="off" required aria-describedby="owner-token-hint">
          <small id="owner-token-hint">Enter the same private token stored in storage/app/private/first-run-token. This is not your cPanel API token.</small>
        </div>
        <div class="divider"></div>
        <div class="field">
          <label for="name">Owner display name</label>
          <input id="name" name="name" value="{{ old('name') }}" maxlength="120" minlength="2" autocomplete="name" required>
        </div>
        <div class="field">
          <label for="email">Owner email address</label>
          <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>
        </div>
        <div class="grid2">
          <div class="field">
            <label for="password">Strong password</label>
            <input id="password" type="password" name="password" minlength="12" maxlength="1024" autocomplete="new-password" required>
            <small>At least 12 characters. A unique passphrase is recommended.</small>
          </div>
          <div class="field">
            <label for="confirm">Confirm password</label>
            <input id="confirm" type="password" name="password_confirmation" minlength="12" maxlength="1024" autocomplete="new-password" required>
          </div>
        </div>
        <button class="btn primary" type="submit">Complete secure installation →</button>
      </form>
      <p class="trust">One-time Owner creation · Protected setup token · Automatic installer lock after success</p>
    </main>
    <aside class="panel sidebar-panel" aria-label="Next steps">
      <div class="sidebar-heading"><span class="sidebar-icon" aria-hidden="true">✦</span><div><h2>Welcome to your studio.</h2><p>Your next chapter starts here.</p></div></div>
      <div class="eyebrow">AFTER INSTALLATION</div>
      <ul class="checklist">
        <li><span class="check" aria-hidden="true">✓</span><span><strong>Secure Owner login</strong><br>Manage your Core and access controls.</span></li>
        <li><span class="check" aria-hidden="true">✓</span><span><strong>Guided Control Studio tour</strong><br>Get familiar with your new workspace.</span></li>
        <li><span class="check" aria-hidden="true">✓</span><span><strong>Independent plugin ecosystem</strong><br>Enable only the capabilities you need.</span></li>
        <li><span class="check" aria-hidden="true">✓</span><span><strong>Optional hosting integrations</strong><br>Configure cPanel with encrypted credentials and plugin-level permissions later.</span></li>
      </ul>
      <div class="notice secret-note"><span class="secret-icon" aria-hidden="true">✧</span><span><strong>Your data stays yours.</strong> Initial migrations only target your dedicated empty database. The installer becomes unavailable after Owner setup.</span></div>
      <div class="helper">
        <div class="helper-head">SECURITY NOTE</div>
        <p>Do not share your setup token, password, API keys or database credentials in chat, screenshots or source control.</p>
      </div>
    </aside>
  </div>
  <footer class="footer"><span>© ParmTech · 84PAD Control Studio</span><strong>BUILD ANYTHING. PLUG EVERYTHING.</strong></footer>
</div>
</body>
</html>
