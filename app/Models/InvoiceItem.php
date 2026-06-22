<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InvoiceItem extends Model {
    public $timestamps = false;
    protected $fillable = ['invoice_id','item_name','unit_price','quantity','unit','amount','tax_rate','sort_order'];
    public function invoice() { return $this->belongsTo(Invoice::class); }
}
