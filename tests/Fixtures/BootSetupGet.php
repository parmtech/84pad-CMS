<?php
declare(strict_types=1);

// Test-only wrapper for a separate PHP process. It never posts credentials.
$script = $argv[1] ?? '';
$secure = ($argv[2] ?? 'no') === 'yes';
$_SERVER['HTTPS'] = $secure ? 'on' : 'off';
$_SERVER['SERVER_PORT'] = $secure ? '443' : '80';
$_SERVER['SERVER_NAME'] = 'test.example';
$_SERVER['REQUEST_METHOD'] = 'GET';
require $script;
