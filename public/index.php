<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// With cPanel public_html deployment, the Laravel app is stored outside
// the document root. Normal Laravel deployments keep using dirname(__DIR__).
$rootConfig = __DIR__.'/.84pad-root.php';
$root = is_file($rootConfig) && !is_link($rootConfig)
    ? require $rootConfig
    : dirname(__DIR__);
if (!is_string($root) || !is_dir($root)) {
    http_response_code(503);
    exit('84PAD private application root is unavailable.');
}

// Before APP_KEY/.env exist, open the first-time setup wizard at site root.
if (!is_file($root.'/.env')) {
    require __DIR__.'/84pad-bootstrap.php';
    exit;
}
if (file_exists($maintenance = $root.'/storage/framework/maintenance.php')) {
    require $maintenance;
}
require $root.'/vendor/autoload.php';
/** @var Application $app */
$app = require $root.'/bootstrap/app.php';
$app->handleRequest(Request::capture());
