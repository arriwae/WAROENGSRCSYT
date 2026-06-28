<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\OrderHistoryController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Protected routes (requires login)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Redirect root to dashboard
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Restock list (Kulakan)
    Route::get('/restock', [ProductController::class, 'restock'])->name('products.restock');
    
    // Product CRUD
    Route::resource('products', ProductController::class)->except(['create', 'show']);
    
    // Cashier (POS)
    Route::get('/cashier', [CashierController::class, 'index'])->name('cashier.index');
    Route::get('/cashier/search', [CashierController::class, 'search'])->name('cashier.search');
    Route::post('/cashier/checkout', [CashierController::class, 'checkout'])->name('cashier.checkout');
    
    // Order History & Revenue Reports
    Route::get('/history', [OrderHistoryController::class, 'index'])->name('history.index');
    
    // Financial Reports
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::get('/api/check-state', [FinanceController::class, 'checkState'])->name('api.check_state');
    
    // Debts (Utang)
    Route::get('/debts', [DebtController::class, 'index'])->name('debts.index');
    Route::get('/debts/{debt}', [DebtController::class, 'show'])->name('debts.show');
    Route::post('/debts/{debt}/payments', [DebtController::class, 'storePayment'])->name('debts.store_payment');

    // Vouchers management & validation
    Route::resource('/vouchers', VoucherController::class);
    Route::post('/api/vouchers/validate', [VoucherController::class, 'validateVoucher'])->name('api.vouchers.validate');
});
