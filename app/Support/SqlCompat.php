<?php

namespace App\Support;

class SqlCompat
{
    public static function usesPostgres(?string $driver = null): bool
    {
        if ($driver !== null) {
            return $driver === 'pgsql';
        }

        $connection = config('database.default');

        return config("database.connections.{$connection}.driver") === 'pgsql';
    }

    public static function dateEquals(string $column, ?string $driver = null): string
    {
        return self::usesPostgres($driver)
            ? "CAST({$column} AS date) = ?"
            : "DATE({$column}) = ?";
    }

    public static function yearEquals(string $column, ?string $driver = null): string
    {
        return self::usesPostgres($driver)
            ? "EXTRACT(YEAR FROM {$column}) = ?"
            : "YEAR({$column}) = ?";
    }

    public static function timeExpression(string $column, ?string $driver = null): string
    {
        return self::usesPostgres($driver)
            ? "CAST({$column} AS time)"
            : "TIME({$column})";
    }
}
