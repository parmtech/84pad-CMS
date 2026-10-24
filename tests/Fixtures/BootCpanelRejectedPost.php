<?php
declare(strict_types=1);

$bootstrap = $argv[1] ?? '';
if (! is_file($bootstrap)) {
    exit(1);
}
session_name('84PAD_SETUP');
session_start();
$_SESSION['csrf'] = 'ci-cpanel-negative';
session_write_close();

$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = '443';
$_SERVER['SERVER_NAME'] = 'test.example';
$_SERVER['REQUEST_METHOD'] = 'POST';

$_POST = [
    'csrf' => 'ci-cpanel-negative',
    'setup_token' => str_repeat('A', 48),
    'connection_mode' => 'cpanel',
    'cpanel_host' => '127.0.0.1',
    'cpanel_username' => 'account',
    'cpanel_token' => str_repeat('Z', 40),
    'db_suffix' => 'padcore',
    'db_user_suffix' => 'paduser',
    'db_host' => 'localhost',
    'db_port' => '3306',
    'db_password' => str_repeat('B', 24),
];

require $bootstrap;
