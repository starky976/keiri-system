<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * 認証コントローラー
 *
 * セッションベースの認証（ログイン・ログアウト・ユーザー情報取得）を処理する。
 * throttle:5,1 ミドルウェアでブルートフォース攻撃を防御（1分間に5回まで）。
 * CSRF保護は Laravel セッション + X-CSRF-TOKEN ヘッダーで実現。
 */
class AuthController extends Controller
{
    /**
     * ログイン処理
     *
     * メールアドレスとパスワードで認証し、セッションを発行する。
     * 認証失敗時は ValidationException で 422 を返す。
     *
     * @param  Request  $request  email, password, remember?
     * @return JsonResponse  200: {user} / 422: バリデーションエラー
     */
    public function login(Request $request): JsonResponse
    {
        // 入力バリデーション
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // 認証試行（remember オプション対応）
        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            // 認証失敗: email フィールドにエラーを返す
            throw ValidationException::withMessages([
                'email' => 'メールアドレスまたはパスワードが正しくありません。',
            ]);
        }

        // セッション固定攻撃対策のためセッションIDを再発行
        $request->session()->regenerate();

        return response()->json(['user' => auth()->user()]);
    }

    /**
     * ログアウト処理
     *
     * セッションを破棄し、CSRFトークンを再生成する。
     *
     * @param  Request  $request
     * @return JsonResponse  200: {message}
     */
    public function logout(Request $request): JsonResponse
    {
        // セッションを無効化してCSRFトークンを再生成
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'ログアウトしました。']);
    }

    /**
     * 認証中ユーザー情報の取得
     *
     * フロントエンドの初期化時に呼ばれ、ログイン状態を確認する。
     *
     * @return JsonResponse  200: {user} / 401: 未認証
     */
    public function user(): JsonResponse
    {
        return response()->json(['user' => auth()->user()]);
    }
}
