<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\BillExpense;
use App\Models\Category;
use App\Models\Cure;
use App\Models\CureCycle;
use App\Models\CurePayment;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\InboundLab;
use App\Models\Lead;
use App\Models\MoneyAccount;
use App\Models\MoneyAccountTransaction;
use App\Models\MoneyTransfer;
use App\Models\OutboundLab;
use App\Models\OwnerPickup;
use App\Models\Payment;
use App\Models\People;
use App\Models\PeopleAccount;
use App\Models\PeopleAccountTransaction;
use App\Models\Product;
use App\Models\Salary;
use App\Models\Service;
use App\Models\ServiceGroup;
use App\Models\Stage;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class DeletedHistoryController extends Controller
{
    private function models(): array
    {
        return [
            'people' => People::class,
            'appointment' => Appointment::class,
            'lead' => Lead::class,
            'cureCycle' => CureCycle::class,
            'cure' => Cure::class,
            'curePayment' => CurePayment::class,
            'expense' => Expense::class,
            'billExpense' => BillExpense::class,
            'payment' => Payment::class,
            'ownerPickup' => OwnerPickup::class,
            'inboundLaboratory' => InboundLab::class,
            'outboundLaboratory' => OutboundLab::class,
            'moneyAccount' => MoneyAccount::class,
            'moneyTransfer' => MoneyTransfer::class,
            'peopleAccount' => PeopleAccount::class,
            'category' => Category::class,
            'leadStage' => Stage::class,
            'service' => Service::class,
            'serviceGroup' => ServiceGroup::class,
            'expenseProduct' => Product::class,
            'expenseCategory' => ExpenseCategory::class,
            'salary' => Salary::class,
            'accountTransaction' => MoneyAccountTransaction::class,
            'peopleTransaction' => PeopleAccountTransaction::class,
        ];
    }

    public function index(Request $request)
    {
        $type = $request->query('type');
        $models = $this->models();
        if ($type) {
            abort_unless(isset($models[$type]), 422);
            $models = [$type => $models[$type]];
        }

        $items = [];
        foreach ($models as $key => $class) {
            foreach ($class::onlyTrashed()->latest('deleted_at')->limit(80)->get() as $row) {
                $items[] = [
                    'type' => $key,
                    'id' => $row->id,
                    'label' => $this->label($row),
                    'deletedAt' => optional($row->deleted_at)->format('Y-m-d H:i'),
                ];
            }
        }

        usort($items, fn ($a, $b) => strcmp($b['deletedAt'] ?? '', $a['deletedAt'] ?? ''));

        return ['data' => array_slice($items, 0, 200)];
    }

    public function restoreItem(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'string'],
            'id' => ['required', 'integer'],
        ]);

        $models = $this->models();
        abort_unless(isset($models[$data['type']]), 422);

        $row = $models[$data['type']]::onlyTrashed()->findOrFail($data['id']);
        $row->restore();
        AuditLogger::log('restored', $row::class, (int) $row->id, [
            'label' => $this->label($row),
        ]);

        return ['restored' => true];
    }

    private function label(object $row): string
    {
        foreach (['name', 'reference', 'phone', 'description', 'amount'] as $field) {
            if (isset($row->{$field}) && $row->{$field} !== null && $row->{$field} !== '') {
                return (string) $row->{$field};
            }
        }

        return '#'.$row->id;
    }
}
