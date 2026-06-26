<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 経費明細モデル
 *
 * 経費申請（Expense）の各明細行を管理する。
 * 勘定科目・費用日・内容・金額・税率を行ごとに保持する。
 * receipt_path は領収書ファイルパス（将来の添付機能用）。
 * timestamps は不要なため無効化している。
 */
class ExpenseItem extends Model
{
    /** タイムスタンプ不使用 */
    public $timestamps = false;

    /** 一括代入可能なカラム */
    protected $fillable = [
        'expense_id', 'account_item_id', 'item_date',
        'description', 'amount', 'tax_rate', 'receipt_path', 'sort_order',
    ];

    /** キャスト定義 */
    protected $casts = [
        'item_date' => 'date',
        'amount'    => 'integer',
    ];

    // =========================================================
    // リレーション
    // =========================================================

    /** この明細が属する経費申請 */
    public function expense() { return $this->belongsTo(Expense::class); }

    /** この明細の勘定科目（費用系） */
    public function accountItem() { return $this->belongsTo(AccountItem::class); }
}
