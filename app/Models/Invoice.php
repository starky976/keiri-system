<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Invoice extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['invoice_number','client_id','user_id','invoice_date','due_date','subtotal','tax_amount','total_amount','paid_amount','status','sent_at','notes'];
    protected $casts = ['invoice_date'=>'date','due_date'=>'date','sent_at'=>'datetime','subtotal'=>'integer','tax_amount'=>'integer','total_amount'=>'integer','paid_amount'=>'integer'];
    public function client() { return $this->belongsTo(Client::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(InvoiceItem::class); }
    public function receipts() { return $this->hasMany(Receipt::class); }
    public function getBalanceAttribute() { return $this->total_amount - $this->paid_amount; }
}
