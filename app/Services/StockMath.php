<?php

namespace App\Services;

use InvalidArgumentException;

class StockMath
{
    public static function apply(string $type, float $before, float $quantity): float
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Quantity cannot be negative.');
        }

        $after = match ($type) {
            'in' => $before + $quantity,
            'out' => $before - $quantity,
            'adjust' => $quantity,
            default => throw new InvalidArgumentException('Unknown stock movement.'),
        };

        if ($after < 0) {
            throw new InvalidArgumentException('Stock cannot go below zero.');
        }

        return round($after, 2);
    }
}
