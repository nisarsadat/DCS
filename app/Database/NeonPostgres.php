<?php

namespace App\Database;

use Illuminate\Database\PostgresConnection;
use PDO;

class NeonPostgres
{
    public static function connect(array $config): PostgresConnection
    {
        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (string) ($config['port'] ?? '5432');
        $database = str_replace("'", "\\'", (string) ($config['database'] ?? 'postgres'));
        $sslmode = (string) ($config['sslmode'] ?? 'prefer');
        $dsn = "pgsql:host={$host};port={$port};dbname='{$database}';sslmode={$sslmode}";

        $endpoint = self::endpoint($host);
        if ($endpoint !== null) {
            $dsn .= ";options='endpoint={$endpoint}'";
        }

        $pdo = new PDO($dsn, (string) ($config['username'] ?? ''), (string) ($config['password'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec('set search_path to "public"');
        $pdo->exec("set time zone 'UTC'");

        return new PostgresConnection($pdo, (string) ($config['database'] ?? 'postgres'), $config['prefix'] ?? '', $config);
    }

    public static function endpoint(string $host): ?string
    {
        if (preg_match('/^(ep-[a-z0-9-]+)\./i', $host, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
