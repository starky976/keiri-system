<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 支払コントローラー
 *
 * 仕入先への支払予定・実績の CRUD を提供する。
 * 支払ごとに勘定科目（買掛金・支払手数料等）を紐づける。
 * 番号採番: P + YYYYMMDD + 当日連番3桁
 */
class PaymentController extends Controller
{
    use \App\Http\Controllers\Api\Concerns\EscapesLikeQuery;

    /**
     * 支払一覧
     *
     * @param  Request  $request  search?, status?
     * @return JsonResponse  200: PaginationResponse
     */
    public function index(Request $request): JsonResponse
    {
        $q = Payment::with('client:id,name', 'accountItem:id,name', 'user:id,name');

        // キーワード検索: payment_number・description・取引先名
        if ($request->filled('search')) {
            $q->where(fn($q) => $q
                ->where('payment_number', 'like', '%' . $this->escapeLike($request->search) . '%')
                ->orWhere('description', 'like', '%' . $this->escapeLike($request->search) . '%')
                ->orWhereHas('client', fn($q) => $q->where('name', 'like', '%' . $this->escapeLike($request->search) . '%'))
            );
        }

        if ($request->filled('status')) $q->where('status', $request->status);

        return response()->json($q->orderByDesc('due_date')->paginate(20));
    }

    /**
     * 支払登録
     *
     * @param  Request  $request
     * @return JsonResponse  201: Payment+client+accountItem
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id'       => 'required|exists:clients,id',
            'due_date'        => 'required|date',
            'payment_date'    => 'nullable|date',
            'amount'          => 'required|integer|min:1',
            'method'          => 'required|in:bank_transfer,cash,check,credit_card,other',
            'description'     => 'required|string|max:200',
            'account_item_id' => 'required|exists:account_items,id',
            'notes'           => 'nullable|string',
        ]);

        // 支払番号採番: P + YYYYMMDD + 当日連番
        $prefix = 'P' . Carbon::now()->format('Ymd');
        $p = Payment::create(array_merge($data, [
            'payment_number' => $prefix . str_pad(Payment::where('payment_number', 'like', $prefix . '%')->count() + 1, 3, '0', STR_PAD_LEFT),
            'user_id'        => auth()->id(),
            'status'         => $data['status'] ?? 'pending',
        ]));

        return response()->json($p->load('client', 'accountItem'), 201);
    }

    /**
     * 支払詳細
     *
     * @param  Payment  $payment  Route Model Binding
     * @return JsonResponse  200: Payment+client+user+accountItem
     */
    public function show(Payment $payment): JsonResponse
    {
        return response()->json($payment->load('client', 'user', 'accountItem'));
    }

    /**
     * 支払更新
     *
     * @param  Request  $request
     * @param  Payment  $payment  Route Model Binding
     * @return JsonResponse  200: Payment
     */
    public function update(Request $request, Payment $payment): JsonResponse
    {
        $data = $request->validate([
            'client_id'       => 'required|exists:clients,id',
            'due_date'        => 'required|date',
            'payment_date'    => 'nullable|date',
            'amount'          => 'required|integer|min:1',
            'method'          => 'required|in:bank_transfer,cash,check,credit_card,other',
            'description'     => 'required|string|max:200',
            'account_item_id' => 'required|exists:account_items,id',
            'status'          => 'in:pending,approved,paid,cancelled',
            'notes'           => 'nullable|string',
        ]);

        $payment->update($data);
        return response()->json($payment);
    }

    /**
     * 支払削除
     *
     * @param  Payment  $payment  Route Model Binding
     * @return JsonResponse  200: {message}
     */
    public function destroy(Payment $payment): JsonResponse
    {
        $payment->delete();
        return response()->json(['message' => '削除しました。']);
    }
}
