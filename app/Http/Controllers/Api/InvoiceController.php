<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class InvoiceController extends Controller {
    public function index(Request $request): JsonResponse {
        $q = Invoice::with('client:id,name','user:id,name');
        if ($request->filled('search')) $q->where(fn($q)=>$q->where('invoice_number','like',"%{$request->search}%")->orWhereHas('client',fn($q)=>$q->where('name','like',"%{$request->search}%")));
        if ($request->filled('status')) $q->where('status',$request->status);
        return response()->json($q->orderByDesc('invoice_date')->paginate(20));
    }
    public function store(Request $request): JsonResponse {
        $data = $request->validate(['client_id'=>'required|exists:clients,id','invoice_date'=>'required|date','due_date'=>'required|date','notes'=>'nullable|string','items'=>'required|array|min:1','items.*.item_name'=>'required|string','items.*.unit_price'=>'required|integer|min:0','items.*.quantity'=>'required|numeric|min:0.01','items.*.unit'=>'nullable|string','items.*.tax_rate'=>'required|in:0,8,10']);
        $inv = DB::transaction(function () use ($data) {
            $subtotal = collect($data['items'])->sum(fn($i)=>$i['unit_price']*$i['quantity']);
            $tax = (int)floor(collect($data['items'])->sum(fn($i)=>$i['unit_price']*$i['quantity']*(int)$i['tax_rate']/100));
            $prefix='INV'.Carbon::now()->format('Ym');
            $inv = Invoice::create(['invoice_number'=>$prefix.str_pad(Invoice::where('invoice_number','like',$prefix.'%')->count()+1,4,'0',STR_PAD_LEFT),'client_id'=>$data['client_id'],'user_id'=>auth()->id(),'invoice_date'=>$data['invoice_date'],'due_date'=>$data['due_date'],'subtotal'=>$subtotal,'tax_amount'=>$tax,'total_amount'=>$subtotal+$tax,'notes'=>$data['notes']??null]);
            foreach ($data['items'] as $i=>$item) $inv->items()->create(['item_name'=>$item['item_name'],'unit_price'=>$item['unit_price'],'quantity'=>$item['quantity'],'unit'=>$item['unit']??'式','amount'=>(int)($item['unit_price']*$item['quantity']),'tax_rate'=>$item['tax_rate'],'sort_order'=>$i]);
            return $inv;
        });
        return response()->json($inv->load('items','client'),201);
    }
    public function show(Invoice $invoice): JsonResponse { return response()->json($invoice->load('client','user','items','receipts')); }
    public function update(Request $request, Invoice $invoice): JsonResponse {
        $data = $request->validate(['client_id'=>'required|exists:clients,id','invoice_date'=>'required|date','due_date'=>'required|date','notes'=>'nullable|string','items'=>'required|array|min:1','items.*.item_name'=>'required|string','items.*.unit_price'=>'required|integer|min:0','items.*.quantity'=>'required|numeric|min:0.01','items.*.unit'=>'nullable|string','items.*.tax_rate'=>'required|in:0,8,10']);
        DB::transaction(function () use ($data,$invoice) {
            $subtotal=collect($data['items'])->sum(fn($i)=>$i['unit_price']*$i['quantity']);
            $tax=(int)floor(collect($data['items'])->sum(fn($i)=>$i['unit_price']*$i['quantity']*(int)$i['tax_rate']/100));
            $invoice->update(['client_id'=>$data['client_id'],'invoice_date'=>$data['invoice_date'],'due_date'=>$data['due_date'],'subtotal'=>$subtotal,'tax_amount'=>$tax,'total_amount'=>$subtotal+$tax,'notes'=>$data['notes']??null]);
            $invoice->items()->delete();
            foreach ($data['items'] as $i=>$item) $invoice->items()->create(['item_name'=>$item['item_name'],'unit_price'=>$item['unit_price'],'quantity'=>$item['quantity'],'unit'=>$item['unit']??'式','amount'=>(int)($item['unit_price']*$item['quantity']),'tax_rate'=>$item['tax_rate'],'sort_order'=>$i]);
        });
        return response()->json($invoice->load('items','client'));
    }
    public function destroy(Invoice $invoice): JsonResponse { $invoice->delete(); return response()->json(['message'=>'削除しました。']); }
    public function send(Invoice $invoice): JsonResponse { $invoice->update(['status'=>'sent','sent_at'=>now()]); return response()->json($invoice); }
}
