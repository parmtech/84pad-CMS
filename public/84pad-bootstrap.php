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
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
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

if ($method === 'POST') {
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

        if (! preg_match('/^[a-zA-Z0-9.-]{1,253}$/D', $dbHost)
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
:root{color-scheme:dark;font-family:system-ui,sans-serif}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:grid;place-items:center;background:radial-gradient(ellipse at 15% 15%,#263d72 0,#0b1021 65%);color:#eff7ff}
main{width:min(510px,94vw);margin:20px;padding:clamp(25px,5vw,40px);border:1px solid #5775a5;border-radius:25px;background:#111e34d9;box-shadow:0 18px 55px #0008}
small{font-weight:800;letter-spacing:.17em;color:#80ebef}h1{font-size:clamp(35px,7vw,57px);line-height:1.05;letter-spacing:-.07em;margin:15px 0}
p{line-height:1.65;color:#c1d2e8}label{display:block;font-weight:700;font-size:13px;margin:14px 0 6px}
input{width:100%;padding:13px;border:1px solid #536b95;border-radius:11px;background:#0c1729;color:white}
button{width:100%;margin-top:25px;padding:14px;background:linear-gradient(90deg,#72e9dc,#a492ff);border:0;border-radius:12px;font-weight:900;font-size:16px;color:#0b1830;cursor:pointer}
.error{color:#ffd0dc}
</style>
</head>
<body><main><small>PARMTECH · 84PAD / INITIALISE</small><h1>Your space begins here.</h1>
<p>Enter the one-time token from your private hosting folder and credentials for a <strong>new, empty</strong> MySQL database. After configuration, the secure Owner setup opens automatically.</p>
<?php if ($problem !== null): ?><p role="alert" class="error"><?= h($problem) ?></p><?php endif; ?>
<form method="post" autocomplete="off">
<input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
<label for="token">Private first-run token</label><input id="token" name="setup_token" type="password" required>
<label for="host">MySQL host</label><input id="host" name="db_host" value="localhost" maxlength="253" required>
<label for="port">MySQL port</label><input id="port" name="db_port" value="3306" inputmode="numeric" maxlength="5" required>
<label for="name">Database name</label><input id="name" name="db_name" maxlength="64" required>
<label for="user">Database username</label><input id="user" name="db_user" maxlength="64" required>
<label for="password">Database password</label><input id="password" name="db_password" type="password" required>
<button type="submit">Securely configure Core →</button>
</form>
<p style="font-size:12px">HTTPS is required. No passwords or private tokens should be shared in chat or uploaded to GitHub.</p>
</main></body></html>
