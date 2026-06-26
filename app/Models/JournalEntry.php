<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 仕訳明細モデル
 *
 * 仕訳伝票（Journal）の各行（借方・貸方）を管理する。
 * side: 'debit'（借方）/ 'credit'（貸方）
 * 総勘定元帳・損益計算書・貸借対照表はこのテーブルを集計して生成する。
 * timestamps は不要なため無効化している。
 */
class JournalEntry extends Model
{
    /** タイムスタンプ不使用 */
    public $timestamps = false;

    /** 一括代入可能なカラム */
    protected $fillable = [
        'journal_id', 'side', 'account_item_id',
        'amount', 'description', 'sort_order',
    ];

    /** キャスト定義 */
    protected $casts = [
        'amount' => 'integer',
    ];

    // =========================================================
    // リレーション
    // =========================================================

    /** この明細が属する仕訳伝票 */
    public function journal() { return $this->belongsTo(Journal::class); }

    /** この明細の勘定科目 */
    public function accountItem() { return $this->belongsTo(AccountItem::class); }
}
