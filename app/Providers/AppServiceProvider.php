<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * 本番環境で APP_DEBUG=true のまま起動した場合にログへ警告を記録する。
     * スタックトレース等の機密情報がレスポンスに含まれるリスクを防ぐため。
     */
    public function boot(): void
    {
        if (app()->isProduction() && config('app.debug')) {
            \Illuminate\Support\Facades\Log::critical(
                '⚠ セキュリティ警告: 本番環境で APP_DEBUG=true が設定されています。' .
                '直ちに .env の APP_DEBUG を false に変更してください。'
            );
        }
    }
}
