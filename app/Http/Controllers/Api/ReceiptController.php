<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 入金コントローラー
 *
 * 取引先からの入金記録と請求書消込を処理する。
 * invoice_id を指定した場合、請求書の paid_amount を加算する。
 * paid_amount >= total_amount になった時点で請求書を 'paid' に自動更新する。
 * 番号採番: R + YYYYMMDD + 当日連番3桁
 */
class ReceiptController extends Controller
{
    use \App\Http\Controllers\Api\Concerns\EscapesLikeQuery;

    /**
     * 入金一覧
     *
     * @param  Request  $request  search?, from?, to?
     * @return JsonResponse  200: PaginationResponse
     */
    public function index(Request $request): JsonResponse
    {
        $q = Receipt::with('client:id,name', 'invoice:id,invoice_number', 'user:id,name');

        // キーワード検索: receipt_number または取引先名
        if ($request->filled('search')) {
            $q->where(fn($q) => $q
                ->where('receipt_number', 'like', '%' . $this->escapeLike($request->search) . '%')
                ->orWhereHas('client', fn($q) => $q->where('name', 'like', '%' . $this->escapeLike($request->search) . '%'))
            );
        }

        // 期間フィルタ
        if ($request->filled('from')) $q->where('receipt_date', '>=', $request->from);
        if ($request->filled('to'))   $q->where('receipt_date', '<=', $request->to);

        return response()->json($q->orderByDesc('receipt_date')->paginate(20));
    }

    /**
     * 入金登録
     *
     * invoice_id が指定された場合、請求書消込処理を実行する。
     * 全体を DB::transaction() で包み、整合性を保証する。
     *
     * @param  Request  $request
     * @return JsonResponse  201: Receipt+client+invoice
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id'      => 'required|exists:clients,id',
            'invoice_id'     => 'nullable|exists:invoices,id',
            'receipt_date'   => 'required|date',
            'amount'         => 'required|integer|min:1',
            'method'         => 'required|in:bank_transfer,cash,check,credit_card,other',
            'bank_name'      => 'nullable|string',
            'account_number' => 'nullable|string',
            'notes'          => 'nullable|string',
        ]);

        $receipt = DB::transaction(function () use ($data) {
            // 入金番号採番: R + YYYYMMDD + 当日連番
            $prefix = 'R' . Carbon::now()->format('Ymd');
            $r = Receipt::create(array_merge($data, [
                'receipt_number' => $prefix . str_pad(Receipt::where('receipt_number', 'like', $prefix . '%')->count() + 1, 3, '0', STR_PAD_LEFT),
                'user_id'        => auth()->id(),
            ]));

            // 請求書消込処理（invoice_id が指定された場合のみ）
            if ($r->invoice_id) {
                $inv = Invoice::find($r->invoice_id);
                // paid_amount に入金額を加算
                $inv->increment('paid_amount', $r->amount);
                // 全額入金済みなら status を 'paid' に更新
                if ($inv->paid_amount >= $inv->total_amount) {
                    $inv->update(['status' => 'paid']);
                }
            }
            return $r;
        });

        return response()->json($receipt->load('client', 'invoice'), 201);
    }

    /**
     * 入金詳細
     *
     * @param  Receipt  $receipt  Route Model Binding
     * @return JsonResponse  200: Receipt+client+invoice+user
     */
    public function show(Receipt $receipt): JsonResponse
    {
        return response()->json($receipt->load('client', 'invoice', 'user'));
    }

    /**
     * 入金更新
     *
     * @param  Request  $request
     * @param  Receipt  $receipt  Route Model Binding
     * @return JsonResponse  200: Receipt
     */
    public function update(Request $request, Receipt $receipt): JsonResponse
    {
        $data = $request->validate([
            'client_id'    => 'required|exists:clients,id',
            'invoice_id'   => 'nullable|exists:invoices,id',
            'receipt_date' => 'required|date',
            'amount'       => 'required|integer|min:1',
            'method'       => 'required|in:bank_transfer,cash,check,credit_card,other',
            'notes'        => 'nullable|string',
        ]);

        $receipt->update($data);
        return response()->json($receipt);
    }

    /**
     * 入金削除
     *
     * @param  Receipt  $receipt  Route Model Binding
     * @return JsonResponse  200: {message}
     */
    public function destroy(Receipt $receipt): JsonResponse
    {
        $receipt->delete();
        return response()->json(['message' => '削除しました。']);
    }
}
