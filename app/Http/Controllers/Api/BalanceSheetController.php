<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\AccountItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class BalanceSheetController extends Controller {
    public function index(Request $request): JsonResponse {
        $asOf=$request->get('as_of',Carbon::now()->format('Y-m-d'));
        $items=AccountItem::whereIn('category',['asset','liability','equity'])->where('is_active',true)->with(['journalEntries'=>fn($q)=>$q->whereHas('journal',fn($j)=>$j->where('journal_date','<=',$asOf))])->orderBy('sort_order')->get();
        $build=fn($cat,$isDebitNormal)=>$items->where('category',$cat)->map(fn($item)=>['code'=>$item->code,'name'=>$item->name,'amount'=>$isDebitNormal?$item->journalEntries->where('side','debit')->sum('amount')-$item->journalEntries->where('side','credit')->sum('amount'):$item->journalEntries->where('side','credit')->sum('amount')-$item->journalEntries->where('side','debit')->sum('amount')])->values();
        $assets=$build('asset',true);$liabilities=$build('liability',false);$equity=$build('equity',false);
        return response()->json(['assets'=>$assets,'liabilities'=>$liabilities,'equity'=>$equity,'totalAssets'=>$assets->sum('amount'),'totalLiabilities'=>$liabilities->sum('amount'),'totalEquity'=>$equity->sum('amount'),'filters'=>['asOf'=>$asOf]]);
    }
}
