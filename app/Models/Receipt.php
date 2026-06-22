<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Receipt extends Model {
    use HasFactory;
    protected $fillable = ['receipt_number','client_id','invoice_id','user_id','receipt_date','amount','method','bank_name','account_number','notes'];
    protected $casts = ['receipt_date'=>'date','amount'=>'integer'];
    public function client() { return $this->belongsTo(Client::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function user() { return $this->belongsTo(User::class); }
}
