<?php

namespace Tests\Unit;

use App\Services\StockMath;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class StockMathTest extends TestCase
{
    public function test_stock_in_increases_quantity(): void
    {
        $this->assertSame(15.0, StockMath::apply('in', 10, 5));
    }

    public function test_stock_out_rejects_negative_balance(): void
    {
        $this->expectException(InvalidArgumentException::class);
        StockMath::apply('out', 2, 5);
    }

    public function test_adjustment_sets_the_counted_quantity(): void
    {
        $this->assertSame(8.0, StockMath::apply('adjust', 20, 8));
    }
}
