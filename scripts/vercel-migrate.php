<?php

$url = getenv('DATABASE_URL') ?: '';

if ($url === '') {
    fwrite(STDOUT, "DATABASE_URL is not set, so this build will not migrate.\n");
    exit(0);
}

if (str_starts_with($url, 'postgres')) {
    putenv('DB_CONNECTION=pgsql');
    $_ENV['DB_CONNECTION'] = 'pgsql';
    $_SERVER['DB_CONNECTION'] = 'pgsql';
}

passthru(PHP_BINARY.' artisan migrate --force', $code);
exit($code);
