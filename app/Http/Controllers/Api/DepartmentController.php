<?php
/**
 * DepartmentController（部門管理）
 *
 * 部門マスタの CRUD と、部門別損益レポートを提供する。
 * 部門別損益: journals.department_id で仕訳を絞り、収益・費用を集計する。
 */
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\JournalEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Department::orderBy('code')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $dept = Department::create($request->validate([
            'code'        => 'required|string|max:10|unique:departments,code',
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]));
        return response()->json($dept, 201);
    }

    public function update(Request $request, Department $department): JsonResponse
    {
        $department->update($request->validate([
            'name'        => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]));
        return response()->json($department);
    }

    public function destroy(Department $department): JsonResponse
    {
        $department->delete();
        return response()->json(['message' => '削除しました。']);
    }

    /** 部門別損益レポート */
    public function report(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->startOfYear()->toDateString());
        $to   = $request->input('to',   now()->toDateString());

        $rows = JournalEntry::join('journals',      'journals.id',      '=', 'journal_entries.journal_id')
            ->join('account_items', 'account_items.id', '=', 'journal_entries.account_item_id')
            ->join('departments',   'departments.id',   '=', 'journals.department_id')
            ->whereBetween('journals.journal_date', [$from, $to])
            ->whereIn('account_items.type', ['revenue', 'expense'])
            ->select(
                'departments.id',
                'departments.name as department_name',
                'account_items.type',
                'account_items.name as account_name',
                DB::raw('SUM(journal_entries.debit_amount)  as debit_total'),
                DB::raw('SUM(journal_entries.credit_amount) as credit_total')
            )
            ->groupBy('departments.id', 'departments.name', 'account_items.type', 'account_items.name')
            ->get();

        // 部門ごとに集計
        $grouped = $rows->groupBy('id')->map(function ($items, $id) {
            $revenue = $items->where('type', 'revenue')
                ->sum(fn($r) => $r->credit_total - $r->debit_total);
            $expense = $items->where('type', 'expense')
                ->sum(fn($r) => $r->debit_total - $r->credit_total);
            return [
                'department_id'   => $id,
                'department_name' => $items->first()->department_name,
                'revenue'         => $revenue,
                'expense'         => $expense,
                'net_income'      => $revenue - $expense,
                'details'         => $items->values(),
            ];
        })->values();

        return response()->json(['from' => $from, 'to' => $to, 'departments' => $grouped]);
    }
}
