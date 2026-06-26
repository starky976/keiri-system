<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 仕訳帳モデル
 *
 * 複式簿記の仕訳伝票を管理する。
 * 1件の仕訳に借方・貸方の明細（JournalEntry）を複数持つ。
 * 借方合計 = 貸方合計（貸借バランス）が必須条件。
 * source_type: manual（手動）/ sale / invoice / receipt / payment / expense（自動仕訳用）
 */
class Journal extends Model
{
    use HasFactory;

    /** 一括代入可能なカラム */
    protected $fillable = [
        'journal_number', 'journal_date', 'description',
        'source_type', 'source_id', 'user_id',
    ];

    /** キャスト定義 */
    protected $casts = [
        'journal_date' => 'date',
    ];

    // =========================================================
    // リレーション
    // =========================================================

    /**
     * この仕訳の明細行一覧（借方・貸方）
     * sort_order で表示順を管理する
     */
    public function entries() { return $this->hasMany(JournalEntry::class); }

    /** この仕訳を入力したユーザー */
    public function user() { return $this->belongsTo(User::class); }
}
