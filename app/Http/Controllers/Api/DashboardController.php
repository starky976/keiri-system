<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

/**
 * ダッシュボードコントローラー
 *
 * 経営サマリーを集計して返す。
 * - 当月売上合計 / 当月入金合計
 * - 期限超過請求書件数 / 承認待ち経費件数
 * - 直近5件の未払い請求書・承認待ち経費
 * - 過去6ヶ月の売上推移グラフデータ
 */
class DashboardController extends Controller
{
    /**
     * ダッシュボード集計データを返す
     *
     * @return JsonResponse  200: {stats, unpaidInvoices, pendingExpenses, monthlySalesChart}
     */
    public function index(): JsonResponse
    {
        $now = Carbon::now();

        // 当月の売上合計（税込）
        $monthlySales = Sale::whereYear('sale_date', $now->year)
            ->whereMonth('sale_date', $now->month)->sum('total_amount');

        // 当月の入金合計
        $monthlyReceipts = Receipt::whereYear('receipt_date', $now->year)
            ->whereMonth('receipt_date', $now->month)->sum('amount');

        // 直近5件の未払い請求書（sent または overdue）
        $unpaidInvoices = Invoice::whereIn('status', ['sent', 'overdue'])
            ->with('client:id,name')
            ->orderBy('due_date')
            ->limit(5)->get();

        // 直近5件の承認待ち経費
        $pendingExpenses = Expense::where('status', 'pending')
            ->with('user:id,name')
            ->orderBy('applied_date')
            ->limit(5)->get();

        // 過去6ヶ月の売上推移グラフデータ（月ごとの合計）
        $monthlySalesChart = collect(range(5, 0))->map(function ($i) use ($now) {
            $date = $now->copy()->subMonths($i);
            return [
                'month'  => $date->format('Y/m'),
                'amount' => Sale::whereYear('sale_date', $date->year)
                    ->whereMonth('sale_date', $date->month)->sum('total_amount'),
            ];
        });

        return response()->json([
            'stats' => [
                'monthlySales'    => $monthlySales,
                'monthlyReceipts' => $monthlyReceipts,
                // 期限超過件数（アラート表示用）
                'overdueCount'    => Invoice::where('status', 'overdue')->count(),
                // 承認待ち経費件数（アラート表示用）
                'pendingExpenses' => Expense::where('status', 'pending')->count(),
            ],
            'unpaidInvoices'    => $unpaidInvoices,
            'pendingExpenses'   => $pendingExpenses,
            'monthlySalesChart' => $monthlySalesChart,
        ]);
    }
}
