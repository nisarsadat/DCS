<?php

namespace App\Http\Controllers;

use App\Models\MoneyAccountTransaction;
use App\Models\People;
use App\Support\SqlCompat;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Morilog\Jalali\Jalalian;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        // Gregorian Dates
        $today = Carbon::today();
        $currentYear = Carbon::now()->year;
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        // Hijri Dates
        $jalaliToday = Jalalian::fromCarbon($today)->format('Y-m-d');
        $jalaliStartOfMonth = Jalalian::fromCarbon($startOfMonth)->format('Y-m-d');
        $jalaliEndOfMonth = Jalalian::fromCarbon($endOfMonth)->format('Y-m-d');
        $jalaliStartOfLastMonth = Jalalian::fromCarbon($startOfLastMonth)->format('Y-m-d');
        $jalaliEndOfLastMonth = Jalalian::fromCarbon($endOfLastMonth)->format('Y-m-d');
        $currentJalaliYear = Jalalian::now()->format('Y-m-d');


        // All-time earnings and expenses
        $totalEarnings = MoneyAccountTransaction::where('payment_type', 'received')->sum('amount');
        $totalAllExpenses = MoneyAccountTransaction::where('payment_type', 'paid')->sum('amount');
        $netProfit = $totalEarnings - $totalAllExpenses;

        // Monthly Earnings and Expenses (this month)
        $monthlyEarnings = MoneyAccountTransaction::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('payment_type', 'received')->sum('amount');

        $totalMonthlyExpenses = MoneyAccountTransaction::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('payment_type', 'paid')->sum('amount');

        $thisMonthProfit = $monthlyEarnings - $totalMonthlyExpenses;

        // Last Month Profit
        $lastMonthEarnings = MoneyAccountTransaction::whereBetween('date', [$startOfLastMonth, $endOfLastMonth])
            ->where('payment_type', 'received')->sum('amount');

        $totalLastMonthExpenses = MoneyAccountTransaction::whereBetween('date', [$startOfLastMonth, $endOfLastMonth])
            ->where('payment_type', 'paid')->sum('amount');

        $lastMonthProfit = $lastMonthEarnings - $totalLastMonthExpenses;

        // New Patients Today
        $newPatients = People::where('type', 'patient')->whereDate('created_at', $today)->count();
        $totalPatients = People::where('type', 'patient')->count();

        // Daily Expenses
        $dailyExpenses = DB::table('expenses')
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->selectRaw(
                'expense_categories.name as categoryName, 
                 SUM(expenses.amount) as totalExpense,
                 (SUM(expenses.amount) / NULLIF((SELECT SUM(amount) FROM expenses WHERE '.SqlCompat::dateEquals('date').'), 0)) * 100 as percentage',
                [$today->format('Y-m-d')]
            )
            ->whereDate('expenses.date', $today)
            ->groupBy('expense_categories.name')
            ->orderByDesc('totalExpense')
            ->get();

        // Monthly Expenses
        $monthlyExpenses = DB::table('expenses')
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->selectRaw(
                'expense_categories.name as categoryName,
                 SUM(expenses.amount) as totalExpense,
                 (SUM(expenses.amount) / 
                  NULLIF((SELECT SUM(amount) FROM expenses WHERE date BETWEEN ? AND ?), 0)) * 100 as percentage',
                [$startOfMonth, $endOfMonth]
            )
            ->whereBetween('expenses.date', [$startOfMonth, $endOfMonth])
            ->groupBy('expense_categories.name')
            ->orderByDesc('totalExpense')
            ->get();

        // Yearly Expenses
        $yearlyExpenses = DB::table('expenses')
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->selectRaw(
                'expense_categories.name as categoryName,
                 SUM(expenses.amount) as totalExpense,
                 (SUM(expenses.amount) / 
                  NULLIF((SELECT SUM(amount) FROM expenses WHERE '.SqlCompat::yearEquals('date').'), 0)) * 100 as percentage',
                [$currentYear]
            )
            ->whereYear('expenses.date', $currentYear)
            ->groupBy('expense_categories.name')
            ->orderByDesc('totalExpense')
            ->get();

        // Upcoming Appointments
        $upcomingAppointments = DB::table('appointments')
            ->join('people', 'appointments.people_id', '=', 'people.id')
            ->select('people.name', 'people.phone', DB::raw(SqlCompat::timeExpression('appointments.date_time').' as time'))
            ->whereDate('appointments.date_time', '>=', $today)
            ->orderBy('appointments.date_time', 'asc')
            ->limit(5)
            ->get();

        // Income and Expense per month for chart
        $everyMonthExpenses = [];
$everyMonthIncomes = [];
$everyMonthProfits = [];

for ($i = 1; $i <= 12; $i++) {
    $expenseAmount = DB::table('money_account_transactions')
        ->whereMonth('date', $i)
        ->whereYear('date', $currentYear)
        ->where('payment_type', 'paid')
        ->sum('amount');

    $incomeAmount = DB::table('money_account_transactions')
        ->whereMonth('date', $i)
        ->whereYear('date', $currentYear)
        ->where('payment_type', 'received')
        ->sum('amount');


    $everyMonthExpenses[$i - 1] = $expenseAmount;
    $everyMonthIncomes[$i - 1] = $incomeAmount;
    $everyMonthProfits[$i - 1] =$incomeAmount - $expenseAmount;
}


        [$from, $to] = $this->resolveRange($request, $today);
        $periodIncome = (float) MoneyAccountTransaction::whereBetween('date', [$from, $to])
            ->where('payment_type', 'received')->sum('amount');
        $periodExpense = (float) MoneyAccountTransaction::whereBetween('date', [$from, $to])
            ->where('payment_type', 'paid')->sum('amount');

        $todayAppointments = DB::table('appointments')->whereDate('date_time', $today)->count();
        $openStatuses = ['completed', 'cancelled', 'canceled', 'noshow', 'no_show', 'no-show'];
        $waitingPatients = DB::table('appointments')
            ->whereDate('date_time', $today)
            ->whereRaw('LOWER(status) not in (?, ?, ?, ?, ?, ?)', $openStatuses)
            ->count();
        $activeTreatments = DB::table('cures')->whereNull('deleted_at')
            ->whereRaw('LOWER(status) not in (?, ?, ?)', ['completed', 'cancelled', 'canceled'])
            ->count();
        $completedTreatments = DB::table('cures')->whereNull('deleted_at')
            ->whereRaw('LOWER(status) = ?', ['completed'])
            ->count();
        $pendingLabs = Schema::hasTable('outbound_labs')
            ? DB::table('outbound_labs')->whereNull('deleted_at')->whereDate('return_date', '>=', $today)->count()
            : 0;
        $overdueLabs = Schema::hasTable('outbound_labs')
            ? DB::table('outbound_labs')->whereNull('deleted_at')->whereDate('return_date', '<', $today)->count()
            : 0;
        $outstanding = (float) DB::table('people_accounts')
            ->join('people', 'people_accounts.people_id', '=', 'people.id')
            ->where('people.type', 'patient')
            ->where('people_accounts.balance', '>', 0)
            ->whereNull('people_accounts.deleted_at')
            ->sum('people_accounts.balance');
        $monthNewPatients = People::where('type', 'patient')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

        $lowStock = 0;
        $outOfStock = 0;
        if (Schema::hasTable('inventory_items')) {
            $lowStock = DB::table('inventory_items')->where('is_active', true)
                ->whereColumn('quantity', '<=', 'min_stock')->where('quantity', '>', 0)->count();
            $outOfStock = DB::table('inventory_items')->where('is_active', true)->where('quantity', '<=', 0)->count();
        }

        return [
            'thisMonthProfit' => $thisMonthProfit,
            'lastMonthProfit' => $lastMonthProfit,
            'newPatients' => $newPatients,
            'monthNewPatients' => $monthNewPatients,
            'totalPatients' => $totalPatients,
            'totalEarnings' => $totalEarnings,
            'totalAllExpenses' => $totalAllExpenses,
            'netProfit' => $netProfit,
            'dailyExpenses' => $dailyExpenses,
            'monthlyExpenses' => $monthlyExpenses,
            'yearlyExpenses' => $yearlyExpenses,
            'upcomingAppointments' => $upcomingAppointments,
            'monthExpenses' => $everyMonthExpenses,
            'monthIncomes' => $everyMonthIncomes,
            'monthProfits' => $everyMonthProfits,
            'range' => $request->query('range', 'month'),
            'periodIncome' => $periodIncome,
            'periodExpense' => $periodExpense,
            'periodProfit' => $periodIncome - $periodExpense,
            'todayAppointments' => $todayAppointments,
            'waitingPatients' => $waitingPatients,
            'activeTreatments' => $activeTreatments,
            'completedTreatments' => $completedTreatments,
            'pendingLabs' => $pendingLabs,
            'overdueLabs' => $overdueLabs,
            'outstanding' => $outstanding,
            'lowStock' => $lowStock,
            'outOfStock' => $outOfStock,
        ];
    }

    private function resolveRange(Request $request, Carbon $today): array
    {
        $range = $request->query('range', 'month');

        try {
            return match ($range) {
                'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
                'week' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()],
                'year' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
                'custom' => [
                    Carbon::parse($request->query('from', $today))->startOfDay(),
                    Carbon::parse($request->query('to', $today))->endOfDay(),
                ],
                default => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            };
        } catch (\Throwable $exception) {
            return [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()];
        }
    }
}

