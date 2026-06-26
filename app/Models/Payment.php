<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 支払モデル
 *
 * 仕入先への支払予定・実績を管理する。
 * account_item_id で費用勘定科目（買掛金など）と紐づける。
 * status: pending（未承認）→ approved（承認済）→ paid（支払済）/ cancelled（取消）
 */
class Payment extends Model
{
    use HasFactory;

    /** 一括代入可能なカラム */
    protected $fillable = [
        'payment_number', 'client_id', 'user_id',
        'due_date', 'payment_date', 'amount', 'method',
        'description', 'status', 'account_item_id', 'notes',
    ];

    /** キャスト定義 */
    protected $casts = [
        'due_date'     => 'date',
        'payment_date' => 'date',
        'amount'       => 'integer',
    ];

    // =========================================================
    // リレーション
    // =========================================================

    /** この支払の支払先取引先 */
    public function client() { return $this->belongsTo(Client::class); }

    /** この支払を登録したユーザー */
    public function user() { return $this->belongsTo(User::class); }

    /** この支払に紐づく勘定科目 */
    public function accountItem() { return $this->belongsTo(AccountItem::class); }
}
