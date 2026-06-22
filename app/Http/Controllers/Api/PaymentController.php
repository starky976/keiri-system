<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class PaymentController extends Controller {
    public function index(Request $request): JsonResponse {
        $q=Payment::with('client:id,name','accountItem:id,name','user:id,name');
        if($request->filled('search'))$q->where(fn($q)=>$q->where('payment_number','like',"%{$request->search}%")->orWhere('description','like',"%{$request->search}%")->orWhereHas('client',fn($q)=>$q->where('name','like',"%{$request->search}%")));
        if($request->filled('status'))$q->where('status',$request->status);
        return response()->json($q->orderByDesc('due_date')->paginate(20));
    }
    public function store(Request $request): JsonResponse {
        $data=$request->validate(['client_id'=>'required|exists:clients,id','due_date'=>'required|date','payment_date'=>'nullable|date','amount'=>'required|integer|min:1','method'=>'required|in:bank_transfer,cash,check,credit_card,other','description'=>'required|string|max:200','account_item_id'=>'required|exists:account_items,id','notes'=>'nullable|string']);
        $prefix='P'.Carbon::now()->format('Ymd');
        $p=Payment::create(array_merge($data,['payment_number'=>$prefix.str_pad(Payment::where('payment_number','like',$prefix.'%')->count()+1,3,'0',STR_PAD_LEFT),'user_id'=>auth()->id()]));
        return response()->json($p->load('client','accountItem'),201);
    }
    public function show(Payment $payment): JsonResponse { return response()->json($payment->load('client','user','accountItem')); }
    public function update(Request $request,Payment $payment): JsonResponse {
        $data=$request->validate(['client_id'=>'required|exists:clients,id','due_date'=>'required|date','payment_date'=>'nullable|date','amount'=>'required|integer|min:1','method'=>'required|in:bank_transfer,cash,check,credit_card,other','description'=>'required|string|max:200','account_item_id'=>'required|exists:account_items,id','status'=>'in:pending,approved,paid,cancelled','notes'=>'nullable|string']);
        $payment->update($data); return response()->json($payment);
    }
    public function destroy(Payment $payment): JsonResponse { $payment->delete(); return response()->json(['message'=>'削除しました。']); }
}
