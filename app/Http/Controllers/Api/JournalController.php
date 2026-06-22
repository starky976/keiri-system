<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Journal;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class JournalController extends Controller {
    public function index(Request $request): JsonResponse {
        $q=Journal::with('user:id,name','entries.accountItem:id,name');
        if($request->filled('search'))$q->where(fn($q)=>$q->where('journal_number','like',"%{$request->search}%")->orWhere('description','like',"%{$request->search}%"));
        if($request->filled('from'))$q->where('journal_date','>=',$request->from);
        if($request->filled('to'))$q->where('journal_date','<=',$request->to);
        return response()->json($q->orderByDesc('journal_date')->paginate(20));
    }
    public function store(Request $request): JsonResponse {
        $data=$request->validate(['journal_date'=>'required|date','description'=>'required|string|max:200','entries'=>'required|array|min:2','entries.*.side'=>'required|in:debit,credit','entries.*.account_item_id'=>'required|exists:account_items,id','entries.*.amount'=>'required|integer|min:1','entries.*.description'=>'nullable|string']);
        $debit=collect($data['entries'])->where('side','debit')->sum('amount');
        $credit=collect($data['entries'])->where('side','credit')->sum('amount');
        if($debit!==$credit) throw ValidationException::withMessages(['entries'=>"借方({$debit})と貸方({$credit})が一致しません。"]);
        $j=DB::transaction(function () use ($data) {
            $prefix='J'.Carbon::now()->format('Ymd');
            $j=Journal::create(['journal_number'=>$prefix.str_pad(Journal::where('journal_number','like',$prefix.'%')->count()+1,4,'0',STR_PAD_LEFT),'journal_date'=>$data['journal_date'],'description'=>$data['description'],'source_type'=>'manual','user_id'=>auth()->id()]);
            foreach($data['entries'] as $i=>$e)$j->entries()->create(array_merge($e,['sort_order'=>$i]));
            return $j;
        });
        return response()->json($j->load('entries.accountItem'),201);
    }
    public function show(Journal $journal): JsonResponse { return response()->json($journal->load('user','entries.accountItem')); }
    public function update(Request $request,Journal $journal): JsonResponse {
        $data=$request->validate(['journal_date'=>'required|date','description'=>'required|string','entries'=>'required|array|min:2','entries.*.side'=>'required|in:debit,credit','entries.*.account_item_id'=>'required|exists:account_items,id','entries.*.amount'=>'required|integer|min:1','entries.*.description'=>'nullable|string']);
        $debit=collect($data['entries'])->where('side','debit')->sum('amount');
        $credit=collect($data['entries'])->where('side','credit')->sum('amount');
        if($debit!==$credit) throw ValidationException::withMessages(['entries'=>"借方({$debit})と貸方({$credit})が一致しません。"]);
        DB::transaction(function () use ($data,$journal) {
            $journal->update(['journal_date'=>$data['journal_date'],'description'=>$data['description']]);
            $journal->entries()->delete();
            foreach($data['entries'] as $i=>$e)$journal->entries()->create(array_merge($e,['sort_order'=>$i]));
        });
        return response()->json($journal->load('entries.accountItem'));
    }
    public function destroy(Journal $journal): JsonResponse { $journal->delete(); return response()->json(['message'=>'削除しました。']); }
}
