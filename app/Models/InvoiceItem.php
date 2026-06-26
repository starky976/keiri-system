<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 請求書明細モデル
 *
 * 請求書（Invoice）に紐づく品目行を管理する。
 * 売上明細と異なり、明細ごとに個別の tax_rate を保持する（軽減税率対応）。
 * timestamps は不要なため無効化している。
 */
class InvoiceItem extends Model
{
    /** タイムスタンプ不使用 */
    public $timestamps = false;

    /** 一括代入可能なカラム */
    protected $fillable = [
        'invoice_id', 'item_name', 'unit_price',
        'quantity', 'unit', 'amount', 'tax_rate', 'sort_order',
    ];

    // =========================================================
    // リレーション
    // =========================================================

    /** この明細が属する請求書 */
    public function invoice() { return $this->belongsTo(Invoice::class); }
}
