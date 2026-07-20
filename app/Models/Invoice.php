<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 請求書モデル
 *
 * 取引先への請求書を管理する。
 * 明細ごとに個別の税率（0/8/10%）を設定可能（軽減税率対応）。
 * 入金登録時に paid_amount が加算され、total_amount 以上で paid に自動移行する。
 * status: draft（下書き）→ sent（送付済）→ paid（入金済）/ overdue（期限超過）/ cancelled（取消）
 */
class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    /** 一括代入可能なカラム */
    protected $fillable = [
        'invoice_number', 'client_id', 'user_id', 'invoice_date', 'due_date',
        'subtotal', 'tax_amount', 'total_amount', 'paid_amount',
        'status', 'sent_at', 'notes',
    ];

    /** キャスト定義 */
    protected $casts = [
        'invoice_date' => 'date:Y-m-d',
        'due_date'     => 'date:Y-m-d',
        'sent_at'      => 'datetime',
        'subtotal'     => 'integer',
        'tax_amount'   => 'integer',
        'total_amount' => 'integer',
        'paid_amount'  => 'integer',
    ];

    // =========================================================
    // リレーション
    // =========================================================

    /** この請求書の取引先 */
    public function client() { return $this->belongsTo(Client::class); }

    /** この請求書を作成したユーザー */
    public function user() { return $this->belongsTo(User::class); }

    /** この請求書の明細一覧 */
    public function items() { return $this->hasMany(InvoiceItem::class); }

    /** この請求書に紐づく入金一覧 */
    public function receipts() { return $this->hasMany(Receipt::class); }

    // =========================================================
    // アクセサ
    // =========================================================

    /**
     * 未入金残高（total_amount - paid_amount）を返すアクセサ
     */
    public function getBalanceAttribute(): int
    {
        return $this->total_amount - $this->paid_amount;
    }
}
