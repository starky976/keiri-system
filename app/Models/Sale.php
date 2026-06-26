<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 売上モデル
 *
 * 取引先への売上伝票を管理する。
 * 1件の売上に対して複数の明細（SaleItem）を持つ。
 * status: pending（未請求）→ invoiced（請求済）→ paid（入金済）
 * 税計算: subtotal × tax_rate / 100 を切り捨て → tax_amount
 */
class Sale extends Model
{
    use HasFactory, SoftDeletes;

    /** 一括代入可能なカラム */
    protected $fillable = [
        'sale_number', 'client_id', 'user_id', 'sale_date',
        'description', 'subtotal', 'tax_amount', 'total_amount',
        'tax_rate', 'status', 'notes',
    ];

    /** キャスト定義 */
    protected $casts = [
        'sale_date'    => 'date',
        'subtotal'     => 'integer',
        'tax_amount'   => 'integer',
        'total_amount' => 'integer',
    ];

    // =========================================================
    // リレーション
    // =========================================================

    /** この売上の取引先 */
    public function client() { return $this->belongsTo(Client::class); }

    /** この売上を登録したユーザー */
    public function user() { return $this->belongsTo(User::class); }

    /** この売上の明細一覧（品目・単価・数量） */
    public function items() { return $this->hasMany(SaleItem::class); }
}
