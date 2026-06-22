<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ReceiptController extends Controller {
    public function index(Request $request): JsonResponse {
        $q = Receipt::with('client:id,name','invoice:id,invoice_number','user:id,name');
        if ($request->filled('search')) $q->where(fn($q)=>$q->where('receipt_number','like',"%{$request->search}%")->orWhereHas('client',fn($q)=>$q->where('name','like',"%{$request->search}%")));
        if ($request->filled('from')) $q->where('receipt_date','>=',$request->from);
        if ($request->filled('to')) $q->where('receipt_date','<=',$request->to);
        return response()->json($q->orderByDesc('receipt_date')->paginate(20));
    }
    public function store(Request $request): JsonResponse {
        $data = $request->validate(['client_id'=>'required|exists:clients,id','invoice_id'=>'nullable|exists:invoices,id','receipt_date'=>'required|date','amount'=>'required|integer|min:1','method'=>'required|in:bank_transfer,cash,check,credit_card,other','bank_name'=>'nullable|string','account_number'=>'nullable|string','notes'=>'nullable|string']);
        $receipt = DB::transaction(function () use ($data) {
            $prefix='R'.Carbon::now()->format('Ymd');
            $r = Receipt::create(array_merge($data,['receipt_number'=>$prefix.str_pad(Receipt::where('receipt_number','like',$prefix.'%')->count()+1,3,'0',STR_PAD_LEFT),'user_id'=>auth()->id()]));
            if ($r->invoice_id) { $inv=Invoice::find($r->invoice_id); $inv->increment('paid_amount',$r->amount); if($inv->paid_amount>=$inv->total_amount)$inv->update(['status'=>'paid']); }
            return $r;
        });
        return response()->json($receipt->load('client','invoice'),201);
    }
    public function show(Receipt $receipt): JsonResponse { return response()->json($receipt->load('client','invoice','user')); }
    public function update(Request $request, Receipt $receipt): JsonResponse {
        $data=$request->validate(['client_id'=>'required|exists:clients,id','invoice_id'=>'nullable|exists:invoices,id','receipt_date'=>'required|date','amount'=>'required|integer|min:1','method'=>'required|in:bank_transfer,cash,check,credit_card,other','notes'=>'nullable|string']);
        $receipt->update($data); return response()->json($receipt);
    }
    public function destroy(Receipt $receipt): JsonResponse { $receipt->delete(); return response()->json(['message'=>'削除しました。']); }
}
