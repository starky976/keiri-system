<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ExpenseController extends Controller {
    public function index(Request $request): JsonResponse {
        $q=Expense::with('user:id,name','approver:id,name');
        if($request->filled('status'))$q->where('status',$request->status);
        if($request->filled('search'))$q->where('title','like',"%{$request->search}%");
        return response()->json($q->orderByDesc('applied_date')->paginate(20));
    }
    public function store(Request $request): JsonResponse {
        $data=$request->validate(['expense_date'=>'required|date','title'=>'required|string|max:200','notes'=>'nullable|string','items'=>'required|array|min:1','items.*.account_item_id'=>'required|exists:account_items,id','items.*.item_date'=>'required|date','items.*.description'=>'required|string','items.*.amount'=>'required|integer|min:1','items.*.tax_rate'=>'required|in:0,8,10']);
        $exp=DB::transaction(function () use ($data){
            $total=collect($data['items'])->sum('amount');
            $prefix='EXP'.Carbon::now()->format('Ym');
            $e=Expense::create(['expense_number'=>$prefix.str_pad(Expense::where('expense_number','like',$prefix.'%')->count()+1,4,'0',STR_PAD_LEFT),'user_id'=>auth()->id(),'expense_date'=>$data['expense_date'],'applied_date'=>today(),'title'=>$data['title'],'total_amount'=>$total,'status'=>'pending','notes'=>$data['notes']??null]);
            foreach($data['items'] as $i=>$item)$e->items()->create(array_merge($item,['sort_order'=>$i]));
            return $e;
        });
        return response()->json($exp->load('items.accountItem'),201);
    }
    public function show(Expense $expense): JsonResponse { return response()->json($expense->load('user','approver','items.accountItem')); }
    public function update(Request $request,Expense $expense): JsonResponse {
        $data=$request->validate(['expense_date'=>'required|date','title'=>'required|string','notes'=>'nullable|string','items'=>'required|array|min:1','items.*.account_item_id'=>'required|exists:account_items,id','items.*.item_date'=>'required|date','items.*.description'=>'required|string','items.*.amount'=>'required|integer|min:1','items.*.tax_rate'=>'required|in:0,8,10']);
        DB::transaction(function () use ($data,$expense){
            $expense->update(['expense_date'=>$data['expense_date'],'title'=>$data['title'],'total_amount'=>collect($data['items'])->sum('amount'),'notes'=>$data['notes']??null]);
            $expense->items()->delete();
            foreach($data['items'] as $i=>$item)$expense->items()->create(array_merge($item,['sort_order'=>$i]));
        });
        return response()->json($expense->load('items.accountItem'));
    }
    public function destroy(Expense $expense): JsonResponse { $expense->delete(); return response()->json(['message'=>'削除しました。']); }
    public function approve(Expense $expense): JsonResponse { $expense->update(['status'=>'approved','approved_by'=>auth()->id(),'approved_at'=>now()]); return response()->json($expense); }
    public function reject(Request $request,Expense $expense): JsonResponse {
        $request->validate(['reason'=>'required|string|max:500']);
        $expense->update(['status'=>'rejected','rejection_reason'=>$request->reason]);
        return response()->json($expense);
    }
}
