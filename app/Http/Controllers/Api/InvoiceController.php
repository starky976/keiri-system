<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 請求書コントローラー
 *
 * 請求書と明細の CRUD、および送付アクションを提供する。
 * 明細ごとに個別税率（0/8/10%）を設定可能（軽減税率対応）。
 * 番号採番: INV + YYYYMM + 当月連番4桁（例: INV2026060001）
 */
class InvoiceController extends Controller
{
    use \App\Http\Controllers\Api\Concerns\EscapesLikeQuery;

    /**
     * 請求書一覧
     *
     * @param  Request  $request  search?, status?
     * @return JsonResponse  200: PaginationResponse
     */
    public function index(Request $request): JsonResponse
    {
        $q = Invoice::with('client:id,name', 'user:id,name');

        // キーワード検索: invoice_number または取引先名
        if ($request->filled('search')) {
            $q->where(fn($q) => $q
                ->where('invoice_number', 'like', '%' . $this->escapeLike($request->search) . '%')
                ->orWhereHas('client', fn($q) => $q->where('name', 'like', '%' . $this->escapeLike($request->search) . '%'))
            );
        }

        if ($request->filled('status')) $q->where('status', $request->status);

        return response()->json($q->orderByDesc('invoice_date')->paginate(20));
    }

    /**
     * 請求書作成
     *
     * 明細ごとの税額を合算して invoice.tax_amount を計算する。
     *
     * @param  Request  $request
     * @return JsonResponse  201: Invoice+items+client
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id'            => 'required|exists:clients,id',
            'invoice_date'         => 'required|date',
            'due_date'             => 'required|date',
            'notes'                => 'nullable|string',
            'items'                => 'required|array|min:1',
            'items.*.item_name'    => 'required|string',
            'items.*.unit_price'   => 'required|integer|min:0',
            'items.*.quantity'     => 'required|numeric|min:0.01',
            'items.*.unit'         => 'nullable|string',
            'items.*.tax_rate'     => 'required|in:0,8,10',
        ]);

        $inv = DB::transaction(function () use ($data) {
            // 小計（税抜）= 全明細の単価×数量の合計
            $subtotal = collect($data['items'])->sum(fn($i) => $i['unit_price'] * $i['quantity']);
            // 税額 = 明細ごとの税額合計を切り捨て
            $tax = (int) floor(
                collect($data['items'])->sum(fn($i) => $i['unit_price'] * $i['quantity'] * (int)$i['tax_rate'] / 100)
            );

            // 請求書番号: INV + YYYYMM + 当月連番
            $prefix = 'INV' . Carbon::now()->format('Ym');
            $inv = Invoice::create([
                'invoice_number' => $prefix . str_pad(Invoice::where('invoice_number', 'like', $prefix . '%')->count() + 1, 4, '0', STR_PAD_LEFT),
                'client_id'      => $data['client_id'],
                'user_id'        => auth()->id(),
                'invoice_date'   => $data['invoice_date'],
                'due_date'       => $data['due_date'],
                'subtotal'       => $subtotal,
                'tax_amount'     => $tax,
                'total_amount'   => $subtotal + $tax,
                'notes'          => $data['notes'] ?? null,
            ]);

            // 明細行を一括登録（明細ごとに税率を保持）
            foreach ($data['items'] as $i => $item) {
                $inv->items()->create([
                    'item_name'  => $item['item_name'],
                    'unit_price' => $item['unit_price'],
                    'quantity'   => $item['quantity'],
                    'unit'       => $item['unit'] ?? '式',
                    'amount'     => (int)($item['unit_price'] * $item['quantity']),
                    'tax_rate'   => $item['tax_rate'],
                    'sort_order' => $i,
                ]);
            }
            return $inv;
        });

        return response()->json($inv->load('items', 'client'), 201);
    }

    /**
     * 請求書詳細
     * 入金履歴（receipts）も含めて返す
     *
     * @param  Invoice  $invoice  Route Model Binding
     * @return JsonResponse  200: Invoice+client+user+items+receipts
     */
    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json($invoice->load('client', 'user', 'items', 'receipts'));
    }

    /**
     * 請求書更新
     * 明細は全削除→再挿入（全置換）
     *
     * @param  Request  $request
     * @param  Invoice  $invoice  Route Model Binding
     * @return JsonResponse  200: Invoice+items+client
     */
    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $data = $request->validate([
            'client_id'          => 'required|exists:clients,id',
            'invoice_date'       => 'required|date',
            'due_date'           => 'required|date',
            'notes'              => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.item_name'  => 'required|string',
            'items.*.unit_price' => 'required|integer|min:0',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.unit'       => 'nullable|string',
            'items.*.tax_rate'   => 'required|in:0,8,10',
        ]);

        DB::transaction(function () use ($data, $invoice) {
            $subtotal = collect($data['items'])->sum(fn($i) => $i['unit_price'] * $i['quantity']);
            $tax = (int) floor(
                collect($data['items'])->sum(fn($i) => $i['unit_price'] * $i['quantity'] * (int)$i['tax_rate'] / 100)
            );

            $invoice->update([
                'client_id'    => $data['client_id'],
                'invoice_date' => $data['invoice_date'],
                'due_date'     => $data['due_date'],
                'subtotal'     => $subtotal,
                'tax_amount'   => $tax,
                'total_amount' => $subtotal + $tax,
                'notes'        => $data['notes'] ?? null,
            ]);

            // 明細を全削除して再挿入
            $invoice->items()->delete();
            foreach ($data['items'] as $i => $item) {
                $invoice->items()->create([
                    'item_name'  => $item['item_name'],
                    'unit_price' => $item['unit_price'],
                    'quantity'   => $item['quantity'],
                    'unit'       => $item['unit'] ?? '式',
                    'amount'     => (int)($item['unit_price'] * $item['quantity']),
                    'tax_rate'   => $item['tax_rate'],
                    'sort_order' => $i,
                ]);
            }
        });

        return response()->json($invoice->load('items', 'client'));
    }

    /**
     * 請求書削除（ソフトデリート）
     *
     * @param  Invoice  $invoice  Route Model Binding
     * @return JsonResponse  200: {message}
     */
    public function destroy(Invoice $invoice): JsonResponse
    {
        $invoice->delete();
        return response()->json(['message' => '削除しました。']);
    }

    /**
     * 請求書送付
     *
     * status を 'sent' に更新し、sent_at に現在日時を記録する。
     *
     * @param  Invoice  $invoice  Route Model Binding
     * @return JsonResponse  200: Invoice
     */
    public function send(Invoice $invoice): JsonResponse
    {
        $invoice->update(['status' => 'sent', 'sent_at' => now()]);
        return response()->json($invoice);
    }
}
