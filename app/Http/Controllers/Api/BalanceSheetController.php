<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 貸借対照表（B/S）コントローラー
 *
 * 指定日時点の全勘定科目残高を集計し、
 * 資産・負債・純資産の区分で貸借対照表形式にまとめて返す。
 *
 * 貸借均衡チェック: 資産合計 === 負債合計 + 純資産合計
 */
class BalanceSheetController extends Controller
{
    /**
     * 貸借対照表の生成
     *
     * クエリパラメータ:
     *  - as_of (required): 集計基準日 (YYYY-MM-DD)。この日以前の仕訳を全集計。
     *
     * @param  Request  $request
     * @return JsonResponse  200: {as_of, assets[], liabilities[], equity[], totals, is_balanced}
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'as_of' => 'required|date',
        ]);

        $asOf = $request->as_of; // 基準日

        /**
         * 指定カテゴリの勘定科目残高を集計するクロージャ
         *
         * @param  string  $category  'asset' | 'liability' | 'equity'
         * @return array  [{id, code, name, balance}] ← 残高ゼロを除いた一覧
         */
        $getBalances = function (string $category) use ($asOf): array {
            return AccountItem::where('category', $category)
                ->with(['journalEntries' => fn($q) => $q
                    ->whereHas('journal', fn($jq) => $jq->where('journal_date', '<=', $asOf))
                ])
                ->get()
                ->map(fn($item) => [
                    'id'      => $item->id,
                    'code'    => $item->code,
                    'name'    => $item->name,
                    // 借方-貸方 が残高（資産はプラス、負債・純資産はプラスが貸方超過）
                    'balance' => $item->journalEntries->where('side', 'debit')->sum('amount')
                               - $item->journalEntries->where('side', 'credit')->sum('amount'),
                ])
                ->filter(fn($row) => $row['balance'] != 0) // 残高ゼロ除外
                ->values()
                ->toArray();
        };

        $assets      = $getBalances('asset');      // 資産科目
        $liabilities = $getBalances('liability');  // 負債科目
        $equity      = $getBalances('equity');     // 純資産科目

        // 各区分の合計
        $assetTotal     = collect($assets)->sum('balance');
        $liabilityTotal = collect($liabilities)->sum('balance');
        $equityTotal    = collect($equity)->sum('balance');

        // 貸借均衡チェック（資産 = 負債 + 純資産）
        $isBalanced = $assetTotal === ($liabilityTotal + $equityTotal);

        return response()->json([
            'as_of'       => $asOf,
            'assets'      => $assets,
            'liabilities' => $liabilities,
            'equity'      => $equity,
            'totals'      => [
                'assets'      => $assetTotal,
                'liabilities' => $liabilityTotal,
                'equity'      => $equityTotal,
            ],
            'is_balanced' => $isBalanced, // true: 貸借一致, false: 不一致（異常）
        ]);
    }
}
