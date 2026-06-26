<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountItem;
use App\Models\JournalEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 損益計算書（P/L）コントローラー
 *
 * 指定期間の収益・費用を勘定科目ごとに集計し、損益を計算する。
 * 売上高（revenue）- 費用（expense）= 当期純損益
 */
class ProfitLossController extends Controller
{
    /**
     * 損益計算書の生成
     *
     * クエリパラメータ:
     *  - from (required): 集計開始日 (YYYY-MM-DD)
     *  - to   (required): 集計終了日 (YYYY-MM-DD)
     *
     * @param  Request  $request
     * @return JsonResponse  200: {period, revenue[], expenses[], revenue_total, expense_total, net_income}
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $from = $request->from;
        $to   = $request->to;

        /**
         * 勘定科目カテゴリで収益/費用を取得するクロージャ
         *
         * @param  string  $category  'revenue' or 'expense'
         * @return array  [{id, code, name, debit, credit, net}] 形式
         */
        $getByCategory = function (string $category) use ($from, $to): array {
            return AccountItem::where('category', $category)
                ->with(['journalEntries' => fn($q) => $q
                    ->whereHas('journal', fn($jq) => $jq->whereBetween('journal_date', [$from, $to]))
                ])
                ->get()
                ->map(fn($item) => [
                    'id'     => $item->id,
                    'code'   => $item->code,
                    'name'   => $item->name,
                    'debit'  => $item->journalEntries->where('side', 'debit')->sum('amount'),
                    'credit' => $item->journalEntries->where('side', 'credit')->sum('amount'),
                    // 収益: 貸方-借方, 費用: 借方-貸方
                    'net'    => $category === 'revenue'
                        ? $item->journalEntries->where('side', 'credit')->sum('amount') - $item->journalEntries->where('side', 'debit')->sum('amount')
                        : $item->journalEntries->where('side', 'debit')->sum('amount') - $item->journalEntries->where('side', 'credit')->sum('amount'),
                ])
                ->filter(fn($row) => $row['debit'] > 0 || $row['credit'] > 0) // 残高ゼロの科目を除外
                ->values()
                ->toArray();
        };

        $revenues = $getByCategory('revenue'); // 収益科目一覧
        $expenses = $getByCategory('expense'); // 費用科目一覧

        $revenueTotal = collect($revenues)->sum('net'); // 収益合計
        $expenseTotal = collect($expenses)->sum('net'); // 費用合計
        $netIncome    = $revenueTotal - $expenseTotal;  // 当期純損益

        return response()->json([
            'period'        => ['from' => $from, 'to' => $to],
            'revenues'      => $revenues,
            'expenses'      => $expenses,
            'revenue_total' => $revenueTotal,
            'expense_total' => $expenseTotal,
            'net_income'    => $netIncome,   // 正: 利益, 負: 損失
        ]);
    }
}
