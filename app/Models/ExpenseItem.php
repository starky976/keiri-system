<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ExpenseItem extends Model {
    public $timestamps = false;
    protected $fillable = ['expense_id','account_item_id','item_date','description','amount','tax_rate','receipt_path','sort_order'];
    protected $casts = ['item_date'=>'date','amount'=>'integer'];
    public function expense() { return $this->belongsTo(Expense::class); }
    public function accountItem() { return $this->belongsTo(AccountItem::class); }
}
