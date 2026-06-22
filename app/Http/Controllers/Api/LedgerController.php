<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\AccountItem;
use App\Models\JournalEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class LedgerController extends Controller {
    public function index(): JsonResponse {
        $items=AccountItem::where('is_active',true)->withSum(['journalEntries as debit_total'=>fn($q)=>$q->where('side','debit')],'amount')->withSum(['journalEntries as credit_total'=>fn($q)=>$q->where('side','credit')],'amount')->orderBy('sort_order')->orderBy('code')->get()->map(function($item){
            $d=(int)($item->debit_total??0);$c=(int)($item->credit_total??0);
            $balance=in_array($item->category,['asset','expense'])?$d-$c:$c-$d;
            return['id'=>$item->id,'code'=>$item->code,'name'=>$item->name,'category'=>$item->category,'categoryText'=>$item->category_text,'debitTotal'=>$d,'creditTotal'=>$c,'balance'=>$balance];
        });
        return response()->json($items);
    }
    public function show(Request $request,string $code): JsonResponse {
        $account=AccountItem::where('code',$code)->firstOrFail();
        $q=JournalEntry::with('journal')->where('account_item_id',$account->id);
        if($request->filled('from'))$q->whereHas('journal',fn($q)=>$q->where('journal_date','>=',$request->from));
        if($request->filled('to'))$q->whereHas('journal',fn($q)=>$q->where('journal_date','<=',$request->to));
        $entries=$q->orderBy('journal_id')->get();
        $isDebitNormal=in_array($account->category,['asset','expense']);
        $running=0;
        $result=$entries->map(function($e) use (&$running,$isDebitNormal){
            if($isDebitNormal)$running+=$e->side==='debit'?$e->amount:-$e->amount;
            else $running+=$e->side==='credit'?$e->amount:-$e->amount;
            return['id'=>$e->id,'journalDate'=>$e->journal->journal_date,'journalNumber'=>$e->journal->journal_number,'description'=>$e->journal->description,'side'=>$e->side,'amount'=>$e->amount,'balance'=>$running];
        });
        return response()->json(['account'=>$account,'entries'=>$result]);
    }
}
