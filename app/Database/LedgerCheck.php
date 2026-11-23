<?php

namespace App\Database;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class LedgerCheck
{
    public static function assertBalances(): void
    {
        if (! SchemaDialect::postgres()) {
            throw new RuntimeException('Ledger checks run only on PostgreSQL.');
        }

        DB::beginTransaction();
        try {
            $now = now();
            $moneyId = DB::table('money_accounts')->insertGetId([
                'name' => 'pg-ledger-check',
                'balance' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            self::insertMoney($moneyId, 'money_received', 'received', 200);
            self::insertMoney($moneyId, 'expense', 'paid', 50);
            $moneyBalance = (float) DB::table('money_accounts')->where('id', $moneyId)->value('balance');
            if (abs($moneyBalance - 150.0) > 0.001) {
                throw new RuntimeException('Money account balance was '.$moneyBalance.' after received 200 and paid 50.');
            }

            $personId = DB::table('people')->insertGetId([
                'name' => 'pg-ledger-check',
                'type' => 'patient',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $accountId = DB::table('people_accounts')->insertGetId([
                'name' => 'pg-ledger-check',
                'people_id' => $personId,
                'balance' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            self::insertPeople($accountId, $personId, 'paid', 80);
            self::insertPeople($accountId, $personId, 'received', 30);
            $peopleBalance = (float) DB::table('people_accounts')->where('id', $accountId)->value('balance');
            if (abs($peopleBalance - 50.0) > 0.001) {
                throw new RuntimeException('People account balance was '.$peopleBalance.' after paid 80 and received 30.');
            }

            DB::rollBack();
        } catch (\Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            throw $exception;
        }
    }

    private static function insertMoney(int $accountId, string $operation, string $payment, float $amount): void
    {
        $now = now();
        DB::table('money_account_transactions')->insert([
            'money_account_id' => $accountId,
            'operation_type' => $operation,
            'payment_type' => $payment,
            'amount' => $amount,
            'date' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private static function insertPeople(int $accountId, int $personId, string $payment, float $amount): void
    {
        $now = now();
        DB::table('people_account_transactions')->insert([
            'people_account_id' => $accountId,
            'people_id' => $personId,
            'transaction_type' => 'operation',
            'operation_type' => 'cure_cycle',
            'payment_type' => $payment,
            'amount' => $amount,
            'date' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
