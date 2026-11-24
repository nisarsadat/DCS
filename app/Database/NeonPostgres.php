<?php

namespace App\Database;

use Illuminate\Database\PostgresConnection;
use PDO;

class NeonPostgres
{
    public static function connect(array $config): PostgresConnection
    {
        if (! extension_loaded('pdo_pgsql')) {
            throw new \RuntimeException('PostgreSQL PDO driver is not available.');
        }

        $host = (string) ($config['host'] ?? '');
        if ($host === '') {
            throw new \InvalidArgumentException('PostgreSQL host is not configured.');
        }

        $port = (string) ($config['port'] ?? '5432');
        $database = str_replace("'", "\\'", (string) ($config['database'] ?? 'postgres'));
        $sslmode = (string) ($config['sslmode'] ?? '');
        if ($sslmode === '' || ($sslmode === 'prefer' && str_contains($host, 'neon.tech'))) {
            $sslmode = 'require';
        }
        $dsn = "pgsql:host={$host};port={$port};dbname='{$database}';sslmode={$sslmode}";

        

        $pdo = new PDO($dsn, (string) ($config['username'] ?? ''), (string) ($config['password'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec('set search_path to "public"');
        $pdo->exec("set time zone 'UTC'");

        return new PostgresConnection($pdo, (string) ($config['database'] ?? 'postgres'), $config['prefix'] ?? '', $config);
    }

    public static function endpoint(string $host): ?string
    {
        $host = preg_replace('/-pooler(?=\.)/', '', $host) ?? $host;

        if (preg_match('/^(ep-[a-z0-9-]+)\./i', $host, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
