<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * ログイン成功後の遷移先をLMSのロール別トップへ統一するレスポンス。
 *
 * Fortify標準のintendedリダイレクトを使用すると、未認証時に最後に開こうとした
 * Google Workspace画面などへログイン直後に戻るため、学校ポータルの入口を固定する。
 */
final class LoginResponse implements LoginResponseContract
{
    /**
     * ログイン成功時のHTTPレスポンスを返す。
     *
     * ブラウザからのログインでは保存済みの遷移先を破棄し、共通ダッシュボードを経由して
     * 利用者のロールに対応するトップ画面へ遷移させる。API向けJSONレスポンスは
     * Fortify標準と同じ形式を維持する。
     *
     * @param  Request  $request  認証完了後のリクエスト
     * @return Response ダッシュボードへのリダイレクトまたは認証成功を示すJSONレスポンス
     */
    public function toResponse($request): Response
    {
        // 過去の未認証アクセス先が次回以降のログインへ影響しないよう明示的に破棄する。
        $request->session()->forget('url.intended');

        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : redirect()->route('dashboard');
    }
}
