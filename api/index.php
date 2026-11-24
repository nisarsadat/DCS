<?php

// Vercel starts PHP from this file. Laravel's front controller stays in public/.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';

if (isset($_GET['probe'])) {
    $databaseUrl = getenv('DATABASE_URL');
    if (! is_string($databaseUrl)) {
        $databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? '';
    }

    header('Content-Type: application/json');
    echo json_encode([
        'app_key' => trim((string) (getenv('APP_KEY') ?: '')) !== '',
        'database_url' => $databaseUrl !== '',
        'postgres' => str_starts_with(ltrim($databaseUrl, "\"'"), 'postgres'),
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
