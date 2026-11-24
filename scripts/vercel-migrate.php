<?php

$url = trim((string) getenv('DATABASE_URL'), " \t\n\r\0\x0B\"'");

if ($url === '' || !str_starts_with($url, 'postgres')) {
    fwrite(STDOUT, "No Postgres DATABASE_URL is available, so this build will not migrate.\n");
    exit(0);
}

$env = [];
foreach (array_merge(getenv() ?: [], $_ENV, $_SERVER) as $key => $value) {
    if (is_string($key) && is_string($value)) {
        $env[$key] = $value;
    }
}

$env['DATABASE_URL'] = $url;
$env['DB_CONNECTION'] = 'pgsql';

$command = escapeshellarg(PHP_BINARY).' artisan migrate --force';
$process = proc_open($command, [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
], $pipes, null, $env);

if (!is_resource($process)) {
    fwrite(STDERR, "Could not start the migration.\n");
    exit(1);
}

fclose($pipes[0]);
echo stream_get_contents($pipes[1]);
fwrite(STDERR, stream_get_contents($pipes[2]));
fclose($pipes[1]);
fclose($pipes[2]);

exit(proc_close($process));
