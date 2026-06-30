<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 仕訳コントローラー
 *
 * 複式簿記の仕訳（借方・貸方）を CRUD する。
 * 仕訳ヘッダー (journals) + 仕訳明細 (journal_entries) を
 * DB::transaction() で一括保存する。
 *
 * 制約:
 *  - 借方合計 === 貸方合計でなければ 422 を返す（貸借一致）
 *  - 番号採番: J + YYYYMMDD + 当日連番4桁
 */
class JournalController extends Controller
{
    use \App\Http\Controllers\Api\Concerns\EscapesLikeQuery;

    /**
     * 仕訳一覧
     *
     * @param  Request  $request  search?, from?, to?
     * @return JsonResponse  200: PaginationResponse
     */
    public function index(Request $request): JsonResponse
    {
        $q = Journal::with('user:id,name');

        // キーワード検索: 番号・摘要
        if ($request->filled('search')) {
            $q->where(fn($q) => $q
                ->where('journal_number', 'like', '%' . $this->escapeLike($request->search) . '%')
                ->orWhere('description', 'like', '%' . $this->escapeLike($request->search) . '%')
            );
        }

        // 期間フィルタ
        if ($request->filled('from')) $q->where('journal_date', '>=', $request->from);
        if ($request->filled('to'))   $q->where('journal_date', '<=', $request->to);

        return response()->json($q->orderByDesc('journal_date')->paginate(20));
    }

    /**
     * 仕訳登録
     *
     * 借方合計と貸方合計が一致しない場合は 422 を返す（貸借バランスチェック）。
     *
     * @param  Request  $request
     * @return JsonResponse  201: Journal+entries
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'journal_date'          => 'required|date',
            'description'           => 'required|string|max:200',
            'entries'               => 'required|array|min:2',
            'entries.*.side'        => 'required|in:debit,credit',
            'entries.*.account_item_id' => 'required|exists:account_items,id',
            'entries.*.amount'      => 'required|integer|min:1',
            'entries.*.description' => 'nullable|string',
            'entries.*.sort_order'  => 'nullable|integer',
        ]);

        // 貸借バランスチェック（借方合計 === 貸方合計）
        $debitTotal  = collect($data['entries'])->where('side', 'debit')->sum('amount');
        $creditTotal = collect($data['entries'])->where('side', 'credit')->sum('amount');
        if ($debitTotal !== $creditTotal) {
            return response()->json(['message' => '借方合計と貸方合計が一致しません。'], 422);
        }

        $journal = DB::transaction(function () use ($data) {
            // 仕訳番号採番: J + YYYYMMDD + 当日連番
            $prefix = 'J' . Carbon::now()->format('Ymd');
            $j = Journal::create([
                'journal_number' => $prefix . str_pad(Journal::where('journal_number', 'like', $prefix . '%')->count() + 1, 4, '0', STR_PAD_LEFT),
                'journal_date'   => $data['journal_date'],
                'description'    => $data['description'],
                'source_type'    => 'manual',
                'user_id'        => auth()->id(),
            ]);

            // 仕訳明細を一括作成
            foreach ($data['entries'] as $i => $entry) {
                $j->entries()->create(array_merge($entry, ['sort_order' => $entry['sort_order'] ?? $i]));
            }

            return $j;
        });

        return response()->json($journal->load('entries.accountItem'), 201);
    }

    /**
     * 仕訳詳細
     *
     * @param  Journal  $journal  Route Model Binding
     * @return JsonResponse  200: Journal+entries+user
     */
    public function show(Journal $journal): JsonResponse
    {
        return response()->json($journal->load('entries.accountItem', 'user'));
    }

    /**
     * 仕訳更新
     *
     * 既存の明細を一括削除し、送信データで再作成する（リプレース方式）。
     *
     * @param  Request  $request
     * @param  Journal  $journal  Route Model Binding
     * @return JsonResponse  200: Journal+entries
     */
    public function update(Request $request, Journal $journal): JsonResponse
    {
        $data = $request->validate([
            'journal_date'          => 'required|date',
            'description'           => 'required|string|max:200',
            'entries'               => 'required|array|min:2',
            'entries.*.side'        => 'required|in:debit,credit',
            'entries.*.account_item_id' => 'required|exists:account_items,id',
            'entries.*.amount'      => 'required|integer|min:1',
            'entries.*.description' => 'nullable|string',
            'entries.*.sort_order'  => 'nullable|integer',
        ]);

        // 貸借バランスチェック
        $debitTotal  = collect($data['entries'])->where('side', 'debit')->sum('amount');
        $creditTotal = collect($data['entries'])->where('side', 'credit')->sum('amount');
        if ($debitTotal !== $creditTotal) {
            return response()->json(['message' => '借方合計と貸方合計が一致しません。'], 422);
        }

        DB::transaction(function () use ($data, $journal) {
            $journal->update([
                'journal_date' => $data['journal_date'],
                'description'  => $data['description'],
            ]);

            // 既存明細を全削除してから再作成（シンプルなリプレース）
            $journal->entries()->delete();
            foreach ($data['entries'] as $i => $entry) {
                $journal->entries()->create(array_merge($entry, ['sort_order' => $entry['sort_order'] ?? $i]));
            }
        });

        return response()->json($journal->fresh()->load('entries.accountItem'));
    }

    /**
     * 仕訳削除
     *
     * @param  Journal  $journal  Route Model Binding
     * @return JsonResponse  200: {message}
     */
    public function destroy(Journal $journal): JsonResponse
    {
        $journal->entries()->delete(); // 明細を先に削除
        $journal->delete();
        return response()->json(['message' => '削除しました。']);
    }
}
