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
// throttle:5,1 = 1分間に5回を超えるリクエストを 429 Too Many Requests で拒否（ブルートフォース対策）
Route::post('/api/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
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

    // 勘定科目マスタ（60分キャッシュ）
    // 勘定科目は変更頻度が低いため Cache::remember でDBアクセスを削減する
    // キャッシュキー 'account_items' は AccountItem 更新時に cache()->forget('account_items') で破棄すること
    Route::get('account-items', fn() => cache()->remember(
        'account_items',
        now()->addMinutes(60),
        fn() => \App\Models\AccountItem::where('is_active', true)->orderBy('code')->get()
    ));
});

// SPA フォールバック（全ての未マッチルートをSPAに渡す）
Route::get('/{any}', fn() => view('app'))->where('any', '.*');

// ── 追加5機能ルート（機能11〜15）────────────────────────────
Route::middleware('auth')->prefix('api')->group(function () {
    // 11. 予算管理
    Route::apiResource('budgets', \App\Http\Controllers\Api\BudgetController::class);
    Route::get('budgets-comparison', [\App\Http\Controllers\Api\BudgetController::class, 'comparison']);

    // 12. 固定資産管理
    Route::apiResource('fixed-assets', \App\Http\Controllers\Api\FixedAssetController::class);
    Route::get('fixed-assets/{fixedAsset}/depreciation', [\App\Http\Controllers\Api\FixedAssetController::class, 'depreciation']);

    // 13. 部門管理
    Route::apiResource('departments', \App\Http\Controllers\Api\DepartmentController::class);
    Route::get('department-report', [\App\Http\Controllers\Api\DepartmentController::class, 'report']);

    // 14. 消費税管理
    Route::get('tax',         [\App\Http\Controllers\Api\TaxController::class, 'index']);
    Route::get('tax-summary', [\App\Http\Controllers\Api\TaxController::class, 'summary']);

    // 15. 帳票出力
    Route::get('documents/invoice/{invoice}', [\App\Http\Controllers\Api\DocumentController::class, 'invoice']);
    Route::get('documents/receipt/{receipt}', [\App\Http\Controllers\Api\DocumentController::class, 'receipt']);
    Route::get('documents/payment/{payment}', [\App\Http\Controllers\Api\DocumentController::class, 'payment']);
});
