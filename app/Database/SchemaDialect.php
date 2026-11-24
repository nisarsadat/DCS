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
}
