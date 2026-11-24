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

if (isset($_GET['probe'])) {
    $scheme = 'none';
    if (preg_match('/^([A-Za-z][A-Za-z0-9+.-]{0,15}):\/\//', $databaseUrl, $schemeMatch)) {
        $scheme = strtolower($schemeMatch[1]);
    }

    header('Content-Type: application/json');
    echo json_encode([
        'app_key' => trim((string) (getenv('APP_KEY') ?: '')) !== '',
        'database_url' => $databaseUrl !== '',
        'postgres' => str_starts_with($databaseUrl, 'postgres'),
        'scheme' => $scheme,
        'mentions_postgres' => str_contains(strtolower($databaseUrl), 'postgres'),
        'mentions_neon' => str_contains($databaseUrl, 'neon.tech'),
        'mentions_pooler' => str_contains($databaseUrl, 'pooler'),
        'url_length' => strlen($databaseUrl),
        'db_connection' => getenv('DB_CONNECTION') ?: null,
        'cache' => getenv('CACHE_DRIVER') ?: null,
        'session' => getenv('SESSION_DRIVER') ?: null,
        'log' => getenv('LOG_CHANNEL') ?: null,
        'app_env' => getenv('APP_ENV') ?: null,
        'env_file' => is_file(__DIR__.'/../.env'),
        'vendor' => is_file(__DIR__.'/../vendor/autoload.php'),
        'services' => is_file(__DIR__.'/../bootstrap/cache/services.php'),
        'manifest' => is_file(__DIR__.'/../public/build/manifest.json'),
        'pdo_pgsql' => extension_loaded('pdo_pgsql'),
        'storage_writable' => is_writable(__DIR__.'/../storage'),
        'bootstrap_cache_writable' => is_writable(__DIR__.'/../bootstrap/cache'),
        'tmp_writable' => is_writable('/tmp'),
    ]);
    exit;
}

require __DIR__.'/../public/index.php';
