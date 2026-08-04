<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdatePasswordRequest;
use App\Models\User;
use App\Services\OperationLogWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

final class PasswordController extends Controller
{
    /**
     * ローカル認証用のパスワード変更画面を表示する。
     *
     * @return View 表示する画面
     */
    public function edit(): View
    {
        return view('account.password.edit');
    }

    /**
     * ログインユーザー本人のパスワードを変更する。
     *
     * @param  UpdatePasswordRequest  $request  HTTPリクエスト
     * @param  OperationLogWriter  $operationLogWriter  操作ログ記録サービス
     * @return RedirectResponse リダイレクトレスポンス
     */
    public function update(
        UpdatePasswordRequest $request,
        OperationLogWriter $operationLogWriter,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $user->update([
            'password' => Hash::make(
                (string) $request->validated('password'),
            ),
        ]);

        $operationLogWriter->write(
            actor: $user,
            action: 'change_password',
            target: $user,
            ipAddress: $request->ip(),
        );

        return redirect()
            ->route('account.password.edit')
            ->with('success', 'パスワードを変更しました。');
    }
}
