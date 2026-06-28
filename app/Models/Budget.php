<?php
/**
 * Budget モデル
 * 年次・月次予算を管理する。勘定科目・部門ごとに予算額を設定し、
 * 実績（仕訳明細）と比較することで予実管理を実現する。
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    protected $fillable = ['fiscal_year', 'month', 'account_item_id', 'department_id', 'amount', 'note'];

    protected $casts = ['amount' => 'decimal:2', 'fiscal_year' => 'integer', 'month' => 'integer'];

    public function accountItem(): BelongsTo { return $this->belongsTo(AccountItem::class); }
    public function department(): BelongsTo  { return $this->belongsTo(Department::class); }
}
