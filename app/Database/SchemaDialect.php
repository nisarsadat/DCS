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
        $url = preg_replace('/([?&])channel_binding=[^&]*&/', '$1', $url) ?? $url;
        $url = preg_replace('/([?&])channel_binding=[^&]*$/', '', $url) ?? $url;

        return rtrim($url, '?&');
    }
}
