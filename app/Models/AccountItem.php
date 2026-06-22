<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AccountItem extends Model {
    protected $fillable = ['code','name','category','sub_category','is_active','sort_order'];
    protected $casts = ['is_active' => 'boolean'];
    public function journalEntries() { return $this->hasMany(JournalEntry::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function expenseItems() { return $this->hasMany(ExpenseItem::class); }
    public function getCategoryTextAttribute() {
        return ['asset'=>'資産','liability'=>'負債','equity'=>'純資産','revenue'=>'収益','expense'=>'費用'][$this->category] ?? $this->category;
    }
}
