<?php
/**
 * BudgetController（予算管理）
 *
 * 年次・月次予算の CRUD と、仕訳明細との突合による予実比較を提供する。
 * 予実比較: 同一年度・月・勘定科目の budget.amount と journal_entries の合計を並べる。
 */
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\JournalEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    /** 予算一覧（年度・月でフィルタ可） */
    public function index(Request $request): JsonResponse
    {
        $q = Budget::with(['accountItem', 'department'])
            ->when($request->fiscal_year, fn($q, $v) => $q->where('fiscal_year', $v))
            ->when($request->month !== null, fn($q) => $q->where('month', $request->month ?: null))
            ->orderBy('fiscal_year')
            ->orderBy('month');

        return response()->json($q->paginate(50));
    }

    /** 予算登録 */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fiscal_year'     => 'required|integer|min:2000|max:2100',
            'month'           => 'nullable|integer|min:1|max:12',
            'account_item_id' => 'required|exists:account_items,id',
            'department_id'   => 'nullable|exists:departments,id',
            'amount'          => 'required|numeric|min:0',
            'note'            => 'nullable|string',
        ]);

        $budget = Budget::updateOrCreate(
            [
                'fiscal_year'     => $data['fiscal_year'],
                'month'           => $data['month'] ?? null,
                'account_item_id' => $data['account_item_id'],
                'department_id'   => $data['department_id'] ?? null,
            ],
            ['amount' => $data['amount'], 'note' => $data['note'] ?? null]
        );

        return response()->json($budget->load(['accountItem', 'department']), 201);
    }

    /** 予実比較（年度・月を指定） */
    public function comparison(Request $request): JsonResponse
    {
        $year  = $request->integer('fiscal_year', now()->year);
        $month = $request->integer('month', 0) ?: null;

        // 予算
        $budgets = Budget::with('accountItem')
            ->where('fiscal_year', $year)
            ->where('month', $month)
            ->get()
            ->keyBy('account_item_id');

        // 実績（仕訳明細の集計）
        $actuals = JournalEntry::join('journals', 'journals.id', '=', 'journal_entries.journal_id')
            ->when($month, fn($q) => $q->whereMonth('journals.journal_date', $month)
                                       ->whereYear('journals.journal_date', $year))
            ->when(!$month, fn($q) => $q->whereYear('journals.journal_date', $year))
            ->select('account_item_id',
                DB::raw('SUM(debit_amount) as debit_total'),
                DB::raw('SUM(credit_amount) as credit_total'))
            ->groupBy('account_item_id')
            ->get()
            ->keyBy('account_item_id');

        // 全勘定科目 ID を統合して比較表を構築
        $allIds = $budgets->keys()->merge($actuals->keys())->unique();

        $rows = $allIds->map(function ($id) use ($budgets, $actuals) {
            $b   = $budgets->get($id);
            $a   = $actuals->get($id);
            $bAmt = $b?->amount ?? 0;
            $aAmt = ($a?->debit_total ?? 0) - ($a?->credit_total ?? 0);
            return [
                'account_item_id'   => $id,
                'account_item_name' => $b?->accountItem->name ?? $a?->account_item_id,
                'budget_amount'     => $bAmt,
                'actual_amount'     => $aAmt,
                'variance'          => $aAmt - $bAmt,          // 差異（実績 - 予算）
                'achievement_rate'  => $bAmt > 0 ? round($aAmt / $bAmt * 100, 1) : null,
            ];
        })->values();

        return response()->json(['fiscal_year' => $year, 'month' => $month, 'rows' => $rows]);
    }

    public function update(Request $request, Budget $budget): JsonResponse
    {
        $budget->update($request->validate([
            'amount' => 'required|numeric|min:0',
            'note'   => 'nullable|string',
        ]));
        return response()->json($budget->load(['accountItem', 'department']));
    }

    public function destroy(Budget $budget): JsonResponse
    {
        $budget->delete();
        return response()->json(['message' => '削除しました。']);
    }
}
