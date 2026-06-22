<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\AccountItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class ProfitLossController extends Controller {
    public function index(Request $request): JsonResponse {
        $from=$request->get('from',Carbon::now()->startOfYear()->format('Y-m-d'));
        $to=$request->get('to',Carbon::now()->format('Y-m-d'));
        $items=AccountItem::whereIn('category',['revenue','expense'])->where('is_active',true)->with(['journalEntries'=>fn($q)=>$q->whereHas('journal',fn($j)=>$j->whereBetween('journal_date',[$from,$to]))])->orderBy('sort_order')->get();
        $mapItems=fn($cat,$isDebitNormal)=>$items->where('category',$cat)->map(fn($item)=>['code'=>$item->code,'name'=>$item->name,'amount'=>$isDebitNormal?$item->journalEntries->where('side','debit')->sum('amount')-$item->journalEntries->where('side','credit')->sum('amount'):$item->journalEntries->where('side','credit')->sum('amount')-$item->journalEntries->where('side','debit')->sum('amount')])->values();
        $revenue=$mapItems('revenue',false);$expense=$mapItems('expense',true);
        $tr=$revenue->sum('amount');$te=$expense->sum('amount');
        return response()->json(['revenue'=>$revenue,'expense'=>$expense,'totalRevenue'=>$tr,'totalExpense'=>$te,'netIncome'=>$tr-$te,'filters'=>compact('from','to')]);
    }
}
