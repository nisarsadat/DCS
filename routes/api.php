<?php

use App\Http\Controllers\MoneyAccountTransactionController;
use App\Http\Resources\UserResource;
use App\Models\MoneyAccountTransaction;
use App\Models\Service;
use App\Models\Laboratory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CureController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\StageController;
use App\Http\Controllers\ToothController;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\DentistController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CureCycleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaboratoryController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BillExpenseController;
use App\Http\Controllers\CurePaymentController;
use App\Http\Controllers\OwnerPickupController;
use App\Http\Controllers\ServiceGroupController;
use App\Http\Controllers\PeopleAccountController;
use App\Http\Controllers\ServiceReportController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\ClinicDataController;
use App\Http\Controllers\DeletedHistoryController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ProfitLossReportController;
use App\Http\Controllers\OwnerPickupReportController;
use App\Http\Controllers\ExpenseProductReportController;
use App\Http\Controllers\PatientPaymentReportController;
use App\Http\Controllers\ExpenseCategoryReportController;
use App\Http\Controllers\InboundLabController;
use App\Http\Controllers\PeopleAccountTransactionController;
use App\Http\Controllers\MoneyAccountController;
use App\Http\Controllers\MoneyTransferController;
use App\Http\Controllers\OutboundLabController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TwoFactorController;
use App\Models\InboundLab;
use App\Models\Salary;

// Authentication routes
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/two-factor/challenge', [TwoFactorController::class, 'challenge'])->middleware('throttle:login');
Route::get('/', function(){
    return view('welcome');
});

// Group all routes that need authentication
Route::middleware('auth:sanctum')->group(function () {

    // Authenticated user route
    Route::get('/me', [UserController::class, 'me']);

    // Resource routes that require authentication
    Route::apiResource('/expenseCategories', ExpenseCategoryController::class);
    Route::apiResource('/billExpenses', BillExpenseController::class);
    Route::delete('/billExpenseBulkDelete', [BillExpenseController::class,"bulkDelete"]);
    Route::apiResource('/ownerPickups', OwnerPickupController::class);
    Route::apiResource('/products', ProductController::class);
    Route::apiResource('/expenses', ExpenseController::class);
    Route::delete('/expensesBulkDelete',[ ExpenseController::class,"bulkDelete"]);

    Route::apiResource('/payments', PaymentController::class);
    Route::apiResource('/suppliers', SupplierController::class);
    Route::apiResource('/dentists', DentistController::class);
    Route::delete('/dentistBulkDelete', [DentistController::class,'bulkDelete']);
    
    Route::post('/dentists/updateDentist/{dentist}', [DentistController::class, 'updateDentist']);
    Route::apiResource('/owners', OwnerController::class);
    Route::post('/owners/updateOwners/{owner}',[OwnerController::class,'updateOwner']);
    Route::apiResource('/users', UserController::class);
    Route::post('/users/updateUsers/{user}',[UserController::class,'updateUser'])->name('updateUsers');
    Route::put('/users/status/{user}', [UserController::class, 'updateStatus'])->name('users.updateStatus');

    Route::apiResource('/patients', PatientController::class);
    Route::apiResource('/inboundLab', InboundLabController::class);
    Route::apiResource('/outboundLab', OutboundLabController::class);
    Route::apiResource('/tooths', ToothController::class);
    Route::apiResource('/peoples', PeopleController::class);
    Route::apiResource('/appointments', AppointmentController::class);
    Route::apiResource('/categories', CategoryController::class);
    Route::apiResource('/stages', StageController::class);
    Route::apiResource('/leads', LeadController::class);
    Route::delete('/leadBulkDelete', [LeadController::class,'bulkDelete']);
    Route::put('/leads/stage/{lead}', [LeadController::class, 'updateStage'])->name('leads.updateStage');

    Route::apiResource('/services', ServiceController::class);
    Route::apiResource('/serviceGroups', ServiceGroupController::class);

    Route::get('system/backups', [ClinicDataController::class, 'index']);
    Route::post('system/backups', [ClinicDataController::class, 'store']);
    Route::get('system/backups/{file}', [ClinicDataController::class, 'download']);
    Route::post('system/restore', [ClinicDataController::class, 'upload']);
    Route::post('system/reset', [ClinicDataController::class, 'reset']);

    // System Settings
    Route::post('/systemSettings/updateSettings/{systemSetting}', [SystemSettingController::class, 'updateSetting']);
    Route::apiResource('/systemSettings', SystemSettingController::class);

    // Cure-related routes
    Route::apiResource('/cures', CureController::class);
    Route::apiResource('/curePayments', CurePaymentController::class);
    Route::apiResource('/cureCycles', CureCycleController::class);

    // Logout route (requires authentication)
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('/role_permissions', RolePermissionController::class);

    // Reports
    Route::get('dashboardReport', DashboardController::class);
    Route::get('search', SearchController::class);

    Route::get('inventory/summary', [InventoryController::class, 'summary']);
    Route::get('inventory/report', [InventoryController::class, 'report']);
    Route::get('inventory/movements', [InventoryController::class, 'movements']);
    Route::get('inventory/categories', [InventoryController::class, 'categories']);
    Route::post('inventory/categories', [InventoryController::class, 'storeCategory']);
    Route::get('inventory/items', [InventoryController::class, 'index']);
    Route::post('inventory/items', [InventoryController::class, 'store']);
    Route::put('inventory/items/{inventoryItem}', [InventoryController::class, 'update']);
    Route::post('inventory/items/{inventoryItem}/move', [InventoryController::class, 'move']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/read', [NotificationController::class, 'read']);
    Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
    Route::get('notification-preferences', [NotificationController::class, 'preferences']);
    Route::put('notification-preferences', [NotificationController::class, 'savePreferences']);

    Route::post('two-factor/enable', [TwoFactorController::class, 'enable']);
    Route::post('two-factor/confirm', [TwoFactorController::class, 'confirm']);
    Route::post('two-factor/disable', [TwoFactorController::class, 'disable']);
    Route::get('deleted-history', [DeletedHistoryController::class, 'index']);
    Route::post('deleted-history/restore', [DeletedHistoryController::class, 'restoreItem']);

    Route::get('financialReport', ProfitLossReportController::class);
    Route::get('pickupReport', OwnerPickupReportController::class);
    Route::get('expenseProductReport', ExpenseProductReportController::class);
    Route::get('expenseCategoryReport', ExpenseCategoryReportController::class);
    Route::get('patientPaymentReport', PatientPaymentReportController::class);
    Route::get('serviceReport', ServiceReportController::class);
    // money account  
    Route::apiResource('/moneyAccount', MoneyAccountController::class);
    Route::get('/showPayments', [PeopleAccountTransactionController::class,'showPayments']);
    Route::apiResource('/moneyTransfer', MoneyTransferController::class);
    Route::apiResource('/peopleAccount', PeopleAccountController::class);
    Route::apiResource('/salary', SalaryController::class);
  
    Route::post('/generatePaySlip', [PeopleAccountTransactionController::class, 'generatePaySlip']);
    Route::apiResource('/peopleAccountTransaction', PeopleAccountTransactionController::class);

    Route::post('/paySalary', [PeopleAccountTransactionController::class, 'paySalary']);
    Route::post('/payCureCycle', [PeopleAccountTransactionController::class, 'payCureCycle']);
    Route::post('/outBoundLabPayment', [PeopleAccountTransactionController::class, 'outBoundLabPayment']);
    Route::post('/inBoundLabPayment', [PeopleAccountTransactionController::class, 'inBoundLabPayment']);
    Route::post('/billExpensePayment', [PeopleAccountTransactionController::class, 'billExpensePayment']);
    Route::get('/reset-password/{token}', function ($token) {
        return view('auth.reset-password', ['token' => $token]);
    })->name('password.reset');

    
    
});


Route::get('/', function () {
    return view('welcome');
});

Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:login');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:login');

