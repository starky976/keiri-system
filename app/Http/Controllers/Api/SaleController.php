<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Sale::with('client:id,name', 'user:id,name');
        if ($request->filled('search')) {
            $q->where(fn($q) => $q->where('sale_number','like',"%{$request->search}%")->orWhere('description','like',"%{$request->search}%")->orWhereHas('client', fn($q) => $q->where('name','like',"%{$request->search}%")));
        }
        if ($request->filled('status')) $q->where('status', $request->status);
        if ($request->filled('from')) $q->where('sale_date', '>=', $request->from);
        if ($request->filled('to')) $q->where('sale_date', '<=', $request->to);
        return response()->json($q->orderByDesc('sale_date')->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id'   => 'required|exists:clients,id',
            'sale_date'   => 'required|date',
            'description' => 'required|string|max:200',
            'tax_rate'    => 'required|in:0,8,10',
            'notes'       => 'nullable|string',
            'items'       => 'required|array|min:1',
            'items.*.item_name'  => 'required|string',
            'items.*.unit_price' => 'required|integer|min:0',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.unit'       => 'nullable|string',
        ]);

        $sale = DB::transaction(function () use ($data) {
            $subtotal = collect($data['items'])->sum(fn($i) => $i['unit_price'] * $i['quantity']);
            $taxAmount = (int) floor($subtotal * (int)$data['tax_rate'] / 100);
            $sale = Sale::create([
                'sale_number'  => 'S'.Carbon::now()->format('Ymd').str_pad(Sale::whereDate('created_at', today())->count()+1, 3, '0', STR_PAD_LEFT),
                'client_id'    => $data['client_id'],
                'user_id'      => auth()->id(),
                'sale_date'    => $data['sale_date'],
                'description'  => $data['description'],
                'subtotal'     => $subtotal,
                'tax_amount'   => $taxAmount,
                'total_amount' => $subtotal + $taxAmount,
                'tax_rate'     => $data['tax_rate'],
                'notes'        => $data['notes'] ?? null,
            ]);
            foreach ($data['items'] as $i => $item) {
                $sale->items()->create(['item_name'=>$item['item_name'],'unit_price'=>$item['unit_price'],'quantity'=>$item['quantity'],'unit'=>$item['unit']??'式','amount'=>(int)($item['unit_price']*$item['quantity']),'sort_order'=>$i]);
            }
            return $sale;
        });

        return response()->json($sale->load('items', 'client'), 201);
    }

    public function show(Sale $sale): JsonResponse
    {
        return response()->json($sale->load('client', 'user', 'items'));
    }

    public function update(Request $request, Sale $sale): JsonResponse
    {
        $data = $request->validate([
            'client_id'   => 'required|exists:clients,id',
            'sale_date'   => 'required|date',
            'description' => 'required|string|max:200',
            'tax_rate'    => 'required|in:0,8,10',
            'notes'       => 'nullable|string',
            'items'       => 'required|array|min:1',
            'items.*.item_name'  => 'required|string',
            'items.*.unit_price' => 'required|integer|min:0',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.unit'       => 'nullable|string',
        ]);

        DB::transaction(function () use ($data, $sale) {
            $subtotal = collect($data['items'])->sum(fn($i) => $i['unit_price'] * $i['quantity']);
            $taxAmount = (int) floor($subtotal * (int)$data['tax_rate'] / 100);
            $sale->update(['client_id'=>$data['client_id'],'sale_date'=>$data['sale_date'],'description'=>$data['description'],'subtotal'=>$subtotal,'tax_amount'=>$taxAmount,'total_amount'=>$subtotal+$taxAmount,'tax_rate'=>$data['tax_rate'],'notes'=>$data['notes']??null]);
            $sale->items()->delete();
            foreach ($data['items'] as $i => $item) {
                $sale->items()->create(['item_name'=>$item['item_name'],'unit_price'=>$item['unit_price'],'quantity'=>$item['quantity'],'unit'=>$item['unit']??'式','amount'=>(int)($item['unit_price']*$item['quantity']),'sort_order'=>$i]);
            }
        });

        return response()->json($sale->load('items', 'client'));
    }

    public function destroy(Sale $sale): JsonResponse
    {
        $sale->delete();
        return response()->json(['message' => '削除しました。']);
    }
}
