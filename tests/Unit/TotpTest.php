<?php

namespace Tests\Unit;

use App\Support\Totp;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    public function test_generated_code_verifies(): void
    {
        $secret = Totp::generateSecret();
        $code = Totp::at($secret, intdiv(time(), 30));

        $this->assertTrue(Totp::verify($secret, $code));
        $this->assertFalse(Totp::verify($secret, '000000') && $code !== '000000');
    }
}
