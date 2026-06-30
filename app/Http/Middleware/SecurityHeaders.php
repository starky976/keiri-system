<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecurityHeaders ミドルウェア
 *
 * 全レスポンスにセキュリティ関連の HTTP ヘッダーを付与する。
 * ブラウザによる不正なコンテンツ解釈・クリックジャッキング・
 * XSS 攻撃等を防ぐための多層防御。
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set(
            // ブラウザによる MIME タイプの自動判定（スニッフィング）を無効化
            'X-Content-Type-Options', 'nosniff'
        );
        $response->headers->set(
            // iframe への埋め込みを同一オリジンのみ許可（クリックジャッキング対策）
            'X-Frame-Options', 'SAMEORIGIN'
        );
        $response->headers->set(
            // ブラウザ組み込みの XSS フィルターを有効化し、攻撃検出時にページをブロック
            'X-XSS-Protection', '1; mode=block'
        );
        $response->headers->set(
            // リファラー情報を同一オリジン内のみに限定（外部サイトへの情報漏洩防止）
            'Referrer-Policy', 'strict-origin-when-cross-origin'
        );
        $response->headers->set(
            // 不要なブラウザ機能（カメラ・マイク・位置情報等）へのアクセスを制限
            'Permissions-Policy', 'camera=(), microphone=(), geolocation=()'
        );

        return $response;
    }
}
