<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /**
         * DB 制約違反（重複・外部キー違反）を 409 Conflict で返す
         *
         * QueryException の SQL エラーコードで判定:
         *  - 23000: UNIQUE 制約違反（MySQL / SQLite 共通）
         *  - 23503: 外部キー制約違反（PostgreSQL）
         * エラー詳細（テーブル名・カラム名）は本番ではログのみに記録し、
         * レスポンスには汎用メッセージのみ返すことで情報漏洩を防ぐ。
         */
        $exceptions->render(function (QueryException $e, $request) {
            if ($request->expectsJson()) {
                $code = $e->errorInfo[1] ?? 0;
                // 23000: UNIQUE 制約違反 / 1452: 外部キー制約違反（MySQL）
                if (in_array($code, [1062, 1451, 1452]) || str_starts_with($e->errorInfo[0] ?? '', '23')) {
                    \Illuminate\Support\Facades\Log::warning('DB constraint violation: ' . $e->getMessage());
                    return response()->json([
                        'message' => 'データの整合性エラーが発生しました。入力内容を確認してください。',
                        'error'   => 'conflict',
                    ], 409);
                }
            }
        });
    })->create();
