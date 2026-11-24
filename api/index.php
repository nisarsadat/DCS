<?php

// Vercel starts PHP from this file. Laravel's front controller stays in public/.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/../public/index.php';

$readEnv = static function (string $key): string {
    $value = getenv($key);
    if (! is_string($value) || $value === '') {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? '';
    }

    return is_string($value) ? $value : '';
};

$writeEnv = static function (string $key, string $value): void {
    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
};

$postgresUrl = '';
$bareHost = '';
foreach (['DB_URL', 'DATABASE_URL', 'DATABASE_URL_UNPOOLED', 'POSTGRES_URL', 'POSTGRES_PRISMA_URL', 'POSTGRES_URL_NON_POOLING'] as $key) {
    $value = preg_replace('/^\xEF\xBB\xBF/', '', trim($readEnv($key), " \t\n\r\0\x0B\"'")) ?? '';
    if ($value === '') {
        continue;
    }

    if (preg_match('/postgres(?:ql)?:\/\/\S+/i', $value, $match)) {
        $postgresUrl = rtrim(preg_replace('/([?&])channel_binding=[^&]*/', '', $match[0]) ?? $match[0], '?&');
        break;
    }

    if ($bareHost === '' && preg_match('/^[A-Za-z0-9.-]+\.neon\.tech$/', $value)) {
        $bareHost = $value;
    }
}

if ($postgresUrl !== '') {
    if (! str_contains($postgresUrl, 'sslmode=')) {
        $postgresUrl .= (str_contains($postgresUrl, '?') ? '&' : '?').'sslmode=require';
    }
    $writeEnv('DATABASE_URL', $postgresUrl);
    $writeEnv('DB_URL', $postgresUrl);
} elseif ($bareHost !== '' && $readEnv('DB_PGSQL_HOST') === '' && $readEnv('PGHOST') === '') {
    $writeEnv('DB_PGSQL_HOST', $bareHost);
}

foreach ([
    'APP_CONFIG_CACHE' => '/tmp/laravel-config.php',
    'APP_ROUTES_CACHE' => '/tmp/laravel-routes.php',
    'APP_EVENTS_CACHE' => '/tmp/laravel-events.php',
    'APP_SERVICES_CACHE' => '/tmp/laravel-services.php',
    'APP_PACKAGES_CACHE' => '/tmp/laravel-packages.php',
] as $key => $path) {
    if (in_array($key, ['APP_CONFIG_CACHE', 'APP_ROUTES_CACHE', 'APP_EVENTS_CACHE'], true) && is_file($path)) {
        unlink($path);
    }
    $writeEnv($key, $path);
}

require __DIR__.'/../public/index.php';
