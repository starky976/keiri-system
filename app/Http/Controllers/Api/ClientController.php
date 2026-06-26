<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 取引先コントローラー
 *
 * 取引先マスタの CRUD を提供する。
 * 検索: name・code に対する LIKE 検索 + type フィルタ。
 * 削除はソフトデリートで物理削除しない（関連データ保護）。
 */
class ClientController extends Controller
{
    /**
     * 取引先一覧（ページネーション）
     *
     * @param  Request  $request  search?, type?
     * @return JsonResponse  200: PaginationResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Client::query();

        // キーワード検索: name または code に部分一致
        if ($request->filled('search')) {
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('code', 'like', "%{$request->search}%")
            );
        }

        // 種別フィルタ: customer / vendor / both
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return response()->json($query->orderBy('code')->paginate(20));
    }

    /**
     * 取引先登録
     *
     * @param  Request  $request
     * @return JsonResponse  201: Client
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'           => 'required|string|max:20|unique:clients',
            'name'           => 'required|string|max:100',
            'name_kana'      => 'nullable|string|max:100',
            'type'           => 'required|in:customer,vendor,both',
            'postal_code'    => 'nullable|string|max:10',
            'address'        => 'nullable|string|max:200',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email',
            'contact_person' => 'nullable|string|max:50',
            'payment_terms'  => 'nullable|integer|min:0|max:180',
            'is_active'      => 'boolean',
            'notes'          => 'nullable|string',
        ]);

        return response()->json(Client::create($data), 201);
    }

    /**
     * 取引先詳細
     * 関連する最新売上5件・最新請求書5件を Eager Load で取得する
     *
     * @param  Client  $client  Route Model Binding
     * @return JsonResponse  200: Client+sales+invoices
     */
    public function show(Client $client): JsonResponse
    {
        return response()->json($client->load([
            'sales'    => fn($q) => $q->latest()->limit(5),
            'invoices' => fn($q) => $q->latest()->limit(5),
        ]));
    }

    /**
     * 取引先更新
     * code の unique チェックは自分自身を除外する
     *
     * @param  Request  $request
     * @param  Client   $client  Route Model Binding
     * @return JsonResponse  200: Client
     */
    public function update(Request $request, Client $client): JsonResponse
    {
        $data = $request->validate([
            // 自分自身の code は重複許可（ignore）
            'code'           => "required|string|max:20|unique:clients,code,{$client->id}",
            'name'           => 'required|string|max:100',
            'name_kana'      => 'nullable|string|max:100',
            'type'           => 'required|in:customer,vendor,both',
            'postal_code'    => 'nullable|string|max:10',
            'address'        => 'nullable|string|max:200',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email',
            'contact_person' => 'nullable|string|max:50',
            'payment_terms'  => 'nullable|integer|min:0|max:180',
            'is_active'      => 'boolean',
            'notes'          => 'nullable|string',
        ]);

        $client->update($data);
        return response()->json($client);
    }

    /**
     * 取引先削除（ソフトデリート）
     *
     * @param  Client  $client  Route Model Binding
     * @return JsonResponse  200: {message}
     */
    public function destroy(Client $client): JsonResponse
    {
        $client->delete();
        return response()->json(['message' => '削除しました。']);
    }
}
