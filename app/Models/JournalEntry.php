<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class JournalEntry extends Model {
    public $timestamps = false;
    protected $fillable = ['journal_id','side','account_item_id','amount','description','sort_order'];
    protected $casts = ['amount'=>'integer'];
    public function journal() { return $this->belongsTo(Journal::class); }
    public function accountItem() { return $this->belongsTo(AccountItem::class); }
}
