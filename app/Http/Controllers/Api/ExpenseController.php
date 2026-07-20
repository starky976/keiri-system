<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 経費コントローラー
 *
 * 従業員が申請する経費（交通費・接待費・消耗品費など）の CRUD と
 * 承認ワークフロー（申請 → 承認/却下）を提供する。
 *
 * ステータス遷移:
 *  draft → pending → approved / rejected
 *  approved → draft （差し戻し相当、再申請のため）
 *
 * 経費ヘッダー (expenses) + 経費明細 (expense_items) を
 * DB::transaction() で一括保存する。
 */
class ExpenseController extends Controller
{
    use \App\Http\Controllers\Api\Concerns\EscapesLikeQuery;

    /**
     * 経費一覧
     *
     * @param  Request  $request  search?, status?
     * @return JsonResponse  200: PaginationResponse
     */
    public function index(Request $request): JsonResponse
    {
        $q = Expense::with('user:id,name', 'approver:id,name');

        // キーワード検索: タイトル
        if ($request->filled('search')) {
            $q->where('title', 'like', '%' . $this->escapeLike($request->search) . '%');
        }

        if ($request->filled('status')) $q->where('status', $request->status);

        return response()->json($q->orderByDesc('expense_date')->paginate(20));
    }

    /**
     * 経費申請の登録
     *
     * 明細は expense_items に一括作成する。
     * status はデフォルト 'draft'。
     *
     * @param  Request  $request
     * @return JsonResponse  201: Expense+items
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'                         => 'required|string|max:200',
            'expense_date'                  => 'required|date',
            'notes'                         => 'nullable|string',
            'items'                         => 'required|array|min:1',
            'items.*.account_item_id'       => 'required|exists:account_items,id',
            'items.*.item_date'             => 'required|date',
            'items.*.description'           => 'required|string',
            'items.*.amount'                => 'required|integer|min:1',
            'items.*.tax_rate'              => 'nullable|in:0,8,10',
            'items.*.receipt_url'           => 'nullable|url',
        ]);

        $expense = DB::transaction(function () use ($data) {
            // 経費番号採番: EXP + YYYYMM + 当月連番4桁
            $prefix = 'EXP' . Carbon::now()->format('Ym');
            $e = Expense::create([
                'expense_number' => $prefix . str_pad(Expense::where('expense_number', 'like', $prefix . '%')->count() + 1, 4, '0', STR_PAD_LEFT),
                'title'          => $data['title'],
                'expense_date'   => $data['expense_date'],
                'applied_date'   => Carbon::today()->toDateString(), // 申請日は自動で今日
                'notes'          => $data['notes'] ?? null,
                'user_id'        => auth()->id(),
                'status'         => 'draft', // 初期ステータスは「下書き」
                'total_amount'   => collect($data['items'])->sum('amount'), // 合計を自動計算
            ]);

            // 経費明細を一括作成
            foreach ($data['items'] as $item) {
                $e->items()->create([
                    'account_item_id' => $item['account_item_id'],
                    'item_date'       => $item['item_date'],
                    'description'     => $item['description'],
                    'amount'          => $item['amount'],
                    'tax_rate'        => $item['tax_rate'] ?? '10',
                ]);
            }

            return $e;
        });

        return response()->json($expense->load('items.accountItem'), 201);
    }

    /**
     * 経費詳細
     *
     * @param  Expense  $expense  Route Model Binding
     * @return JsonResponse  200: Expense+items+user+approver
     */
    public function show(Expense $expense): JsonResponse
    {
        return response()->json($expense->load('items.accountItem', 'user', 'approver'));
    }

    /**
     * 経費申請の更新
     *
     * draft 状態のみ更新可能。submitted 以降は承認ワークフローを使う。
     *
     * @param  Request  $request
     * @param  Expense  $expense  Route Model Binding
     * @return JsonResponse  200: Expense+items
     */
    public function update(Request $request, Expense $expense): JsonResponse
    {
        // draft 以外は更新不可
        if ($expense->status !== 'draft') {
            return response()->json(['message' => '申請済みの経費は編集できません。'], 422);
        }

        $data = $request->validate([
            'title'                   => 'required|string|max:200',
            'expense_date'            => 'required|date',
            'notes'                   => 'nullable|string',
            'items'                   => 'required|array|min:1',
            'items.*.account_item_id' => 'required|exists:account_items,id',
            'items.*.description'     => 'required|string',
            'items.*.amount'          => 'required|integer|min:1',
            'items.*.receipt_url'     => 'nullable|url',
        ]);

        DB::transaction(function () use ($data, $expense) {
            $expense->update([
                'title'        => $data['title'],
                'expense_date' => $data['expense_date'],
                'notes'        => $data['notes'] ?? null,
                'total_amount' => collect($data['items'])->sum('amount'),
            ]);

            // 明細をリプレース
            $expense->items()->delete();
            foreach ($data['items'] as $item) {
                $expense->items()->create($item);
            }
        });

        return response()->json($expense->fresh()->load('items.accountItem'));
    }

    /**
     * 経費申請の削除（draft のみ）
     *
     * @param  Expense  $expense  Route Model Binding
     * @return JsonResponse  200: {message}
     */
    public function destroy(Expense $expense): JsonResponse
    {
        if ($expense->status !== 'draft') {
            return response()->json(['message' => '申請済みの経費は削除できません。'], 422);
        }

        $expense->items()->delete();
        $expense->delete();
        return response()->json(['message' => '削除しました。']);
    }

    /**
     * 経費申請の承認
     *
     * pending 状態の申請を approved に移行する。
     *
     * @param  Expense  $expense  Route Model Binding
     * @return JsonResponse  200: Expense
     */
    public function approve(Expense $expense): JsonResponse
    {
        if ($expense->status !== 'pending') {
            return response()->json(['message' => '申請中の経費のみ承認できます。'], 422);
        }

        $expense->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),      // 承認者を記録
            'approved_at' => now(),             // 承認日時を記録
        ]);

        return response()->json($expense->fresh());
    }

    /**
     * 経費申請の却下
     *
     * pending 状態の申請を rejected に移行する。
     * 却下理由（reason）を必須で記録する。
     *
     * @param  Request  $request
     * @param  Expense  $expense  Route Model Binding
     * @return JsonResponse  200: Expense
     */
    public function reject(Request $request, Expense $expense): JsonResponse
    {
        if ($expense->status !== 'pending') {
            return response()->json(['message' => '申請中の経費のみ却下できます。'], 422);
        }

        $data = $request->validate([
            'reason' => 'required|string|max:500', // 却下理由は必須
        ]);

        $expense->update([
            'status'           => 'rejected',
            'approved_by'      => auth()->id(),
            'rejection_reason' => $data['reason'],
        ]);

        return response()->json($expense->fresh());
    }

    /**
     * 経費申請を提出（draft → submitted）
     *
     * @param  Expense  $expense  Route Model Binding
     * @return JsonResponse  200: Expense
     */
    public function submit(Expense $expense): JsonResponse
    {
        if ($expense->status !== 'draft') {
            return response()->json(['message' => '下書きの経費のみ申請できます。'], 422);
        }

        $expense->update(['status' => 'submitted']);
        return response()->json($expense->fresh());
    }
}
