<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 取引先モデル
 *
 * 顧客・仕入先・両方として管理される取引先情報を扱う。
 * SoftDeletes により削除されても関連データの外部キー参照が維持される。
 * type: customer（得意先）/ vendor（仕入先）/ both（両方）
 */
class Client extends Model
{
    use HasFactory, SoftDeletes;

    /** 一括代入可能なカラム */
    protected $fillable = [
        'code', 'name', 'name_kana', 'type',
        'postal_code', 'address', 'phone', 'email',
        'contact_person', 'payment_terms', 'is_active', 'notes',
    ];

    /** キャスト定義 */
    protected $casts = [
        'is_active'     => 'boolean',
        'payment_terms' => 'integer',
    ];

    // =========================================================
    // リレーション
    // =========================================================

    /** この取引先の売上一覧 */
    public function sales() { return $this->hasMany(Sale::class); }

    /** この取引先の請求書一覧 */
    public function invoices() { return $this->hasMany(Invoice::class); }

    /** この取引先の入金一覧 */
    public function receipts() { return $this->hasMany(Receipt::class); }

    /** この取引先への支払一覧 */
    public function payments() { return $this->hasMany(Payment::class); }

    // =========================================================
    // スコープ
    // =========================================================

    /**
     * 有効な取引先のみに絞り込むクエリスコープ
     */
    public function scopeActive($q) { return $q->where('is_active', true); }
}
