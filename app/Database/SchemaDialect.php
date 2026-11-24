<?php

namespace App\Database;

use Illuminate\Support\Facades\DB;

class SchemaDialect
{
    public static function driver(): string
    {
        return DB::getDriverName();
    }

    public static function postgres(): bool
    {
        return self::driver() === 'pgsql';
    }

    public static function normalizePostgresUrl(string $url): string
    {
        $url = trim($url, " \t\n\r\0\x0B\"'");
        $url = preg_replace('/^\xEF\xBB\xBF/', '', $url) ?? $url;

        if (preg_match('/postgres(?:ql)?:\/\/\S+/i', $url, $matches)) {
            $url = $matches[0];
        }

        $url = preg_replace('/([?&])channel_binding=[^&]*&/', '$1', $url) ?? $url;
        $url = preg_replace('/([?&])channel_binding=[^&]*$/', '', $url) ?? $url;

        return rtrim($url, '?&');
    }

    /**
     * A full Postgres URL from the environment, or null when the value is only a host.
     */
    public static function connectionUrl(): ?string
    {
        foreach (['DB_URL', 'DATABASE_URL', 'DATABASE_URL_UNPOOLED', 'POSTGRES_URL', 'POSTGRES_PRISMA_URL', 'POSTGRES_URL_NON_POOLING'] as $key) {
            $url = self::normalizePostgresUrl((string) env($key));
            if (! str_starts_with($url, 'postgres')) {
                continue;
            }

            if (! str_contains($url, 'sslmode=')) {
                $url .= (str_contains($url, '?') ? '&' : '?').'sslmode=require';
            }

            return $url;
        }

        return null;
    }

    public static function postgresHost(): string
    {
        $url = self::connectionUrl();
        $host = is_string($url) ? parse_url($url, PHP_URL_HOST) : null;
        if (is_string($host) && $host !== '') {
            return $host;
        }

        foreach (['PGHOST', 'POSTGRES_HOST', 'DB_PGSQL_HOST', 'DB_URL', 'DATABASE_URL', 'DB_HOST'] as $key) {
            $value = trim((string) env($key), " \t\n\r\0\x0B\"'");
            if ($value === '' || str_contains($value, '://') || str_contains($value, '/')) {
                continue;
            }
            if (in_array(strtolower($value), ['127.0.0.1', 'localhost', '::1'], true)) {
                continue;
            }
            if (preg_match('/^[A-Za-z0-9.-]+$/', $value)) {
                return $value;
            }
        }

        return env('APP_ENV') === 'production' ? '' : '127.0.0.1';
    }

    public static function postgresPort(): string
    {
        foreach (['DB_PGSQL_PORT', 'PGPORT', 'POSTGRES_PORT'] as $key) {
            $value = trim((string) env($key));
            if ($value !== '') {
                return $value;
            }
        }

        $port = trim((string) env('DB_PORT'));
        if ($port !== '' && $port !== '3306') {
            return $port;
        }

        return '5432';
    }

    public static function firstEnv(array $keys, string $default = ''): string
    {
        foreach ($keys as $key) {
            $value = env($key);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return $default;
    }
}
