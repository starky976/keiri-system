<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model {
    use HasFactory;
    protected $fillable = ['payment_number','client_id','user_id','due_date','payment_date','amount','method','description','status','account_item_id','notes'];
    protected $casts = ['due_date'=>'date','payment_date'=>'date','amount'=>'integer'];
    public function client() { return $this->belongsTo(Client::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function accountItem() { return $this->belongsTo(AccountItem::class); }
}
