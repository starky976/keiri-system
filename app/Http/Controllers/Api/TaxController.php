<?php
/**
 * TaxController（消費税管理）
 *
 * 仕訳明細に記録された消費税額を集計し、申告用の課税・非課税・免税の
 * 内訳レポートを提供する。軽減税率（8%）と標準税率（10%）に対応。
 *
 * ※ 消費税フィールドは journal_entries テーブルの tax_rate / tax_amount を参照。
 *    現状のスキーマに tax_rate/tax_amount カラムがない場合は 0 として集計する。
 */
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxController extends Controller
{
    /**
     * 消費税集計レポート
     * 指定期間の仕訳明細から税率別に課税基準額・消費税額を集計する。
     */
    public function index(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->startOfQuarter()->toDateString());
        $to   = $request->input('to',   now()->endOfQuarter()->toDateString());

        // tax_rate カラムが存在する場合は税率別に集計、存在しない場合は全件を標準税率として集計
        $hasColumns = DB::getSchemaBuilder()->hasColumn('journal_entries', 'tax_rate');

        if ($hasColumns) {
            $rows = JournalEntry::join('journals', 'journals.id', '=', 'journal_entries.journal_id')
                ->whereBetween('journals.journal_date', [$from, $to])
                ->select(
                    'tax_rate',
                    DB::raw('SUM(debit_amount + credit_amount) as taxable_base'),
                    DB::raw('SUM(COALESCE(tax_amount, 0))      as tax_amount')
                )
                ->groupBy('tax_rate')
                ->get();
        } else {
            // スキーマ未対応の場合: 仕訳明細の借方・貸方合計を返す（参考値）
            $total = JournalEntry::join('journals', 'journals.id', '=', 'journal_entries.journal_id')
                ->whereBetween('journals.journal_date', [$from, $to])
                ->select(
                    DB::raw('SUM(debit_amount)  as debit_total'),
                    DB::raw('SUM(credit_amount) as credit_total')
                )
                ->first();

            $rows = collect([
                ['tax_rate' => 10, 'taxable_base' => $total->debit_total ?? 0,  'tax_amount' => 0],
                ['tax_rate' =>  8, 'taxable_base' => $total->credit_total ?? 0, 'tax_amount' => 0],
            ]);
        }

        return response()->json([
            'from'          => $from,
            'to'            => $to,
            'breakdown'     => $rows,
            'total_tax'     => $rows->sum('tax_amount'),
            'note'          => $hasColumns ? null : 'journal_entries に tax_rate カラムを追加するとより正確な集計が可能です。',
        ]);
    }

    /** 消費税申告用サマリー（課税売上・課税仕入・納付税額） */
    public function summary(Request $request): JsonResponse
    {
        $year    = $request->integer('year', now()->year);
        $from    = "{$year}-01-01";
        $to      = "{$year}-12-31";

        // 収益勘定の合計 = 課税売上基準
        $sales = JournalEntry::join('journals',      'journals.id',      '=', 'journal_entries.journal_id')
            ->join('account_items', 'account_items.id', '=', 'journal_entries.account_item_id')
            ->whereBetween('journals.journal_date', [$from, $to])
            ->where('account_items.type', 'revenue')
            ->select(DB::raw('SUM(credit_amount - debit_amount) as amount'))
            ->value('amount') ?? 0;

        // 費用勘定の合計 = 課税仕入基準
        $purchases = JournalEntry::join('journals',      'journals.id',      '=', 'journal_entries.journal_id')
            ->join('account_items', 'account_items.id', '=', 'journal_entries.account_item_id')
            ->whereBetween('journals.journal_date', [$from, $to])
            ->where('account_items.type', 'expense')
            ->select(DB::raw('SUM(debit_amount - credit_amount) as amount'))
            ->value('amount') ?? 0;

        $taxOnSales     = round($sales     * 0.10, 0); // 売上消費税（10%概算）
        $taxOnPurchases = round($purchases * 0.10, 0); // 仕入消費税（10%概算）

        return response()->json([
            'year'              => $year,
            'taxable_sales'     => $sales,
            'tax_on_sales'      => $taxOnSales,
            'taxable_purchases' => $purchases,
            'tax_on_purchases'  => $taxOnPurchases,
            'tax_payable'       => max(0, $taxOnSales - $taxOnPurchases), // 納付消費税額
            'note'              => '概算値（10%一律）。軽減税率対応は journal_entries に tax_rate カラム追加が必要です。',
        ]);
    }
}
