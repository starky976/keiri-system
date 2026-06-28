<?php
/**
 * DocumentController（帳票出力）
 *
 * 請求書・領収書・支払明細の帳票データを返す。
 * フロントエンドは受け取ったデータを HTML でレンダリングして window.print() で PDF 化する。
 * ※ バックエンドで PDF 生成せず、ブラウザ印刷に委ねることで依存ライブラリを最小化する。
 */
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;

class DocumentController extends Controller
{
    /** 請求書帳票データ */
    public function invoice(Invoice $invoice): JsonResponse
    {
        return response()->json([
            'type'     => 'invoice',
            'document' => $invoice->load(['client', 'items.accountItem']),
            'meta'     => [
                'issuer'    => config('app.name'),
                'issued_at' => now()->toDateString(),
            ],
        ]);
    }

    /** 領収書帳票データ */
    public function receipt(Receipt $receipt): JsonResponse
    {
        return response()->json([
            'type'     => 'receipt',
            'document' => $receipt->load(['invoice.client']),
            'meta'     => ['issued_at' => now()->toDateString()],
        ]);
    }

    /** 支払明細帳票データ */
    public function payment(Payment $payment): JsonResponse
    {
        return response()->json([
            'type'     => 'payment',
            'document' => $payment->load(['accountItem']),
            'meta'     => ['issued_at' => now()->toDateString()],
        ]);
    }
}
