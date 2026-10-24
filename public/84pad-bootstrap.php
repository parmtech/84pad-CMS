<?php
/**
 * 84PAD staging first-run host bootstrap.
 * Standalone PHP so cPanel File Manager users can configure a fresh site
 * before Laravel has a database connection or an application key.
 *
 * SECURITY: A per-browser private File Manager proof filename protects setup.
 * No manually entered setup key is required.
 * It is disabled permanently as soon as .env is written.
 */
declare(strict_types=1);

header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'none'; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
header('Referrer-Policy: no-referrer');

$rootConfig = __DIR__.'/.84pad-root.php';
$root = is_file($rootConfig) && !is_link($rootConfig) ? require $rootConfig : dirname(__DIR__);
if (!is_string($root) || !is_dir($root)) {
    http_response_code(503);
    exit('84PAD private application root is unavailable.');
}
$private = $root.'/storage/app/private';
$envPath = $root.'/.env';

$deny = static function (): never {
    http_response_code(404);
    exit;
};

if (is_file($envPath) || is_link($envPath)
    || ! is_dir($private) || is_link($private)) {
    $deny();
}

// Do not trust X-Forwarded-Proto from arbitrary clients.
$https = ($_SERVER['HTTPS'] ?? '') === 'on'
    || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
if (! $https) {
    http_response_code(403);
    exit('84PAD setup requires a direct HTTPS connection.');
}

$host = strtolower((string) ($_SERVER['SERVER_NAME'] ?? ''));
if (strlen($host) > 253
    || ! preg_match('/^[a-z0-9][a-z0-9.-]*$/D', $host)) {
    $deny();
}

// Check host readiness before creating any database resources or .env.
// Installer prerequisites are reported as booleans only (no paths or secrets).
$hostChecks = [
    'PHP 8.3 or newer' => PHP_VERSION_ID >= 80300,
    'PDO MySQL' => extension_loaded('pdo_mysql'),
    'OpenSSL' => extension_loaded('openssl'),
    'mbstring' => extension_loaded('mbstring'),
    'Tokenizer' => extension_loaded('tokenizer'),
    'XML' => extension_loaded('xml'),
    'Composer dependencies' => is_file($root.'/vendor/autoload.php'),
    'Application directory writable' => is_writable($root),
    'Private storage writable' => is_writable($private),
    'Laravel storage writable' => is_dir($root.'/storage') && is_writable($root.'/storage'),
    'Laravel bootstrap cache writable' => is_dir($root.'/bootstrap/cache')
        && is_writable($root.'/bootstrap/cache'),
];
$hostReady = ! in_array(false, $hostChecks, true);

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_samesite', 'Strict');
session_name('84PAD_SETUP');
session_start();

if (! isset($_SESSION['csrf']) || ! is_string($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
if (!isset($_SESSION['owner_proof']) || !is_string($_SESSION['owner_proof'])
    || !preg_match('/^[a-f0-9]{24}$/D', $_SESSION['owner_proof'])) {
    $_SESSION['owner_proof'] = bin2hex(random_bytes(12));
}
$proofFile = 'allow-84pad-'.$_SESSION['owner_proof'].'.txt';
$proofPath = $private.'/'.$proofFile;
$problem = null;
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'POST' && ! $hostReady) {
    http_response_code(503);
    $problem = 'Hosting requirements failed. Fix the checks below before configuring 84PAD.';
} elseif ($method === 'POST') {
    $attempts = (int) ($_SESSION['setup_attempts'] ?? 0);
    $firstTry = (int) ($_SESSION['setup_first_try'] ?? time());
    if (time() - $firstTry > 900) {
        $attempts = 0;
        $firstTry = time();
    }
    if ($attempts >= 5) {
        http_response_code(429);
        exit('Too many setup attempts. Try again later.');
    }
    $_SESSION['setup_first_try'] = $firstTry;
    $_SESSION['setup_attempts'] = $attempts + 1;

    $csrf = (string) ($_POST['csrf'] ?? '');
    if (!hash_equals($_SESSION['csrf'], $csrf) || is_link($proofPath) || !is_file($proofPath)) {
        http_response_code(403);
        $problem = 'Hosting ownership is not verified. Create the shown private proof file in cPanel File Manager.';
    } else {
        $dbHost = (string) ($_POST['db_host'] ?? '');
        $dbPort = (string) ($_POST['db_port'] ?? '3306');
        $dbName = (string) ($_POST['db_name'] ?? '');
        $dbUser = (string) ($_POST['db_user'] ?? '');
        $dbPass = (string) ($_POST['db_password'] ?? '');
        $mode = (string) ($_POST['connection_mode'] ?? 'manual');

        if (! in_array($mode, ['manual', 'cpanel'], true)) {
            $problem = 'Choose a supported database connection method.';
        } elseif ($mode === 'cpanel') {
            try {
                // The API token is used for this request only, never stored in .env.
                // Ensure the database driver exists before creating host resources.
                if (! extension_loaded('pdo_mysql')) {
                    throw new RuntimeException('PDO MySQL is missing. Select a supported PHP version first.');
                }
                if (! preg_match('/^[a-zA-Z0-9.-]{1,253}$/D', $dbHost)
                    || ! ctype_digit($dbPort) || (int) $dbPort < 1 || (int) $dbPort > 65535) {
                    throw new RuntimeException('Enter a valid MySQL hostname and port before provisioning.');
                }
                require_once $root.'/app/Support/Installer/CpanelDatabaseBootstrap.php';
                // Serialize cPanel provisioning to avoid overlapping database side effects.
                $apiLock = @fopen($private.'/cpanel-provision.lock', 'c');
                if ($apiLock === false || ! flock($apiLock, LOCK_EX | LOCK_NB)) {
                    throw new RuntimeException('Another hosting setup is in progress.');
                }
                try {
                    @chmod($private.'/cpanel-provision.lock', 0600);
                    $created = \App\Support\Installer\CpanelDatabaseBootstrap::provision($_POST);
                } finally {
                    flock($apiLock, LOCK_UN);
                    fclose($apiLock);
                }
                $dbHost = $created['db_host'];
                $dbPort = $created['db_port'];
                $dbName = $created['db_name'];
                $dbUser = $created['db_user'];
                $dbPass = $created['db_password'];
                unset($created, $_POST['cpanel_token']);
            } catch (Throwable $e) {
                // Do not reveal cPanel token, database password, URL or response.
                $problem = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : 'Automatic provisioning was unavailable. Check cPanel or select manual setup.';
            }
        }

        if ($problem !== null) {
            // A failed provisioning step must never create .env.
        } elseif (! preg_match('/^[a-zA-Z0-9.-]{1,253}$/D', $dbHost)
            || ! ctype_digit($dbPort)
            || (int) $dbPort < 1 || (int) $dbPort > 65535
            || ! preg_match('/^[a-zA-Z0-9_]{1,64}$/D', $dbName)
            || ! preg_match('/^[a-zA-Z0-9_]{1,64}$/D', $dbUser)
            || strlen($dbPass) > 512
            || str_contains($dbPass, "\n")
            || str_contains($dbPass, "\r")
            || str_contains($dbPass, "\0")) {
            $problem = 'Database settings failed validation.';
        } else {
            try {
                if (! extension_loaded('pdo_mysql')) {
                    throw new RuntimeException('Required PHP extension missing.');
                }
                $pdo = new PDO(
                    'mysql:host='.$dbHost.';port='.$dbPort.';dbname='.$dbName.';charset=utf8mb4',
                    $dbUser,
                    $dbPass,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_TIMEOUT => 5,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
                // A brand-new Core must never migrate a pre-existing website DB.
                if ($pdo->query('SHOW TABLES')->fetchColumn() !== false) {
                    throw new RuntimeException('Database is not empty.');
                }

                $quote = static function (string $value): string {
                    return '"'.str_replace(
                        ['\\', '"', '$'],
                        ['\\\\', '\\"', '\\$'],
                        $value
                    ).'"';
                };
                // The server generates and protects its own internal setup key.
                $token = bin2hex(random_bytes(32));
                $envLines = [
                    'APP_NAME="84PAD"',
                    'APP_ENV=production',
                    'APP_DEBUG=false',
                    'APP_KEY=base64:'.base64_encode(random_bytes(32)),
                    'APP_URL=https://'.$host,
                    'APP_LOCALE=en',
                    'APP_FALLBACK_LOCALE=en',
                    'LOG_CHANNEL=stack',
                    'LOG_LEVEL=warning',
                    'DB_CONNECTION=mysql',
                    'DB_HOST='.$quote($dbHost),
                    'DB_PORT='.$dbPort,
                    'DB_DATABASE='.$quote($dbName),
                    'DB_USERNAME='.$quote($dbUser),
                    'DB_PASSWORD='.$quote($dbPass),
                    'SESSION_DRIVER=file',
                    'SESSION_SECURE_COOKIE=true',
                    'CACHE_STORE=file',
                    'QUEUE_CONNECTION=sync',
                    'FILESYSTEM_DISK=local',
                    'MAIL_MAILER=log',
                    'PAD_SETUP_TOKEN='.$quote($token),
                ];

                $lock = fopen($private.'/first-run-lock', 'c');
                if ($lock === false || ! flock($lock, LOCK_EX)) {
                    throw new RuntimeException('Cannot obtain setup lock.');
                }
                try {
                    if (file_exists($envPath) || is_link($envPath)) {
                        $deny();
                    }

                    // Exclusive create avoids overwriting an existing .env.
                    $handle = fopen($envPath, 'x');
                    if ($handle === false) {
                        throw new RuntimeException('Cannot create environment file.');
                    }
                    chmod($envPath, 0600);
                    $success = false;
                    try {
                        $bytes = implode("\n", $envLines)."\n";
                        $success = fwrite($handle, $bytes) === strlen($bytes)
                            && fflush($handle);
                    } finally {
                        fclose($handle);
                        if (! $success) {
                            @unlink($envPath);
                        }
                    }
                    if (! $success) {
                        throw new RuntimeException('Could not write environment file.');
                    }
                } finally {
                    flock($lock, LOCK_UN);
                    fclose($lock);
                }

                unset($dbPass, $envLines);
                setcookie('84pad_owner_setup', $token, [
                    'expires' => time() + 1800, 'path' => '/setup',
                    'secure' => true, 'httponly' => true, 'samesite' => 'Strict',
                ]);
                @unlink($proofPath);
                session_regenerate_id(true);
                header('Location: /setup', true, 303);
                exit;
            } catch (Throwable $e) {
                // Never expose SQL connection errors, credentials or paths.
                $problem = 'Could not initialise this empty database. Check hosting settings and permissions.';
            }
        }
    }
}

if ($method !== 'GET' && $method !== 'POST') {
    http_response_code(405);
    header('Allow: GET, POST');
    exit;
}

function h(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<meta name="color-scheme" content="dark">
<title>Get Started · 84PAD Control Studio</title>
<link rel="stylesheet" href="/css/84pad-installer.css">
</head>
<body>
<div class="orbs" aria-hidden="true"><i></i><i></i></div>
<div class="site">
<header class="site-header">
  <a class="brand" href="#" aria-label="84PAD Setup">
    <span class="brand-icon" aria-hidden="true"></span><span>84PAD<span class="brand-dot">.</span></span>
  </a>
  <div class="header-side">
    <span class="header-label">PARMTECH / CONTROL STUDIO</span>
    <span class="status-pill">PRIVATE INSTALLATION</span>
  </div>
</header>
<section class="hero" aria-labelledby="setup-title">
  <div class="kicker">WELCOME TO YOUR NEW CREATIVE ENGINE</div>
  <h1 id="setup-title">Your space begins here.</h1>
  <p>Three simple steps to connect your hosting and prepare 84PAD. Set up a secure foundation now—build anything and plug everything in later.</p>
</section>
<nav class="stagebar" aria-label="Setup progress">
  <div class="stage active" data-indicator="0" aria-current="step"><span class="number">01</span><span>Hosting connection</span></div>
  <div class="stage" data-indicator="1"><span class="number">02</span><span>Database setup</span></div>
  <div class="stage" data-indicator="2"><span class="number">03</span><span>Secure initialize</span></div>
</nav>
<div class="content">
<main class="panel main-panel" aria-label="Installation configuration">
  <div class="section-top"><span class="eyebrow">84PAD / SETUP WIZARD</span><span class="section-count" data-step-count>STEP 01 OF 03</span></div>
  <?php if ($problem !== null): ?><p role="alert" class="error"><?= h($problem) ?></p><?php endif; ?>
  <form method="post" autocomplete="off" data-wizard data-host-ready="<?= $hostReady ? 'yes' : 'no' ?>">
  <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
  <section data-step="0" class="visible" aria-labelledby="step-hosting">
    <h2 id="step-hosting" tabindex="-1">Choose your connection.</h2>
    <p class="intro">Select how 84PAD should connect to your database. cPanel automation is optional—you remain in control of your hosting credentials.</p>
    <div class="modes" role="radiogroup" aria-label="Database setup method">
      <label class="mode">
        <input type="radio" name="connection_mode" value="manual" checked>
        <span class="mode-icon" aria-hidden="true">◇</span>
        <strong>Manual MySQL</strong>
        <small>Use an empty database you've already created in cPanel or another hosting control panel.</small>
      </label>
      <label class="mode">
        <input type="radio" name="connection_mode" value="cpanel">
        <span class="mode-icon" aria-hidden="true">⌘</span>
        <strong>Automatic cPanel API</strong>
        <small>Create a dedicated database and user automatically with a temporary API token.</small>
      </label>
    </div>
    <div class="notice secret-note"><span class="secret-icon" aria-hidden="true">✧</span><span><strong>Built for privacy.</strong> Only the Laravel <code>public/</code> directory must be web-accessible. Keep <code>.env</code>, storage and credentials outside the public web root.</span></div>
    <section class="readiness" aria-label="Server readiness checks">
      <div class="readiness-head">
        <h3>Hosting readiness</h3><span class="health <?= $hostReady ? '' : 'warn' ?>"><?= $hostReady ? 'ALL CHECKS PASSED' : 'ACTION REQUIRED' ?></span>
      </div>
      <ul>
        <?php foreach ($hostChecks as $label => $passed): ?>
          <li class="<?= $passed ? 'passed' : 'failed' ?>"><span class="dot" aria-hidden="true"><?= $passed ? '✓' : '!' ?></span><?= h((string) $label) ?> — <?= $passed ? 'Ready' : 'Fix required' ?></li>
        <?php endforeach; ?>
      </ul>
      <?php if (! $hostReady): ?><p role="alert" class="progress-help">Some requirements are missing. Check the PHP version, extensions, Composer runtime and private directory permissions before proceeding.</p><?php endif; ?>
    </section>
  </section>
  <section data-step="1" aria-labelledby="step-database">
    <h2 id="step-database" tabindex="-1">Connect your database.</h2>
    <p class="intro">84PAD uses a separate MySQL or MariaDB database so your existing websites and data remain untouched.</p>
    <div data-mode-fields="manual">
      <div class="notice">Open <strong>Databases → Database Wizard / MySQL Databases</strong> in your hosting dashboard. Create a new empty database and database user, and assign privileges on that database.</div>
      <div class="grid2">
        <div class="field"><label for="db-name">Database name</label><input id="db-name" name="db_name" maxlength="64" placeholder="account_84pad" autocomplete="off"><small>Include the full account prefix if your host adds one.</small></div>
        <div class="field"><label for="db-user">Database username</label><input id="db-user" name="db_user" maxlength="64" placeholder="account_paduser" autocomplete="off"></div>
      </div>
    </div>
    <div data-mode-fields="cpanel">
      <div class="notice">Open <strong>cPanel → Security → Manage API Tokens → Create</strong>. Use a short-lived token, your cPanel username and your server hostname. <a href="https://docs.cpanel.net/cpanel/security/manage-api-tokens-in-cpanel/" target="_blank" rel="noopener noreferrer">Official cPanel instructions ↗</a></div>
      <div class="grid2">
        <div class="field"><label for="cp-host">cPanel server hostname</label><input id="cp-host" name="cpanel_host" maxlength="253" placeholder="server.example.com" autocomplete="off"><small>Hostname only. No https:// or :2083.</small></div>
        <div class="field"><label for="cp-user">cPanel username</label><input id="cp-user" name="cpanel_username" maxlength="16" placeholder="hostingaccount" autocomplete="off"></div>
        <div class="field"><label for="cp-db">New database suffix</label><input id="cp-db" name="db_suffix" maxlength="12" value="padcore" autocomplete="off"><small>Your account prefix is added automatically.</small></div>
        <div class="field"><label for="cp-dbuser">New user suffix</label><input id="cp-dbuser" name="db_user_suffix" maxlength="12" value="paduser" autocomplete="off"></div>
      </div>
      <div class="field"><label for="cp-token">cPanel API token</label><input id="cp-token" type="password" name="cpanel_token" autocomplete="off" maxlength="512"><small>Used for this setup only. Never saved by the bootstrap. Revoke it in cPanel afterwards.</small></div>
    </div>
    <div class="divider"></div>
    <div class="grid2">
      <div class="field"><label for="db-host">MySQL hostname</label><input id="db-host" name="db_host" value="localhost" required maxlength="253" autocomplete="off"><small>Usually localhost; check your host's database details.</small></div>
      <div class="field"><label for="db-port">MySQL port</label><input id="db-port" name="db_port" value="3306" required inputmode="numeric" maxlength="5" autocomplete="off"></div>
    </div>
    <div class="field"><label for="db-pass">Database user password</label><input id="db-pass" type="password" name="db_password" autocomplete="new-password" maxlength="256" required><small>Automatic mode: choose a new 16+ character password. Manual mode: enter the existing database user's password.</small></div>
  </section>
  <section data-step="2" aria-labelledby="step-security">
    <h2 id="step-security" tabindex="-1">Secure your installation.</h2>
    <p class="intro">Verify hosting ownership once in File Manager; the server generates its own setup and encryption keys.</p>
    <div class="notice secret-note"><span class="secret-icon" aria-hidden="true">✧</span><span><strong>One-time hosting ownership check.</strong> Open cPanel File Manager and create an EMPTY file with the filename below inside the private <code>84pad-core-app/storage/app/private</code> directory outside public_html. No 32-character key entry is needed.</span></div>
    <div class="field"><label>Private proof filename</label><code class="proof-filename"><?= h($proofFile) ?></code><small>Copy the exact name and keep this browser tab open. The file is removed after verification.</small></div>
    <div class="readiness"><div class="readiness-head"><h3>What happens next</h3><span class="health">PROTECTED SETUP</span></div><ul>
      <li class="passed"><span class="dot" aria-hidden="true">✓</span>Verify the database is empty</li>
      <li class="passed"><span class="dot" aria-hidden="true">✓</span>Create a private APP_KEY and .env</li>
      <li class="passed"><span class="dot" aria-hidden="true">✓</span>Redirect to Owner account creation</li>
      <li class="passed"><span class="dot" aria-hidden="true">✓</span>Prevent rewriting an existing .env</li>
    </ul></div>
    <div class="notice">For SSL, email or file integrations later, use encrypted cPanel settings with per-plugin permissions. The 84PAD Core works without ongoing cPanel access.</div>
  </section>
  <div class="actions">
    <button class="btn secondary" type="button" data-back>← Back</button>
    <button class="btn primary" type="button" data-next>Continue →</button>
    <button class="btn primary" type="submit">Initialize 84PAD →</button>
  </div>
  </form>
  <p class="trust">HTTPS required · No manual setup key · Private owner verification</p>
</main>
<aside class="panel sidebar-panel" aria-label="Installation help">
  <div class="sidebar-heading"><span class="sidebar-icon" aria-hidden="true">✦</span><div><h2>Setup companion</h2><p>Everything you need, right where you need it.</p></div></div>
  <div class="eyebrow">BEFORE YOU BEGIN</div>
  <ul class="checklist">
    <li><span class="check" aria-hidden="true">✓</span><span><strong>Compatible PHP and extensions</strong><br>PHP 8.3+, PDO MySQL, OpenSSL and required extensions.</span></li>
    <li><span class="check" aria-hidden="true">✓</span><span><strong>HTTPS and safe document root</strong><br>Only <code>public/</code> must be visible online.</span></li>
    <li><span class="check" aria-hidden="true">✓</span><span><strong>Private hosting ownership proof</strong><br>One empty proof file created in cPanel File Manager.</span></li>
    <li><span class="check" aria-hidden="true">✓</span><span><strong>Database connection method</strong><br>Automatic cPanel or manual credentials.</span></li>
  </ul>
  <div class="helper">
    <div class="helper-head">QUICK ANSWERS</div>
    <details><summary>Where is my cPanel API token?</summary><p>Log in to cPanel, then Security → Manage API Tokens → Create. Use your account username, not your email. If the menu is unavailable, use Manual MySQL.</p></details>
    <details><summary>How do I create a database manually?</summary><p>Open Database Wizard or MySQL Databases in your hosting panel. Create a dedicated empty database and user, then grant privileges on that database.</p></details>
    <details><summary>Will 84PAD change my other websites?</summary><p>No. The installer requires a brand-new empty database and does not overwrite existing configuration files.</p></details>
    <details><summary>Is cPanel required afterwards?</summary><p>No. cPanel integration stays optional. Plugins only get API access when the Owner grants it through Core Settings.</p></details>
  </div>
</aside>
</div>
<footer class="footer"><span>© ParmTech · 84PAD Control Studio</span><strong>BUILD ANYTHING. PLUG EVERYTHING.</strong></footer>
</div>
<script src="/js/84pad-bootstrap-wizard.js" defer></script>
</body></html>
