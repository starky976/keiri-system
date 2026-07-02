<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 検索・ソート・フィルター用インデックス追加マイグレーション
 *
 * 各コントローラーの WHERE / ORDER BY / whereBetween で使われている
 * カラムにインデックスを追加し、クエリパフォーマンスを改善する。
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 売上テーブル ──────────────────────────────────────
        Schema::table('sales', function (Blueprint $table) {
            $table->index('sale_date');          // 期間フィルター・ダッシュボード月別集計
            $table->index('status');             // ステータスフィルター
            $table->index('client_id');          // 取引先別絞り込み（外部キーはIndexなしの場合あり）
        });

        // ── 請求書テーブル ────────────────────────────────────
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('invoice_date');       // 期間フィルター
            $table->index('due_date');           // 支払期限ソート・ダッシュボード
            $table->index('status');             // ステータスフィルター（overdue / sent など）
            $table->index('client_id');
        });

        // ── 入金テーブル ──────────────────────────────────────
        Schema::table('receipts', function (Blueprint $table) {
            $table->index('receipt_date');       // 期間フィルター・ダッシュボード月別集計
            $table->index('client_id');
        });

        // ── 支払テーブル ──────────────────────────────────────
        Schema::table('payments', function (Blueprint $table) {
            $table->index('payment_date');       // 期間フィルター
            $table->index('due_date');           // 期限ソート
            $table->index('status');
        });

        // ── 仕訳テーブル ──────────────────────────────────────
        Schema::table('journals', function (Blueprint $table) {
            $table->index('journal_date');       // 期間フィルター・部門別損益クエリ
            $table->index('department_id');      // 部門別集計
        });

        // ── 経費テーブル ──────────────────────────────────────
        Schema::table('expenses', function (Blueprint $table) {
            $table->index('applied_date');       // 期間フィルター・ダッシュボード
            $table->index('status');             // 承認待ちフィルター
            $table->index('user_id');
        });

        // ── 取引先テーブル ────────────────────────────────────
        Schema::table('clients', function (Blueprint $table) {
            $table->index('is_active');          // 有効/無効フィルター
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['sale_date']);
            $table->dropIndex(['status']);
            $table->dropIndex(['client_id']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['invoice_date']);
            $table->dropIndex(['due_date']);
            $table->dropIndex(['status']);
            $table->dropIndex(['client_id']);
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->dropIndex(['receipt_date']);
            $table->dropIndex(['client_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payment_date']);
            $table->dropIndex(['due_date']);
            $table->dropIndex(['status']);
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->dropIndex(['journal_date']);
            $table->dropIndex(['department_id']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['applied_date']);
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });
    }
};
