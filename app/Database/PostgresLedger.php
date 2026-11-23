<?php

namespace App\Database;

use App\Enums\OperationType;
use App\Enums\PaymentType;
use App\Enums\TransactionType;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PostgresLedger
{
    public static function installProcedures(): void
    {
        self::run(self::functionStatements());
    }

    public static function dropProcedures(): void
    {
        self::run(self::dropProcedureStatements(true));
    }

    public static function installMoneyAccountTransactionTriggers(): void
    {
        self::run(array_merge(self::moneyFunctionStatements(), self::moneyAccountTransactionTriggerStatements()));
    }

    public static function dropMoneyAccountTransactionTriggers(): void
    {
        self::run(self::dropTriggerStatements('money_account_transactions', [
            'after_money_account_transaction_insert',
            'after_money_account_transaction_update',
            'after_money_account_transaction_delete',
        ]));
    }

    public static function installPeopleAccountTransactionTriggers(): void
    {
        self::run(array_merge(self::functionStatements(), self::peopleAccountTransactionTriggerStatements()));
    }

    public static function dropPeopleAccountTransactionTriggers(): void
    {
        self::run(self::dropTriggerStatements('people_account_transactions', [
            'after_people_account_transaction_insert',
            'after_people_account_transaction_update',
            'after_people_account_transaction_delete',
        ]));
    }

    public static function installMoneyTransferTriggers(): void
    {
        self::run(array_merge(self::moneyFunctionStatements(), self::moneyTransferTriggerStatements()));
    }

    public static function dropMoneyTransferTriggers(): void
    {
        self::run(self::dropTriggerStatements('money_transfers', [
            'after_money_transfer_insert',
            'after_money_transfer_update',
            'before_money_transfer_delete',
        ]));
    }

    public static function installExpenseTriggers(): void
    {
        self::run(array_merge(self::functionStatements(), self::expenseTriggerStatements()));
    }

    public static function dropExpenseTriggers(): void
    {
        self::run(self::dropTriggerStatements('expenses', [
            'after_expense_insert',
            'after_expense_update',
            'after_expense_delete',
        ]));
    }

    public static function installCureTriggers(): void
    {
        self::run(array_merge(self::functionStatements(), self::cureTriggerStatements()));
    }

    public static function dropCureTriggers(): void
    {
        self::run(self::dropTriggerStatements('cures', [
            'after_cure_cycle_insert',
            'after_cure_cycle_update',
            'after_cure_cycle_delete',
        ]));
    }

    public static function installInboundLabTriggers(): void
    {
        self::run(array_merge(self::functionStatements(), self::inboundLabTriggerStatements()));
    }

    public static function dropInboundLabTriggers(): void
    {
        self::run(self::dropTriggerStatements('inbound_labs', [
            'after_in_bound_lab_insert',
            'after_in_bound_lab_update',
            'after_in_bound_lab_delete',
        ]));
    }

    public static function installOutboundLabTriggers(): void
    {
        self::run(array_merge(self::functionStatements(), self::outboundLabTriggerStatements()));
    }

    public static function dropOutboundLabTriggers(): void
    {
        self::run(self::dropTriggerStatements('outbound_labs', [
            'after_out_bound_lab_insert',
            'after_out_bound_lab_update',
            'after_out_bound_lab_delete',
        ]));
    }

    public static function installBillExpenseTriggers(): void
    {
        self::run(array_merge(self::functionStatements(), self::billExpenseTriggerStatements()));
    }

    public static function dropBillExpenseTriggers(): void
    {
        self::run(self::dropTriggerStatements('bill_expenses', [
            'after_bill_expense_insert',
            'after_bill_expense_update',
            'after_bill_expense_delete',
        ]));
    }

    public static function allSql(): string
    {
        return implode("\n", array_merge(
            self::procedureStatements(),
            self::moneyAccountTransactionTriggerStatements(),
            self::peopleAccountTransactionTriggerStatements(),
            self::moneyTransferTriggerStatements(),
            self::expenseTriggerStatements(),
            self::cureTriggerStatements(),
            self::inboundLabTriggerStatements(),
            self::outboundLabTriggerStatements(),
            self::billExpenseTriggerStatements()
        ));
    }

    public static function procedureStatements(): array
    {
        return array_merge(self::dropProcedureStatements(false), self::functionStatements());
    }

    private static function moneyFunctionStatements(): array
    {
        return [
            self::updateMoneyBalanceFunction(),
            self::insertMoneyFunction(),
            self::updateMoneyFunction(),
            self::deleteMoneyFunction(),
        ];
    }

    private static function functionStatements(): array
    {
        return array_merge(self::moneyFunctionStatements(), [
            self::updatePeopleBalanceFunction(),
            self::insertPeopleFunction(),
            self::updatePeopleFunction(),
            self::deletePeopleFunction(),
        ]);
    }

    private static function updateMoneyBalanceFunction(): string
    {
        return <<<'SQL'
CREATE OR REPLACE FUNCTION update_money_account_balance(p_account_id bigint)
RETURNS void
LANGUAGE plpgsql
AS $fn$
DECLARE
    received_total numeric(15,2) := 0;
    paid_total numeric(15,2) := 0;
    new_balance numeric(15,2);
BEGIN
    SELECT
        COALESCE(SUM(CASE WHEN payment_type = 'received' THEN amount ELSE 0 END), 0),
        COALESCE(SUM(CASE WHEN payment_type = 'paid' THEN amount ELSE 0 END), 0)
    INTO received_total, paid_total
    FROM money_account_transactions
    WHERE money_account_id = p_account_id AND deleted_at IS NULL;

    new_balance := received_total - paid_total;

    UPDATE money_accounts SET balance = new_balance WHERE id = p_account_id;
END;
$fn$;
SQL;
    }

    private static function updatePeopleBalanceFunction(): string
    {
        return <<<'SQL'
CREATE OR REPLACE FUNCTION update_people_account_balance(p_account_id bigint)
RETURNS void
LANGUAGE plpgsql
AS $fn$
DECLARE
    received_total numeric(15,2) := 0;
    paid_total numeric(15,2) := 0;
    new_balance numeric(15,2);
BEGIN
    SELECT
        COALESCE(SUM(CASE WHEN payment_type = 'received' THEN amount ELSE 0 END), 0),
        COALESCE(SUM(CASE WHEN payment_type = 'paid' THEN amount ELSE 0 END), 0)
    INTO received_total, paid_total
    FROM people_account_transactions
    WHERE people_account_id = p_account_id AND deleted_at IS NULL;

    new_balance := paid_total - received_total;

    UPDATE people_accounts SET balance = new_balance WHERE id = p_account_id;
END;
$fn$;
SQL;
    }

    private static function insertMoneyFunction(): string
    {
        return <<<'SQL'
CREATE OR REPLACE FUNCTION insert_money_account_transaction(
    p_parent_record_id bigint,
    p_money_account_id bigint,
    p_people_id bigint,
    p_operation_type varchar,
    p_payment_type varchar,
    p_amount numeric,
    p_description text,
    p_date timestamp
) RETURNS void
LANGUAGE plpgsql
AS $fn$
BEGIN
    INSERT INTO money_account_transactions (
        parent_record_id,
        money_account_id,
        people_id,
        operation_type,
        payment_type,
        amount,
        description,
        date,
        created_at,
        updated_at
    ) VALUES (
        p_parent_record_id,
        p_money_account_id,
        p_people_id,
        p_operation_type,
        p_payment_type,
        p_amount,
        p_description,
        p_date,
        NOW(),
        NOW()
    );
END;
$fn$;
SQL;
    }

    private static function updateMoneyFunction(): string
    {
        return <<<'SQL'
CREATE OR REPLACE FUNCTION update_money_account_transaction(
    p_parent_record_id bigint,
    p_money_account_id bigint,
    p_people_id bigint,
    p_operation_type varchar,
    p_payment_type varchar,
    p_amount numeric,
    p_description text,
    p_date timestamp,
    p_deleted_at timestamp
) RETURNS void
LANGUAGE plpgsql
AS $fn$
BEGIN
    UPDATE money_account_transactions SET
        money_account_id = p_money_account_id,
        people_id = p_people_id,
        operation_type = p_operation_type,
        payment_type = p_payment_type,
        amount = p_amount,
        description = p_description,
        date = p_date,
        updated_at = NOW(),
        deleted_at = p_deleted_at
    WHERE parent_record_id = p_parent_record_id
      AND operation_type = p_operation_type;
END;
$fn$;
SQL;
    }

    private static function deleteMoneyFunction(): string
    {
        return <<<'SQL'
CREATE OR REPLACE FUNCTION delete_money_account_transaction(
    p_parent_record_id bigint,
    p_operation_type varchar
) RETURNS void
LANGUAGE plpgsql
AS $fn$
BEGIN
    UPDATE money_account_transactions
    SET deleted_at = NOW()
    WHERE parent_record_id = p_parent_record_id
      AND operation_type = p_operation_type;
END;
$fn$;
SQL;
    }

    private static function insertPeopleFunction(): string
    {
        return <<<'SQL'
CREATE OR REPLACE FUNCTION insert_people_account_transaction(
    p_parent_record_id bigint,
    p_people_acc_id bigint,
    p_money_account_id bigint,
    p_people_id bigint,
    p_transaction_type varchar,
    p_operation_type varchar,
    p_payment_type varchar,
    p_amount numeric,
    p_description text,
    p_date timestamp
) RETURNS void
LANGUAGE plpgsql
AS $fn$
BEGIN
    INSERT INTO people_account_transactions (
        parent_record_id,
        people_id,
        people_account_id,
        money_account_id,
        transaction_type,
        operation_type,
        payment_type,
        amount,
        description,
        date,
        created_at
    ) VALUES (
        p_parent_record_id,
        p_people_id,
        p_people_acc_id,
        p_money_account_id,
        p_transaction_type,
        p_operation_type,
        p_payment_type,
        p_amount,
        p_description,
        p_date,
        NOW()
    );
END;
$fn$;
SQL;
    }

    private static function updatePeopleFunction(): string
    {
        return <<<'SQL'
CREATE OR REPLACE FUNCTION update_people_account_transaction(
    p_parent_record_id bigint,
    p_people_acc_id bigint,
    p_money_account_id bigint,
    p_people_id bigint,
    p_transaction_type varchar,
    p_operation_type varchar,
    p_payment_type varchar,
    p_amount numeric,
    p_description text,
    p_date timestamp,
    p_deleted_at timestamp
) RETURNS void
LANGUAGE plpgsql
AS $fn$
BEGIN
    UPDATE people_account_transactions SET
        people_id = p_people_id,
        people_account_id = p_people_acc_id,
        money_account_id = p_money_account_id,
        transaction_type = p_transaction_type,
        operation_type = p_operation_type,
        payment_type = p_payment_type,
        amount = p_amount,
        description = p_description,
        date = p_date,
        deleted_at = p_deleted_at,
        updated_at = NOW()
    WHERE parent_record_id = p_parent_record_id
      AND operation_type = p_operation_type;
END;
$fn$;
SQL;
    }

    private static function deletePeopleFunction(): string
    {
        return <<<'SQL'
CREATE OR REPLACE FUNCTION delete_people_account_transaction(
    p_parent_record_id bigint,
    p_operation_type varchar
) RETURNS void
LANGUAGE plpgsql
AS $fn$
BEGIN
    UPDATE people_account_transactions
    SET deleted_at = NOW()
    WHERE parent_record_id = p_parent_record_id
      AND operation_type = p_operation_type;
END;
$fn$;
SQL;
    }

    private static function moneyAccountTransactionTriggerStatements(): array
    {
        return array_merge(self::dropTriggerStatements('money_account_transactions', [
            'after_money_account_transaction_insert',
            'after_money_account_transaction_update',
            'after_money_account_transaction_delete',
        ]), [
            self::triggerFunction('after_money_account_transaction_insert_fn', <<<'SQL'
BEGIN
    PERFORM update_money_account_balance(NEW.money_account_id);
    RETURN NEW;
END;
SQL),
            self::trigger('after_money_account_transaction_insert', 'AFTER INSERT', 'money_account_transactions', 'after_money_account_transaction_insert_fn'),
            self::triggerFunction('after_money_account_transaction_update_fn', <<<'SQL'
BEGIN
    PERFORM update_money_account_balance(OLD.money_account_id);
    PERFORM update_money_account_balance(NEW.money_account_id);
    RETURN NEW;
END;
SQL),
            self::trigger('after_money_account_transaction_update', 'AFTER UPDATE', 'money_account_transactions', 'after_money_account_transaction_update_fn'),
            self::triggerFunction('after_money_account_transaction_delete_fn', <<<'SQL'
BEGIN
    PERFORM update_money_account_balance(OLD.money_account_id);
    RETURN OLD;
END;
SQL),
            self::trigger('after_money_account_transaction_delete', 'AFTER DELETE', 'money_account_transactions', 'after_money_account_transaction_delete_fn'),
        ]);
    }

    private static function peopleAccountTransactionTriggerStatements(): array
    {
        $operation = self::quote(TransactionType::OPERATION);

        return array_merge(self::dropTriggerStatements('people_account_transactions', [
            'after_people_account_transaction_insert',
            'after_people_account_transaction_update',
            'after_people_account_transaction_delete',
        ]), [
            self::triggerFunction('after_people_account_transaction_insert_fn', <<<SQL
BEGIN
    PERFORM update_people_account_balance(NEW.people_account_id);
    IF NEW.transaction_type <> '{$operation}' THEN
        PERFORM insert_money_account_transaction(
            NEW.id,
            NEW.money_account_id,
            NEW.people_id,
            NEW.operation_type,
            NEW.payment_type,
            NEW.amount,
            NEW.description,
            NEW.date
        );
    END IF;
    RETURN NEW;
END;
SQL),
            self::trigger('after_people_account_transaction_insert', 'AFTER INSERT', 'people_account_transactions', 'after_people_account_transaction_insert_fn'),
            self::triggerFunction('after_people_account_transaction_update_fn', <<<SQL
BEGIN
    IF OLD.people_account_id <> NEW.people_account_id THEN
        PERFORM update_people_account_balance(OLD.people_account_id);
        PERFORM update_people_account_balance(NEW.people_account_id);
    ELSE
        PERFORM update_people_account_balance(NEW.people_account_id);
    END IF;

    IF NEW.transaction_type <> '{$operation}' THEN
        PERFORM update_money_account_transaction(
            NEW.id,
            NEW.money_account_id,
            NEW.people_id,
            NEW.operation_type,
            NEW.payment_type,
            NEW.amount,
            NEW.description,
            NEW.date,
            NEW.deleted_at
        );
    END IF;
    RETURN NEW;
END;
SQL),
            self::trigger('after_people_account_transaction_update', 'AFTER UPDATE', 'people_account_transactions', 'after_people_account_transaction_update_fn'),
            self::triggerFunction('after_people_account_transaction_delete_fn', <<<SQL
BEGIN
    PERFORM update_people_account_balance(OLD.people_account_id);
    IF OLD.transaction_type <> '{$operation}' THEN
        PERFORM delete_money_account_transaction(OLD.id, OLD.operation_type);
    END IF;
    RETURN OLD;
END;
SQL),
            self::trigger('after_people_account_transaction_delete', 'AFTER DELETE', 'people_account_transactions', 'after_people_account_transaction_delete_fn'),
        ]);
    }

    private static function moneyTransferTriggerStatements(): array
    {
        $operation = self::quote(OperationType::MONEY_ACCOUNT_TRANSFER);
        $paid = self::quote(PaymentType::PAID);
        $received = self::quote(PaymentType::RECEIVED);

        return array_merge(self::dropTriggerStatements('money_transfers', [
            'after_money_transfer_insert',
            'after_money_transfer_update',
            'before_money_transfer_delete',
        ]), [
            self::triggerFunction('after_money_transfer_insert_fn', <<<SQL
BEGIN
    PERFORM insert_money_account_transaction(
        NEW.id,
        NEW.from_account_id,
        NULL,
        '{$operation}',
        '{$paid}',
        NEW.amount,
        NEW.description,
        NEW.date
    );
    PERFORM insert_money_account_transaction(
        NEW.id,
        NEW.to_account_id,
        NULL,
        '{$operation}',
        '{$received}',
        NEW.amount,
        NEW.description,
        NEW.date
    );
    RETURN NEW;
END;
SQL),
            self::trigger('after_money_transfer_insert', 'AFTER INSERT', 'money_transfers', 'after_money_transfer_insert_fn'),
            self::triggerFunction('after_money_transfer_update_fn', <<<SQL
BEGIN
    PERFORM delete_money_account_transaction(OLD.id, '{$operation}');
    IF NEW.deleted_at IS NULL THEN
        PERFORM insert_money_account_transaction(
            NEW.id,
            NEW.from_account_id,
            NULL,
            '{$operation}',
            '{$paid}',
            NEW.amount,
            NEW.description,
            NEW.date
        );
        PERFORM insert_money_account_transaction(
            NEW.id,
            NEW.to_account_id,
            NULL,
            '{$operation}',
            '{$received}',
            NEW.amount,
            NEW.description,
            NEW.date
        );
    END IF;
    RETURN NEW;
END;
SQL),
            self::trigger('after_money_transfer_update', 'AFTER UPDATE', 'money_transfers', 'after_money_transfer_update_fn'),
            self::triggerFunction('before_money_transfer_delete_fn', <<<SQL
BEGIN
    PERFORM delete_money_account_transaction(OLD.id, '{$operation}');
    RETURN OLD;
END;
SQL),
            self::trigger('before_money_transfer_delete', 'BEFORE DELETE', 'money_transfers', 'before_money_transfer_delete_fn'),
        ]);
    }

    private static function expenseTriggerStatements(): array
    {
        $operation = self::quote(OperationType::EXPENSE);
        $paid = self::quote(PaymentType::PAID);
        $amount = Expense::COLUMN_AMOUNT;
        $account = Expense::COLUMN_MONEY_ACCOUNT_ID;
        $description = Expense::COLUMN_DESCRIPTION;
        $date = Expense::COLUMN_DATE;

        return array_merge(self::dropTriggerStatements('expenses', [
            'after_expense_insert',
            'after_expense_update',
            'after_expense_delete',
        ]), [
            self::triggerFunction('after_expense_insert_fn', <<<SQL
BEGIN
    PERFORM insert_money_account_transaction(
        NEW.id,
        NEW.{$account},
        NULL,
        '{$operation}',
        '{$paid}',
        NEW.{$amount},
        NEW.{$description},
        NEW.{$date}
    );
    RETURN NEW;
END;
SQL),
            self::trigger('after_expense_insert', 'AFTER INSERT', 'expenses', 'after_expense_insert_fn'),
            self::triggerFunction('after_expense_update_fn', <<<SQL
BEGIN
    PERFORM update_money_account_transaction(
        NEW.id,
        NEW.{$account},
        NULL,
        '{$operation}',
        '{$paid}',
        NEW.{$amount},
        NEW.{$description},
        NEW.{$date},
        NEW.deleted_at
    );
    RETURN NEW;
END;
SQL),
            self::trigger('after_expense_update', 'AFTER UPDATE', 'expenses', 'after_expense_update_fn'),
            self::triggerFunction('after_expense_delete_fn', <<<SQL
BEGIN
    PERFORM delete_money_account_transaction(OLD.id, '{$operation}');
    RETURN OLD;
END;
SQL),
            self::trigger('after_expense_delete', 'AFTER DELETE', 'expenses', 'after_expense_delete_fn'),
        ]);
    }

    private static function cureTriggerStatements(): array
    {
        $operation = self::quote(TransactionType::OPERATION);
        $payment = self::quote(TransactionType::PAYMENT);
        $cycle = self::quote(OperationType::CURE_CYLCE);
        $cyclePayment = self::quote(OperationType::CURE_CYLCE_PAYMENT);
        $paid = self::quote(PaymentType::PAID);
        $received = self::quote(PaymentType::RECEIVED);

        return array_merge(self::dropTriggerStatements('cures', [
            'after_cure_cycle_insert',
            'after_cure_cycle_update',
            'after_cure_cycle_delete',
        ]), [
            self::triggerFunction('after_cure_cycle_insert_fn', <<<SQL
BEGIN
    PERFORM insert_people_account_transaction(
        NEW.id, NEW.people_account_id, NEW.money_account_id, NEW.patient_id,
        '{$operation}', '{$cycle}', '{$paid}', NEW.grand_total, NEW.description, NEW.start_date
    );
    IF NEW.paid > 0 THEN
        PERFORM insert_people_account_transaction(
            NEW.id, NEW.people_account_id, NEW.money_account_id, NEW.patient_id,
            '{$payment}', '{$cyclePayment}', '{$received}', NEW.paid, NEW.description, NEW.start_date
        );
    END IF;
    RETURN NEW;
END;
SQL),
            self::trigger('after_cure_cycle_insert', 'AFTER INSERT', 'cures', 'after_cure_cycle_insert_fn'),
            self::triggerFunction('after_cure_cycle_update_fn', <<<SQL
BEGIN
    PERFORM update_people_account_transaction(
        NEW.id, NEW.people_account_id, NEW.money_account_id, NEW.patient_id,
        '{$operation}', '{$cycle}', '{$paid}', NEW.grand_total, NEW.description, NEW.start_date, NEW.deleted_at
    );
    IF NEW.paid <> OLD.paid OR NEW.money_account_id <> OLD.money_account_id THEN
        IF NEW.paid = 0 THEN
            PERFORM delete_people_account_transaction(NEW.id, '{$cyclePayment}');
        ELSIF OLD.paid > 0 THEN
            PERFORM update_people_account_transaction(
                NEW.id, NEW.people_account_id, NEW.money_account_id, NEW.patient_id,
                '{$payment}', '{$cyclePayment}', '{$received}', NEW.paid, NEW.description, NEW.start_date, NEW.deleted_at
            );
        ELSIF NEW.paid > 0 THEN
            PERFORM insert_people_account_transaction(
                NEW.id, NEW.people_account_id, NEW.money_account_id, NEW.patient_id,
                '{$payment}', '{$cyclePayment}', '{$received}', NEW.paid, NEW.description, NEW.start_date
            );
        END IF;
    END IF;
    RETURN NEW;
END;
SQL),
            self::trigger('after_cure_cycle_update', 'AFTER UPDATE', 'cures', 'after_cure_cycle_update_fn'),
            self::triggerFunction('after_cure_cycle_delete_fn', <<<SQL
BEGIN
    PERFORM delete_people_account_transaction(OLD.id, '{$cycle}');
    RETURN OLD;
END;
SQL),
            self::trigger('after_cure_cycle_delete', 'AFTER DELETE', 'cures', 'after_cure_cycle_delete_fn'),
        ]);
    }

    private static function inboundLabTriggerStatements(): array
    {
        $operation = self::quote(TransactionType::OPERATION);
        $payment = self::quote(TransactionType::PAYMENT);
        $lab = self::quote(OperationType::IN_BOUND_LAB);
        $paid = self::quote(PaymentType::PAID);
        $received = self::quote(PaymentType::RECEIVED);
        $deleteOperation = self::quote(OperationType::IN_BOUND_LAB_PAYMENT);

        return array_merge(self::dropTriggerStatements('inbound_labs', [
            'after_in_bound_lab_insert',
            'after_in_bound_lab_update',
            'after_in_bound_lab_delete',
        ]), [
            self::triggerFunction('after_in_bound_lab_insert_fn', <<<SQL
BEGIN
    PERFORM insert_people_account_transaction(
        NEW.id, NEW.people_account_id, NEW.money_account_id, NEW.customer_id,
        '{$operation}', '{$lab}', '{$paid}', NEW.grand_total, NEW.description, NEW.issue_at
    );
    IF NEW.paid > 0 THEN
        PERFORM insert_people_account_transaction(
            NEW.id, NEW.people_account_id, NEW.money_account_id, NEW.customer_id,
            '{$payment}', '{$lab}', '{$received}', NEW.paid, NEW.description, NEW.issue_at
        );
    END IF;
    RETURN NEW;
END;
SQL),
            self::trigger('after_in_bound_lab_insert', 'AFTER INSERT', 'inbound_labs', 'after_in_bound_lab_insert_fn'),
            self::triggerFunction('after_in_bound_lab_update_fn', <<<SQL
BEGIN
    PERFORM update_people_account_transaction(
        NEW.id, NEW.people_account_id, NEW.money_account_id, NEW.customer_id,
        '{$operation}', '{$lab}', '{$paid}', NEW.grand_total, NEW.description, NEW.issue_at, NEW.deleted_at
    );
    RETURN NEW;
END;
SQL),
            self::trigger('after_in_bound_lab_update', 'AFTER UPDATE', 'inbound_labs', 'after_in_bound_lab_update_fn'),
            self::triggerFunction('after_in_bound_lab_delete_fn', <<<SQL
BEGIN
    PERFORM delete_people_account_transaction(OLD.id, '{$deleteOperation}');
    RETURN OLD;
END;
SQL),
            self::trigger('after_in_bound_lab_delete', 'AFTER DELETE', 'inbound_labs', 'after_in_bound_lab_delete_fn'),
        ]);
    }

    private static function outboundLabTriggerStatements(): array
    {
        $operation = self::quote(TransactionType::OPERATION);
        $payment = self::quote(TransactionType::PAYMENT);
        $lab = self::quote(OperationType::OUT_BOUND_LAB);
        $received = self::quote(PaymentType::RECEIVED);
        $paid = self::quote(PaymentType::PAID);
        $expense = self::quote(OperationType::EXPENSE);

        return array_merge(self::dropTriggerStatements('outbound_labs', [
            'after_out_bound_lab_insert',
            'after_out_bound_lab_update',
            'after_out_bound_lab_delete',
        ]), [
            self::triggerFunction('after_out_bound_lab_insert_fn', <<<SQL
BEGIN
    PERFORM insert_people_account_transaction(
        NEW.id, NEW.people_account_id, NEW.money_account_id, NEW.supplier_id,
        '{$operation}', '{$lab}', '{$received}', NEW.grand_total, NEW.description, NEW.issue_at
    );
    IF NEW.paid > 0 THEN
        PERFORM insert_people_account_transaction(
            NEW.id, NEW.people_account_id, NEW.money_account_id, NEW.supplier_id,
            '{$payment}', '{$lab}', '{$paid}', NEW.paid, NEW.description, NEW.issue_at
        );
    END IF;
    RETURN NEW;
END;
SQL),
            self::trigger('after_out_bound_lab_insert', 'AFTER INSERT', 'outbound_labs', 'after_out_bound_lab_insert_fn'),
            self::triggerFunction('after_out_bound_lab_update_fn', <<<SQL
BEGIN
    PERFORM update_people_account_transaction(
        NEW.id, NEW.people_account_id, NEW.money_account_id, NEW.supplier_id,
        '{$operation}', '{$lab}', '{$paid}', NEW.grand_total, NEW.description, NEW.issue_at, NEW.deleted_at
    );
    RETURN NEW;
END;
SQL),
            self::trigger('after_out_bound_lab_update', 'AFTER UPDATE', 'outbound_labs', 'after_out_bound_lab_update_fn'),
            self::triggerFunction('after_out_bound_lab_delete_fn', <<<SQL
BEGIN
    PERFORM delete_money_account_transaction(OLD.id, '{$expense}');
    RETURN OLD;
END;
SQL),
            self::trigger('after_out_bound_lab_delete', 'AFTER DELETE', 'outbound_labs', 'after_out_bound_lab_delete_fn'),
        ]);
    }

    private static function billExpenseTriggerStatements(): array
    {
        $operation = self::quote(TransactionType::OPERATION);
        $payment = self::quote(TransactionType::PAYMENT);
        $lab = self::quote(OperationType::OUT_BOUND_LAB);
        $received = self::quote(PaymentType::RECEIVED);
        $paid = self::quote(PaymentType::PAID);
        $expense = self::quote(OperationType::EXPENSE);

        return array_merge(self::dropTriggerStatements('bill_expenses', [
            'after_bill_expense_insert',
            'after_bill_expense_update',
            'after_bill_expense_delete',
        ]), [
            self::triggerFunction('after_bill_expense_insert_fn', <<<SQL
BEGIN
    PERFORM insert_people_account_transaction(
        NEW.id, NEW.people_account_id, NEW.money_account_id, NEW.supplier_id,
        '{$operation}', '{$lab}', '{$received}', NEW.grand_total, NEW.note, NEW.bill_date
    );
    IF NEW.paid > 0 THEN
        PERFORM insert_people_account_transaction(
            NEW.id, NEW.people_account_id, NEW.money_account_id, NEW.supplier_id,
            '{$payment}', '{$lab}', '{$paid}', NEW.paid, NEW.note, NEW.bill_date
        );
    END IF;
    RETURN NEW;
END;
SQL),
            self::trigger('after_bill_expense_insert', 'AFTER INSERT', 'bill_expenses', 'after_bill_expense_insert_fn'),
            self::triggerFunction('after_bill_expense_update_fn', <<<SQL
BEGIN
    PERFORM update_people_account_transaction(
        NEW.id, NEW.people_account_id, NEW.money_account_id, NEW.supplier_id,
        '{$operation}', '{$lab}', '{$paid}', NEW.grand_total, NEW.note, NEW.bill_date, NEW.deleted_at
    );
    RETURN NEW;
END;
SQL),
            self::trigger('after_bill_expense_update', 'AFTER UPDATE', 'bill_expenses', 'after_bill_expense_update_fn'),
            self::triggerFunction('after_bill_expense_delete_fn', <<<SQL
BEGIN
    PERFORM delete_money_account_transaction(OLD.id, '{$expense}');
    RETURN OLD;
END;
SQL),
            self::trigger('after_bill_expense_delete', 'AFTER DELETE', 'bill_expenses', 'after_bill_expense_delete_fn'),
        ]);
    }

    private static function dropProcedureStatements(bool $cascade): array
    {
        $names = [
            'insert_money_account_transaction',
            'update_money_account_transaction',
            'delete_money_account_transaction',
            'insert_people_account_transaction',
            'update_people_account_transaction',
            'delete_people_account_transaction',
            'update_money_account_balance',
            'update_people_account_balance',
        ];
        $list = implode(', ', array_map(fn ($name) => "'".$name."'", $names));
        $cascadeSql = $cascade ? ' CASCADE' : '';

        return [<<<SQL
DO \$body\$
DECLARE r record;
BEGIN
    FOR r IN
        SELECT p.oid::regprocedure AS signature
        FROM pg_proc p
        JOIN pg_namespace n ON n.oid = p.pronamespace
        WHERE n.nspname = 'public'
          AND p.proname IN ({$list})
    LOOP
        EXECUTE 'DROP FUNCTION IF EXISTS ' || r.signature || '{$cascadeSql}';
    END LOOP;
END;
\$body\$;
SQL];
    }

    private static function dropTriggerStatements(string $table, array $triggers): array
    {
        $statements = [];
        foreach ($triggers as $trigger) {
            $statements[] = "DROP TRIGGER IF EXISTS {$trigger} ON {$table}";
            $statements[] = "DROP FUNCTION IF EXISTS {$trigger}_fn()";
        }

        return $statements;
    }

    private static function triggerFunction(string $name, string $body): string
    {
        return "CREATE OR REPLACE FUNCTION {$name}()\nRETURNS trigger\nLANGUAGE plpgsql\nAS \$fn\$\n{$body}\n\$fn\$;";
    }

    private static function trigger(string $name, string $timing, string $table, string $function): string
    {
        return "CREATE TRIGGER {$name}\n{$timing} ON {$table}\nFOR EACH ROW\nEXECUTE PROCEDURE {$function}();";
    }

    private static function quote(string $value): string
    {
        return str_replace("'", "''", $value);
    }

    private static function run(array $statements): void
    {
        if (! SchemaDialect::postgres()) {
            throw new RuntimeException('PostgreSQL ledger SQL was requested on a non-PostgreSQL connection.');
        }

        foreach ($statements as $statement) {
            DB::unprepared($statement);
        }
    }
}
