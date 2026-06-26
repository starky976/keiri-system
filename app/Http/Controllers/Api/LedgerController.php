<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountItem;
use App\Models\JournalEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 総勘定元帳コントローラー
 *
 * 指定した勘定科目・期間の仕訳明細を集計し、元帳形式で返す。
 * 期首残高 + 借方/貸方 の増減を順次計算して残高推移を返す。
 */
class LedgerController extends Controller
{
    /**
     * 総勘定元帳の取得
     *
     * クエリパラメータ:
     *  - account_item_id (required): 勘定科目 ID
     *  - from (required): 集計開始日 (YYYY-MM-DD)
     *  - to   (required): 集計終了日 (YYYY-MM-DD)
     *
     * @param  Request  $request
     * @return JsonResponse  200: {account_item, entries[], debit_total, credit_total, balance}
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'account_item_id' => 'required|exists:account_items,id',
            'from'            => 'required|date',
            'to'              => 'required|date|after_or_equal:from',
        ]);

        // 勘定科目情報
        $account = AccountItem::findOrFail($request->account_item_id);

        // 指定期間の仕訳明細を取得（仕訳日昇順）
        $entries = JournalEntry::with('journal:id,journal_number,journal_date,description')
            ->where('account_item_id', $request->account_item_id)
            ->whereHas('journal', fn($q) => $q
                ->whereBetween('journal_date', [$request->from, $request->to])
            )
            ->get()
            ->map(function ($entry) {
                return [
                    'journal_number' => $entry->journal->journal_number,
                    'journal_date'   => $entry->journal->journal_date,
                    'description'    => $entry->description ?: $entry->journal->description,
                    'side'           => $entry->side,
                    'amount'         => $entry->amount,
                ];
            })
            ->sortBy('journal_date')
            ->values();

        // 借方・貸方 の合計と残高を計算
        $debitTotal  = $entries->where('side', 'debit')->sum('amount');
        $creditTotal = $entries->where('side', 'credit')->sum('amount');

        return response()->json([
            'account_item'  => $account,          // 勘定科目マスタ情報
            'entries'       => $entries,           // 仕訳明細（時系列）
            'debit_total'   => $debitTotal,        // 借方合計
            'credit_total'  => $creditTotal,       // 貸方合計
            'balance'       => $debitTotal - $creditTotal, // 残高（借方-貸方）
        ]);
    }
}
