<?php
/**
 * FixedAssetController（固定資産管理）
 *
 * 固定資産の CRUD と、指定年度の減価償却明細（償却一覧）を提供する。
 * 番号採番: A + YYYYMMDD + 3桁連番（例: A20240101001）
 */
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FixedAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FixedAssetController extends Controller
{
    use \App\Http\Controllers\Api\Concerns\EscapesLikeQuery;

    public function index(Request $request): JsonResponse
    {
        $q = FixedAsset::query()
            ->when($request->category, fn($q, $v) => $q->where('category', $v))
            ->when($request->search,   fn($q, $v) => $q->where(fn($q) =>
                $q->where('name', 'like', '%' . $this->escapeLike($v) . '%')->orWhere('asset_number', 'like', '%' . $this->escapeLike($v) . '%')))
            ->when($request->active === '1', fn($q) => $q->whereNull('disposal_date'))
            ->orderByDesc('acquisition_date');

        return response()->json($q->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                => 'required|string|max:100',
            'category'            => 'required|string|max:50',
            'acquisition_date'    => 'required|date',
            'acquisition_amount'  => 'required|numeric|min:1',
            'useful_life'         => 'required|integer|min:1|max:100',
            'depreciation_method' => 'required|in:straight_line,declining_balance',
            'residual_value'      => 'nullable|numeric|min:0',
            'note'                => 'nullable|string',
        ]);

        // 番号採番: A + YYYYMMDD + 3桁連番
        $date        = now()->format('Ymd');
        $lastNum     = FixedAsset::where('asset_number', 'like', "A{$date}%")->count();
        $data['asset_number']   = 'A' . $date . str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
        $data['residual_value'] = $data['residual_value'] ?? 1;

        return response()->json(FixedAsset::create($data), 201);
    }

    public function show(FixedAsset $fixedAsset): JsonResponse
    {
        return response()->json($fixedAsset);
    }

    /** 減価償却一覧（取得年度〜現在まで各年の償却額・帳簿価額） */
    public function depreciation(FixedAsset $fixedAsset): JsonResponse
    {
        $startYear = $fixedAsset->acquisition_date->year;
        $endYear   = $fixedAsset->disposal_date?->year ?? now()->year;
        $rows      = [];

        for ($y = $startYear; $y <= min($endYear, $startYear + $fixedAsset->useful_life - 1); $y++) {
            $bvStart   = $fixedAsset->bookValueAtYear($y);
            $bvEnd     = $fixedAsset->bookValueAtYear($y + 1);
            $rows[]    = [
                'year'             => $y,
                'book_value_start' => $bvStart,
                'depreciation'     => round($bvStart - $bvEnd, 2),
                'book_value_end'   => $bvEnd,
            ];
        }

        return response()->json(['asset' => $fixedAsset, 'schedule' => $rows]);
    }

    public function update(Request $request, FixedAsset $fixedAsset): JsonResponse
    {
        $fixedAsset->update($request->validate([
            'name'           => 'sometimes|string|max:100',
            'disposal_date'  => 'nullable|date',
            'disposal_amount'=> 'nullable|numeric|min:0',
            'note'           => 'nullable|string',
        ]));
        return response()->json($fixedAsset);
    }

    public function destroy(FixedAsset $fixedAsset): JsonResponse
    {
        $fixedAsset->delete();
        return response()->json(['message' => '削除しました。']);
    }
}
