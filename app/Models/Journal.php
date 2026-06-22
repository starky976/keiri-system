<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Journal extends Model {
    use HasFactory;
    protected $fillable = ['journal_number','journal_date','description','source_type','source_id','user_id'];
    protected $casts = ['journal_date'=>'date'];
    public function entries() { return $this->hasMany(JournalEntry::class); }
    public function user() { return $this->belongsTo(User::class); }
}
