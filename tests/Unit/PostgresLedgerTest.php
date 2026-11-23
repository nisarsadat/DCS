<?php

namespace Tests\Unit;

use App\Database\PostgresLedger;
use App\Support\SqlCompat;
use PHPUnit\Framework\TestCase;

class PostgresLedgerTest extends TestCase
{
    public function test_postgres_ledger_keeps_the_mysql_balance_formulas(): void
    {
        $sql = PostgresLedger::allSql();

        $this->assertStringContainsString('new_balance := received_total - paid_total', $sql);
        $this->assertStringContainsString('new_balance := paid_total - received_total', $sql);
        $this->assertStringContainsString('deleted_at IS NULL', $sql);
        $this->assertStringContainsString('cure_cycle_payment', $sql);
        $this->assertStringContainsString('NEW.note', $sql);
        $this->assertStringNotContainsString('CREATE PROCEDURE', $sql);
        $this->assertStringNotContainsString('DELIMITER', $sql);
        $this->assertStringNotContainsString('FOREIGN_KEY_CHECKS', $sql);
    }

    public function test_date_helpers_match_each_driver(): void
    {
        $this->assertSame('DATE(expenses.date) = ?', SqlCompat::dateEquals('expenses.date', 'mysql'));
        $this->assertSame('CAST(expenses.date AS date) = ?', SqlCompat::dateEquals('expenses.date', 'pgsql'));
        $this->assertSame('YEAR(expenses.date) = ?', SqlCompat::yearEquals('expenses.date', 'mysql'));
        $this->assertSame('EXTRACT(YEAR FROM expenses.date) = ?', SqlCompat::yearEquals('expenses.date', 'pgsql'));
        $this->assertSame('TIME(appointments.date_time)', SqlCompat::timeExpression('appointments.date_time', 'mysql'));
        $this->assertSame('CAST(appointments.date_time AS time)', SqlCompat::timeExpression('appointments.date_time', 'pgsql'));
    }
}
