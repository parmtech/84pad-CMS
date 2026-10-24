<?php
/**
 * 84PAD staging first-run host bootstrap.
 * Standalone PHP so cPanel File Manager users can configure a fresh site
 * before Laravel has a database connection or an application key.
 *
 * SECURITY: Never show this screen without a private, out-of-webroot token.
 * It is disabled permanently as soon as .env is written.
 */
declare(strict_types=1);

header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
header('Referrer-Policy: no-referrer');

$root = dirname(__DIR__);
$private = $root.'/storage/app/private';
$tokenPath = $private.'/first-run-token';
$envPath = $root.'/.env';

$deny = static function (): never {
    http_response_code(404);
    exit;
};

if (is_file($envPath) || is_link($envPath)
    || ! is_dir($private) || is_link($private)
    || ! is_file($tokenPath) || is_link($tokenPath)
    || ! is_readable($tokenPath)) {
    $deny();
}

// Do not trust X-Forwarded-Proto from arbitrary clients.
$https = ($_SERVER['HTTPS'] ?? '') === 'on'
    || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
if (! $https) {
    http_response_code(403);
    exit('84PAD setup requires a direct HTTPS connection.');
}

$token = trim((string) file_get_contents($tokenPath));
if (strlen($token) < 32 || strlen($token) > 256
    || preg_match('/[\x00-\x20]/', $token)) {
    $deny();
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
    $submitted = (string) ($_POST['setup_token'] ?? '');
    if (! hash_equals($_SESSION['csrf'], $csrf)
        || strlen($submitted) > 256
        || ! hash_equals($token, $submitted)) {
        http_response_code(403);
        $problem = 'Invalid setup token or expired form.';
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
                require_once $root.'/app/Support/Installer/CpanelDatabaseBootstrap.php';
                $created = \App\Support\Installer\CpanelDatabaseBootstrap::provision($_POST);
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
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>84PAD · Host Bootstrap</title>
<style>
:root{color-scheme:dark;font-family:Inter,ui-sans-serif,system-ui,sans-serif}
*{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:#080d1d;color:#f4f7ff;min-height:100vh}
.ambient{position:fixed;inset:0;overflow:hidden;pointer-events:none;z-index:-1;background:radial-gradient(ellipse at 12% 8%,#22365b 0,transparent 45%),radial-gradient(ellipse at 88% 90%,#38215a 0,transparent 45%),#080d1d}
.ambient:before{content:"";position:absolute;inset:0;background-image:linear-gradient(#8ceafb0d 1px,transparent 1px),linear-gradient(90deg,#8ceafb0d 1px,transparent 1px);background-size:48px 48px;mask-image:linear-gradient(110deg,transparent,#0009,transparent)}
.orb{position:absolute;width:32rem;height:32rem;border-radius:50%;filter:blur(90px);opacity:.19;animation:drift 17s ease-in-out infinite alternate;background:#13d7ec;top:-20rem;right:-12rem}.orb.two{background:#a477f7;top:auto;bottom:-23rem;left:-12rem;animation-delay:-8s}
@keyframes drift{to{transform:translate(-8vw,6vh) scale(1.2)}}
.shell{width:min(1170px,calc(100% - 36px));margin:30px auto 70px}
.top{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}
.brand{display:flex;align-items:center;gap:12px;font-weight:900;font-size:22px;letter-spacing:-.05em}
.logo{display:grid;place-items:center;width:38px;height:38px;border:1px solid #baff7866;border-radius:12px;color:#ccff71;background:#baff7812;box-shadow:0 0 22px #baff781e}
.tag{border:1px solid #7184aa77;border-radius:30px;padding:8px 14px;color:#b9d1ef;font-size:12px;font-weight:700;letter-spacing:.08em}
.progress{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:32px 0}
.progress>div{padding:13px 16px;border-radius:14px;border:1px solid #3a507e;background:#111a31;display:flex;gap:10px;align-items:center;color:#9db3ce;font-size:13px;font-weight:750}
.progress b{display:grid;place-items:center;border-radius:50%;background:#2a395c;color:#c1d8f3;width:25px;height:25px;font-size:12px}
.progress .active{border-color:#7addec;background:#1a3447;color:#fff;box-shadow:inset 0 0 30px #4ce6e60a}
.progress .active b{background:#8aefee;color:#071b27}
.columns{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(270px,.85fr);gap:22px}
.panel{background:linear-gradient(145deg,#141f38eb,#101829f0);border:1px solid #465a83;border-radius:26px;padding:clamp(22px,4vw,40px);box-shadow:0 20px 65px #0006}
.eyebrow{color:#8be7ed;letter-spacing:.16em;font-size:11px;font-weight:900;text-transform:uppercase}
h1{font-size:clamp(32px,5.5vw,58px);line-height:1.05;letter-spacing:-.065em;margin:16px 0}h2{font-size:24px;letter-spacing:-.04em;margin:0 0 14px}
p{line-height:1.65;color:#bdcce1;font-size:14px}a{color:#9ceafa;text-underline-offset:4px}
.modes{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:25px 0}
.modes label{margin:0;display:block;border:1px solid #3d5279;padding:18px;border-radius:17px;cursor:pointer;background:#101c32;transition:transform .22s,box-shadow .22s,border-color .22s}
.modes label:hover{transform:translateY(-3px);border-color:#93f0eb;box-shadow:0 10px 30px #36d4ea1c}
.modes input{width:auto;accent-color:#95f5ef;margin-right:8px}
.modes strong{display:block;font-size:16px;margin:12px 0 4px}.modes small{display:block;color:#b0c3dc;line-height:1.55}
.modes label:has(input:checked){border-color:#89eedb;background:#153342;box-shadow:inset 0 0 0 1px #89eedb55}
.field{margin:15px 0}.field label{display:block;font-weight:750;font-size:13px;margin-bottom:8px}
.field input{display:block;width:100%;padding:14px 15px;border:1px solid #536b93;border-radius:12px;background:#0b1427;color:#f3f8ff;font-size:15px;outline-offset:3px}
.field input:focus{border-color:#7beedc;box-shadow:0 0 0 3px #7beedc1c}
.field small{color:#98aac5;font-size:12px;display:block;margin-top:6px}
.grid2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 14px}
.notice{padding:15px 17px;border-radius:14px;background:#6cd9e70d;border:1px solid #58d8ea50;color:#c1e9f6;font-size:13px;line-height:1.6;margin:18px 0}
.error{background:#8f264a22;border:1px solid #f593af88;color:#ffdce7;border-radius:14px;padding:14px 16px}
aside .panel{position:sticky;top:24px;padding:24px}.checklist{list-style:none;padding:0;margin:22px 0}
.checklist li{display:flex;gap:12px;margin:16px 0;color:#c9d8e9;font-size:13px;line-height:1.5}
.checklist span{color:#b5f78d;font-weight:900}
details{border-top:1px solid #405477;padding:14px 0}summary{cursor:pointer;font-size:13px;font-weight:800}details p{margin:10px 0 0}
.actions{display:flex;gap:12px;align-items:center;justify-content:flex-end;margin-top:28px}
button{border:0;padding:14px 22px;border-radius:13px;font-size:14px;font-weight:900;cursor:pointer}
button.primary{background:linear-gradient(100deg,#76f0e1,#a79cff);color:#061628;box-shadow:0 8px 30px #65e4f233}
button.primary:hover{filter:brightness(1.1);transform:translateY(-2px)}
button.secondary{background:#283855;color:#e5efff;border:1px solid #607699}
.mini{font-size:12px;color:#93aac6}
.readiness{margin:22px 0;border:1px solid #41587b;border-radius:16px;padding:17px;background:#0a162a}
.readiness h3{font-size:16px;margin:0 0 12px;letter-spacing:-.02em}
.readiness ul{list-style:none;padding:0;margin:0;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px 15px}
.readiness li{font-size:12px;display:flex;align-items:center;gap:9px;color:#cad9ee}
.readiness li span{font-weight:900;font-size:13px}
.readiness .passed span{color:#adf7a4}.readiness .failed span{color:#ff9caa}
.readiness .failed{color:#ffe0e4}
@media(max-width:600px){.readiness ul{grid-template-columns:1fr}}
html.js [data-step]{display:none}html.js [data-step].visible{display:block}
html.js .actions [data-back],html.js .actions [data-next]{display:inline-block}html.js .actions [hidden]{display:none!important}
[data-back],[data-next]{display:none}html.js button[type=submit]{display:none}html.js [data-step="2"].visible ~ .actions button[type=submit]{display:inline-block}
html.js [data-step="2"].visible ~ .actions [data-next]{display:none}
html.js [data-step="0"].visible ~ .actions [data-back]{display:none}
html.js [data-mode-fields]{display:none}
html.js [data-mode-fields].visible{display:block}
@media(max-width:820px){.columns{grid-template-columns:1fr}aside .panel{position:static}.progress{margin:20px 0}.progress>div{padding:10px;font-size:11px}.shell{width:min(680px,calc(100% - 22px))}}
@media(max-width:480px){.modes,.grid2{grid-template-columns:1fr}.brand{font-size:19px}.progress>div{flex-direction:column;text-align:center;gap:6px}.panel{padding:20px}.actions{flex-wrap:wrap}.actions button{flex:1}}
@media(prefers-reduced-motion:reduce){*,*:before,*:after{animation:none!important;transition:none!important;scroll-behavior:auto!important}}
</style>
</head>
<body>
<div class="ambient" aria-hidden="true"><div class="orb"></div><div class="orb two"></div></div>
<div class="shell">
<header class="top"><div class="brand"><span class="logo" aria-hidden="true">▦</span>84PAD<span style="color:#9ff0d7">.</span></div><span class="tag">PARMTECH · CONTROL STUDIO / SETUP</span></header>
<div class="progress" aria-label="Installation progress">
<div class="active" data-indicator="0"><b>01</b> Connection</div>
<div data-indicator="1"><b>02</b> Database</div>
<div data-indicator="2"><b>03</b> Initialise</div>
</div>
<div class="columns"><main class="panel">
<div class="eyebrow">Private · First-run configuration</div>
<h1>Your space begins here.</h1>
<p>Choose how 84PAD connects to your hosting. Automatic cPanel provisioning is optional. Manual MySQL works on compatible hosting without an API token.</p>
<?php if ($problem !== null): ?><p role="alert" class="error"><?= h($problem) ?></p><?php endif; ?>
<form method="post" autocomplete="off" data-wizard>
<input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
<section data-step="0" class="visible">
<h2 tabindex="-1">How should we connect?</h2>
<div class="modes">
<label><input type="radio" name="connection_mode" value="manual" checked><strong>Manual MySQL</strong><small>Any supported PHP/MySQL hosting. Create an empty database in your hosting dashboard.</small></label>
<label><input type="radio" name="connection_mode" value="cpanel"><strong>cPanel API</strong><small>Let 84PAD create a new database, database user and required database privileges.</small></label>
</div>
<div class="notice">Your website must serve only 84PAD's <code>public/</code> directory over HTTPS. Do not expose <code>.env</code>, <code>storage/</code> or Composer files to visitors.</div>
<div class="readiness" aria-label="Server readiness checks">
<h3>Hosting readiness <?= $hostReady ? '— ready' : '— action needed' ?></h3>
<ul>
<?php foreach ($hostChecks as $label => $passed): ?>
<li class="<?= $passed ? 'passed' : 'failed' ?>"><span aria-hidden="true"><?= $passed ? '✓' : '!' ?></span><?= h((string) $label) ?>: <?= $passed ? 'Ready' : 'Fix required' ?></li>
<?php endforeach; ?>
</ul>
<?php if (! $hostReady): ?><p role="alert">Some requirements are missing. Check your PHP extensions, upload the Composer-inclusive distribution, and allow private storage writes before continuing.</p><?php endif; ?>
</div>
</section>
<section data-step="1">
<h2 tabindex="-1">Database connection</h2>
<div data-mode-fields="manual">
<p>In your hosting dashboard open <strong>MySQL Databases</strong> or <strong>MySQL Database Wizard</strong>. Create a new empty database and user, then grant that user privileges on the new database.</p>
<div class="grid2">
<div class="field"><label for="db-name">Database name</label><input id="db-name" name="db_name" maxlength="64" placeholder="account_84pad"></div>
<div class="field"><label for="db-user">Database username</label><input id="db-user" name="db_user" maxlength="64" placeholder="account_paduser"></div>
</div>
</div>
<div data-mode-fields="cpanel">
<div class="notice">In cPanel go to <strong>Security → Manage API Tokens → Create</strong>. Copy the token once, then enter your hosting server's hostname (not a full URL). API access must be supported by your hosting plan. <a href="https://api.docs.cpanel.net/cpanel/tokens" target="_blank" rel="noopener noreferrer">Official token guide ↗</a></div>
<div class="grid2">
<div class="field"><label for="cp-host">cPanel server hostname</label><input id="cp-host" name="cpanel_host" placeholder="server.example.com" maxlength="253"><small>From your cPanel URL, without https:// or :2083</small></div>
<div class="field"><label for="cp-user">cPanel username</label><input id="cp-user" name="cpanel_username" maxlength="16" placeholder="account"></div>
<div class="field"><label for="cp-db">New database suffix</label><input id="cp-db" name="db_suffix" maxlength="12" value="padcore"><small>The account prefix is added automatically</small></div>
<div class="field"><label for="cp-dbuser">New user suffix</label><input id="cp-dbuser" name="db_user_suffix" maxlength="12" value="paduser"></div>
</div>
<div class="field"><label for="cp-token">cPanel API token</label><input id="cp-token" type="password" name="cpanel_token" autocomplete="off" maxlength="512"><small>Used once over verified HTTPS. Not stored by the installer.</small></div>
</div>
<div class="grid2">
<div class="field"><label for="db-host">MySQL hostname</label><input id="db-host" name="db_host" value="localhost" required maxlength="253"><small>Usually localhost; check your hosting panel</small></div>
<div class="field"><label for="db-port">MySQL port</label><input id="db-port" name="db_port" value="3306" required inputmode="numeric" maxlength="5"></div>
</div>
<div class="field"><label for="db-pass">Database user password</label><input id="db-pass" type="password" name="db_password" autocomplete="new-password" maxlength="256" required><small>For automatic provisioning, choose a new strong password (16+ characters). For manual, enter the existing database user's password.</small></div>
</section>
<section data-step="2">
<h2 tabindex="-1">Securely initialise Core</h2>
<p>84PAD will check the database is <strong>empty</strong>, create a private application key and open the Owner-account setup. Existing databases and existing <code>.env</code> files are never overwritten.</p>
<div class="field"><label for="setup-token">Private first-run token</label><input id="setup-token" name="setup_token" type="password" autocomplete="off" required maxlength="256"><small>In your hosting File Manager create <code>storage/app/private/first-run-token</code> (outside the public web root) containing at least 32 random characters, then enter its contents here.</small></div>
<div class="notice">After this step you will create the Owner account. For ongoing SSL/email/file integrations, add a cPanel connection later in <strong>Core Settings</strong>, where it will be encrypted and individually permissioned.</div>
</section>
<div class="actions"><button class="secondary" type="button" data-back>← Back</button><button class="primary" type="button" data-next>Continue →</button><button class="primary" type="submit">Configure 84PAD Core →</button></div>
</form>
<p class="mini">HTTPS required · No password is sent to ParmTech · Setup closes when the environment is created</p>
</main>
<aside><div class="panel">
<div class="eyebrow">Installation checklist</div><h2>Everything in one place.</h2>
<ul class="checklist">
<li><span>✓</span><div><strong>PHP 8.3 / 8.4</strong><br>Enable PDO MySQL, OpenSSL, mbstring and required Laravel extensions.</div></li>
<li><span>✓</span><div><strong>HTTPS & document root</strong><br>Point the domain to the <code>public/</code> directory only.</div></li>
<li><span>✓</span><div><strong>Private setup token</strong><br>Create the one-time file outside the public directory.</div></li>
<li><span>✓</span><div><strong>Database access</strong><br>Automatic cPanel connection or manual MySQL credentials.</div></li>
</ul>
<details><summary>Where do I find the cPanel API token?</summary><p>Login to cPanel → Security → Manage API Tokens → Create. Use the cPanel account username, not the email address. Some hosts disable tokens.</p></details>
<details><summary>How do I create a database manually?</summary><p>Open MySQL Databases or MySQL Database Wizard in your hosting dashboard. Create a new database and user, assign privileges, then enter the full prefixed names above.</p></details>
<details><summary>Is cPanel required after setup?</summary><p>No. Core and unrelated plugins work without a cPanel token. You can optionally reconnect later in encrypted Core Settings and grant individual plugin permissions.</p></details>
<details><summary>Why does the wizard require a private token?</summary><p>It prevents visitors from claiming ownership of an uninstalled website. The setup file must be outside the publicly served directory.</p></details>
</div></aside></div>
</div>
<script src="/js/84pad-bootstrap-wizard.js" defer></script>
</body></html>