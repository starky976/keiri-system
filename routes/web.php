<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\LedgerController;
use App\Http\Controllers\Api\ProfitLossController;
use App\Http\Controllers\Api\BalanceSheetController;
use App\Http\Controllers\Api\ExpenseController;

// SPA エントリーポイント（認証不要ルートも含め全てここで受け取る）
Route::get('/login', fn() => view('app'))->name('login');

// 認証API
Route::post('/api/login', [AuthController::class, 'login']);
Route::post('/api/logout', [AuthController::class, 'logout'])->middleware('auth');
Route::get('/api/user', [AuthController::class, 'user'])->middleware('auth');

// 認証済みAPIルート
Route::middleware('auth')->prefix('api')->group(function () {
    // ダッシュボード
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // 取引先管理
    Route::apiResource('clients', ClientController::class);

    // 売上管理
    Route::apiResource('sales', SaleController::class);

    // 請求書管理
    Route::apiResource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send']);

    // 入金管理
    Route::apiResource('receipts', ReceiptController::class);

    // 支払管理
    Route::apiResource('payments', PaymentController::class);

    // 仕訳入力
    Route::apiResource('journals', JournalController::class);

    // 総勘定元帳
    Route::get('ledger', [LedgerController::class, 'index']);
    Route::get('ledger/{code}', [LedgerController::class, 'show']);

    // 損益計算書
    Route::get('profit-loss', [ProfitLossController::class, 'index']);

    // 貸借対照表
    Route::get('balance-sheet', [BalanceSheetController::class, 'index']);

    // 経費精算
    Route::apiResource('expenses', ExpenseController::class);
    Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve']);
    Route::post('expenses/{expense}/reject', [ExpenseController::class, 'reject']);

    // 勘定科目マスタ
    Route::get('account-items', fn() => \App\Models\AccountItem::where('is_active', true)->orderBy('code')->get());
});

// SPA フォールバック（全ての未マッチルートをSPAに渡す）
Route::get('/{any}', fn() => view('app'))->where('any', '.*');
