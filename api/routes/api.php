<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CategoryExpenseController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\NotificationController; use App\Http\Controllers\Api\ManagementController; use App\Http\Controllers\Api\UserController;
use App\Models\BusinessNotification;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::get('dashboard', DashboardController::class);

        Route::apiResource('clients', ClientController::class);
        Route::apiResource('branches', BranchController::class);
        Route::apiResource('users', UserController::class)->only(['index','store','update','destroy']);
        Route::get('roles', [UserController::class, 'roles']);
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('expense-categories', CategoryExpenseController::class)->parameters(['expense-categories' => 'expenseCategory']);
        Route::apiResource('expenses', ExpenseController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('sales', SaleController::class)->only(['index', 'show', 'store', 'destroy']);
        Route::apiResource('invoices', InvoiceController::class);
        Route::apiResource('payments', PaymentController::class)->only(['index', 'show', 'store', 'destroy']);

        Route::get('clients/{client}/statement', [ClientController::class, 'statement']);
        Route::get('reports/receivables', [ReportController::class, 'receivables']);
        Route::get('reports/overview', [ReportController::class, 'overview']);
        Route::get('reports/sales', [ReportController::class, 'sales']);
        Route::get('reports/expenses', [ReportController::class, 'expenses']);
        Route::get('reports/inventory', [ReportController::class, 'inventory']);
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'read']);
        Route::get('management/audit', [ManagementController::class, 'audit']);
        Route::get('management/followups', [ManagementController::class, 'followups']);
        Route::post('management/followups', [ManagementController::class, 'createFollowup']);
        Route::patch('management/followups/{followup}/complete', [ManagementController::class, 'completeFollowup']);
        Route::get('management/export/{type}', [ManagementController::class, 'export'])->whereIn('type',['sales','invoices','payments','expenses']);
    });
});
