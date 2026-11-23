<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PostgresMigrationTest extends TestCase
{
    public function test_development_branch_migrates_and_balances_match(): void
    {
        if (! filter_var(env('PG_MIGRATE_TEST'), FILTER_VALIDATE_BOOLEAN)) {
            $this->markTestSkipped('Set PG_MIGRATE_TEST=1 and NEON_DEV_DATABASE_URL for a Neon development branch.');
        }

        $before = config('database.default');
        $exit = Artisan::call('clinic:pg-migrate-test');
        $this->assertSame(0, $exit, Artisan::output());
        $this->assertSame($before, config('database.default'));
    }
}
