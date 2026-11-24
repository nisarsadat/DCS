<?php

// Vercel starts PHP from this file. Laravel's front controller stays in public/.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';

$databaseUrl = getenv('DATABASE_URL');
if (! is_string($databaseUrl)) {
    $databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? '';
}
if ($databaseUrl !== '') {
    $databaseUrl = preg_replace('/^\xEF\xBB\xBF/', '', trim($databaseUrl, " \t\n\r\0\x0B\"'")) ?? $databaseUrl;
    if (preg_match('/postgres(?:ql)?:\/\/\S+/i', $databaseUrl, $databaseMatch)) {
        $databaseUrl = rtrim(preg_replace('/([?&])channel_binding=[^&]*/', '', $databaseMatch[0]) ?? $databaseMatch[0], '?&');
        putenv('DATABASE_URL='.$databaseUrl);
        $_ENV['DATABASE_URL'] = $databaseUrl;
        $_SERVER['DATABASE_URL'] = $databaseUrl;
    }
}

foreach ([
    'APP_SERVICES_CACHE' => '/tmp/services.php',
    'APP_PACKAGES_CACHE' => '/tmp/packages.php',
    'APP_CONFIG_CACHE' => '/tmp/config.php',
    'APP_ROUTES_CACHE' => '/tmp/routes-v7.php',
    'APP_EVENTS_CACHE' => '/tmp/events.php',
] as $cacheKey => $cachePath) {
    putenv($cacheKey.'='.$cachePath);
    $_ENV[$cacheKey] = $cachePath;
    $_SERVER[$cacheKey] = $cachePath;
}

require __DIR__.'/../public/index.php';
