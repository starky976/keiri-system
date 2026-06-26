<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 入金モデル
 *
 * 取引先からの入金情報を管理する。
 * invoice_id を指定すると請求書消込処理が実行される。
 * 消込: invoice.paid_amount += amount → paid_amount >= total_amount で invoice.status = 'paid'
 * method: bank_transfer / cash / check / credit_card / other
 */
class Receipt extends Model
{
    use HasFactory;

    /** 一括代入可能なカラム */
    protected $fillable = [
        'receipt_number', 'client_id', 'invoice_id', 'user_id',
        'receipt_date', 'amount', 'method', 'bank_name', 'account_number', 'notes',
    ];

    /** キャスト定義 */
    protected $casts = [
        'receipt_date' => 'date',
        'amount'       => 'integer',
    ];

    // =========================================================
    // リレーション
    // =========================================================

    /** この入金の取引先 */
    public function client() { return $this->belongsTo(Client::class); }

    /** この入金が消込対象とする請求書（任意） */
    public function invoice() { return $this->belongsTo(Invoice::class); }

    /** この入金を登録したユーザー */
    public function user() { return $this->belongsTo(User::class); }
}
