<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 売上コントローラー
 *
 * 売上伝票と明細の CRUD を提供する。
 * 登録・更新は DB::transaction() で売上レコードと全明細を原子的に保存する。
 * 税計算: subtotal × tax_rate / 100 を floor() で切り捨て。
 * 番号採番: S + YYYYMMDD + 当日連番3桁（例: S20260601001）
 */
class SaleController extends Controller
{
    /**
     * 売上一覧（ページネーション）
     *
     * @param  Request  $request  search?, status?, from?, to?
     * @return JsonResponse  200: PaginationResponse
     */
    public function index(Request $request): JsonResponse
    {
        $q = Sale::with('client:id,name', 'user:id,name');

        // キーワード検索: sale_number・description・取引先名に部分一致
        if ($request->filled('search')) {
            $q->where(fn($q) => $q
                ->where('sale_number', 'like', "%{$request->search}%")
                ->orWhere('description', 'like', "%{$request->search}%")
                ->orWhereHas('client', fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            );
        }

        // ステータスフィルタ
        if ($request->filled('status')) $q->where('status', $request->status);
        // 期間フィルタ
        if ($request->filled('from')) $q->where('sale_date', '>=', $request->from);
        if ($request->filled('to'))   $q->where('sale_date', '<=', $request->to);

        return response()->json($q->orderByDesc('sale_date')->paginate(20));
    }

    /**
     * 売上登録
     *
     * DB::transaction() で売上レコードと全明細を原子的に保存する。
     *
     * @param  Request  $request
     * @return JsonResponse  201: Sale+items+client
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id'          => 'required|exists:clients,id',
            'sale_date'          => 'required|date',
            'description'        => 'required|string|max:200',
            'tax_rate'           => 'required|in:0,8,10',
            'notes'              => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.item_name'  => 'required|string',
            'items.*.unit_price' => 'required|integer|min:0',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.unit'       => 'nullable|string',
        ]);

        $sale = DB::transaction(function () use ($data) {
            // 明細から小計を計算
            $subtotal = collect($data['items'])->sum(fn($i) => $i['unit_price'] * $i['quantity']);
            // 消費税額（切り捨て）
            $taxAmount = (int) floor($subtotal * (int)$data['tax_rate'] / 100);

            // 売上伝票を作成（番号: S + 日付 + 当日連番）
            $sale = Sale::create([
                'sale_number'  => 'S' . Carbon::now()->format('Ymd') . str_pad(Sale::whereDate('created_at', today())->count() + 1, 3, '0', STR_PAD_LEFT),
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

            // 明細行を一括登録
            foreach ($data['items'] as $i => $item) {
                $sale->items()->create([
                    'item_name'  => $item['item_name'],
                    'unit_price' => $item['unit_price'],
                    'quantity'   => $item['quantity'],
                    'unit'       => $item['unit'] ?? '式',
                    'amount'     => (int)($item['unit_price'] * $item['quantity']),
                    'sort_order' => $i,
                ]);
            }
            return $sale;
        });

        return response()->json($sale->load('items', 'client'), 201);
    }

    /**
     * 売上詳細
     *
     * @param  Sale  $sale  Route Model Binding
     * @return JsonResponse  200: Sale+client+user+items
     */
    public function show(Sale $sale): JsonResponse
    {
        return response()->json($sale->load('client', 'user', 'items'));
    }

    /**
     * 売上更新
     *
     * 既存明細を全削除して再挿入する（全置換方式）。
     *
     * @param  Request  $request
     * @param  Sale     $sale  Route Model Binding
     * @return JsonResponse  200: Sale+items+client
     */
    public function update(Request $request, Sale $sale): JsonResponse
    {
        $data = $request->validate([
            'client_id'          => 'required|exists:clients,id',
            'sale_date'          => 'required|date',
            'description'        => 'required|string|max:200',
            'tax_rate'           => 'required|in:0,8,10',
            'notes'              => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.item_name'  => 'required|string',
            'items.*.unit_price' => 'required|integer|min:0',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.unit'       => 'nullable|string',
        ]);

        DB::transaction(function () use ($data, $sale) {
            // 合計再計算
            $subtotal  = collect($data['items'])->sum(fn($i) => $i['unit_price'] * $i['quantity']);
            $taxAmount = (int) floor($subtotal * (int)$data['tax_rate'] / 100);

            $sale->update([
                'client_id'    => $data['client_id'],
                'sale_date'    => $data['sale_date'],
                'description'  => $data['description'],
                'subtotal'     => $subtotal,
                'tax_amount'   => $taxAmount,
                'total_amount' => $subtotal + $taxAmount,
                'tax_rate'     => $data['tax_rate'],
                'notes'        => $data['notes'] ?? null,
            ]);

            // 明細を全削除して再挿入（全置換）
            $sale->items()->delete();
            foreach ($data['items'] as $i => $item) {
                $sale->items()->create([
                    'item_name'  => $item['item_name'],
                    'unit_price' => $item['unit_price'],
                    'quantity'   => $item['quantity'],
                    'unit'       => $item['unit'] ?? '式',
                    'amount'     => (int)($item['unit_price'] * $item['quantity']),
                    'sort_order' => $i,
                ]);
            }
        });

        return response()->json($sale->load('items', 'client'));
    }

    /**
     * 売上削除（ソフトデリート）
     *
     * @param  Sale  $sale  Route Model Binding
     * @return JsonResponse  200: {message}
     */
    public function destroy(Sale $sale): JsonResponse
    {
        $sale->delete();
        return response()->json(['message' => '削除しました。']);
    }
}
