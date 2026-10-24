<?php
declare(strict_types=1);

// Isolated CI-only first-run POST simulator. The caller creates a throwaway
// temporary MySQL database. Never point this fixture at a production DB.
$bootstrap = $argv[1] ?? '';
$host = getenv('CI_BOOTSTRAP_DB_HOST') ?: '127.0.0.1';
$database = getenv('CI_BOOTSTRAP_DB_NAME') ?: '';
$user = getenv('CI_BOOTSTRAP_DB_USER') ?: '';
$password = getenv('CI_BOOTSTRAP_DB_PASSWORD') ?: '';

if (! is_file($bootstrap)
    || ! preg_match('/^pad_bootstrap_ci_[a-f0-9]{12}$/D', $database)
    ) {
    fwrite(STDERR, 'CI bootstrap test configuration missing.');
    exit(1);
}

session_name('84PAD_SETUP');
session_start();
$_SESSION['csrf'] = 'ci-test-csrf-value';
$_SESSION['owner_proof'] = '0123456789abcdef01234567';
session_write_close();
file_put_contents(dirname($bootstrap, 2).'/storage/app/private/allow-84pad-0123456789abcdef01234567.txt', '');

$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = '443';
$_SERVER['SERVER_NAME'] = 'test.example';
$_SERVER['REQUEST_METHOD'] = 'POST';

$_POST = [
    'csrf' => 'ci-test-csrf-value',
    'db_host' => $host,
    'db_port' => '3306',
    'db_name' => $database,
    'db_user' => $user,
    'db_password' => $password,
];

require $bootstrap;
