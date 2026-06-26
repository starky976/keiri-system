<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 売上明細モデル
 *
 * 売上伝票（Sale）に紐づく品目行を管理する。
 * amount = unit_price × quantity で事前計算して保存する。
 * timestamps は不要なため無効化している。
 */
class SaleItem extends Model
{
    /** タイムスタンプ不使用 */
    public $timestamps = false;

    /** 一括代入可能なカラム */
    protected $fillable = [
        'sale_id', 'item_name', 'unit_price',
        'quantity', 'unit', 'amount', 'sort_order',
    ];

    // =========================================================
    // リレーション
    // =========================================================

    /** この明細が属する売上伝票 */
    public function sale() { return $this->belongsTo(Sale::class); }
}
