<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $now = Carbon::now();

        $monthlySales = Sale::whereYear('sale_date', $now->year)
            ->whereMonth('sale_date', $now->month)->sum('total_amount');

        $monthlyReceipts = Receipt::whereYear('receipt_date', $now->year)
            ->whereMonth('receipt_date', $now->month)->sum('amount');

        $unpaidInvoices = Invoice::whereIn('status', ['sent', 'overdue'])
            ->with('client:id,name')
            ->orderBy('due_date')
            ->limit(5)->get();

        $pendingExpenses = Expense::where('status', 'pending')
            ->with('user:id,name')
            ->orderBy('applied_date')
            ->limit(5)->get();

        $monthlySalesChart = collect(range(5, 0))->map(function ($i) use ($now) {
            $date = $now->copy()->subMonths($i);
            return [
                'month'  => $date->format('Y/m'),
                'amount' => Sale::whereYear('sale_date', $date->year)->whereMonth('sale_date', $date->month)->sum('total_amount'),
            ];
        });

        return response()->json([
            'stats' => [
                'monthlySales'    => $monthlySales,
                'monthlyReceipts' => $monthlyReceipts,
                'overdueCount'    => Invoice::where('status', 'overdue')->count(),
                'pendingExpenses' => Expense::where('status', 'pending')->count(),
            ],
            'unpaidInvoices'    => $unpaidInvoices,
            'pendingExpenses'   => $pendingExpenses,
            'monthlySalesChart' => $monthlySalesChart,
        ]);
    }
}
