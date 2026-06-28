<?php
/**
 * Department（部門）モデル
 * 経理データを部門別に分類するためのマスタ。
 * 仕訳（Journal）に department_id を紐付けることで部門別損益が実現できる。
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = ['code', 'name', 'description', 'is_active'];
    protected $casts    = ['is_active' => 'boolean'];

    public function journals(): HasMany { return $this->hasMany(Journal::class); }
}
