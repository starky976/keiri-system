<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 経費精算モデル
 *
 * 従業員の立替経費申請を管理する。
 * 申請者（user_id）が申請し、承認者（approved_by）が承認・却下する。
 * status: draft（下書き）→ pending（申請中）→ approved（承認済）/ rejected（却下）→ paid（支払済）
 * rejection_reason は却下時のみ設定される。
 */
class Expense extends Model
{
    use HasFactory, SoftDeletes;

    /** 一括代入可能なカラム */
    protected $fillable = [
        'expense_number', 'user_id', 'approved_by',
        'expense_date', 'applied_date', 'title', 'total_amount',
        'status', 'approved_at', 'rejection_reason', 'notes',
    ];

    /** キャスト定義 */
    protected $casts = [
        'expense_date' => 'date',
        'applied_date' => 'date',
        'approved_at'  => 'datetime',
        'total_amount' => 'integer',
    ];

    // =========================================================
    // リレーション
    // =========================================================

    /** 申請者ユーザー */
    public function user() { return $this->belongsTo(User::class); }

    /**
     * 承認者ユーザー（approved_by カラムで関連付け）
     * 未承認の場合は null
     */
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }

    /** この経費申請の明細一覧（勘定科目・日付・金額） */
    public function items() { return $this->hasMany(ExpenseItem::class); }
}
