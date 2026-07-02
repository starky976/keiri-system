<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 勘定科目モデル
 *
 * 会計上の勘定科目（現金・売上高・買掛金など）を管理する基盤マスタ。
 * category によって資産・負債・純資産・収益・費用に分類される。
 * 仕訳明細・支払・経費明細などから外部キーで参照される。
 */
class AccountItem extends Model
{
    /** 一括代入可能なカラム */
    protected $fillable = ['code', 'name', 'category', 'sub_category', 'is_active', 'sort_order'];

    // =========================================================
    // モデルイベント
    // =========================================================

    /**
     * 勘定科目の保存・削除時にキャッシュを破棄する
     * account-items API は 60分キャッシュのため、変更を即時反映するために必要
     */
    protected static function booted(): void
    {
        $clearCache = fn() => cache()->forget('account_items');
        static::saved($clearCache);
        static::deleted($clearCache);
    }

    /** キャスト定義 */
    protected $casts = ['is_active' => 'boolean'];

    // =========================================================
    // リレーション
    // =========================================================

    /**
     * この勘定科目に紐づく仕訳明細の一覧を返す
     */
    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * この勘定科目に紐づく支払の一覧を返す
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * この勘定科目に紐づく経費明細の一覧を返す
     */
    public function expenseItems()
    {
        return $this->hasMany(ExpenseItem::class);
    }

    // =========================================================
    // アクセサ
    // =========================================================

    /**
     * category 値を日本語表示名に変換するアクセサ
     * 例: 'asset' → '資産', 'revenue' → '収益'
     */
    public function getCategoryTextAttribute(): string
    {
        return [
            'asset'     => '資産',
            'liability' => '負債',
            'equity'    => '純資産',
            'revenue'   => '収益',
            'expense'   => '費用',
        ][$this->category] ?? $this->category;
    }
}
