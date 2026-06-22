<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Sale extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['sale_number','client_id','user_id','sale_date','description','subtotal','tax_amount','total_amount','tax_rate','status','notes'];
    protected $casts = ['sale_date'=>'date','subtotal'=>'integer','tax_amount'=>'integer','total_amount'=>'integer'];
    public function client() { return $this->belongsTo(Client::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(SaleItem::class); }
}
