<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Expense extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['expense_number','user_id','approved_by','expense_date','applied_date','title','total_amount','status','approved_at','rejection_reason','notes'];
    protected $casts = ['expense_date'=>'date','applied_date'=>'date','approved_at'=>'datetime','total_amount'=>'integer'];
    public function user() { return $this->belongsTo(User::class); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function items() { return $this->hasMany(ExpenseItem::class); }
}
