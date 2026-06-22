<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Client extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['code','name','name_kana','type','postal_code','address','phone','email','contact_person','payment_terms','is_active','notes'];
    protected $casts = ['is_active' => 'boolean', 'payment_terms' => 'integer'];
    public function sales() { return $this->hasMany(Sale::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
    public function receipts() { return $this->hasMany(Receipt::class); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function scopeActive($q) { return $q->where('is_active', true); }
}
