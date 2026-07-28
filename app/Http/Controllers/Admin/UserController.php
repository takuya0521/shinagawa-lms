<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ChangeUserStatusAction;
use App\Actions\Admin\CreateUserAction;
use App\Actions\Admin\UpdateUserAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Requests\Admin\UserIndexRequest;
use App\Models\User;
use App\Queries\Admin\UserListQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class UserController extends Controller
{
    /**
     * 管理者向けユーザー一覧を表示する。
     */
    public function index(
        UserIndexRequest $request,
        UserListQuery $query,
    ): View {
        $users = $query->execute(
            keyword: $request->keyword(),
            role: $request->role(),
            status: $request->status(),
        );

        return view('admin.users.index', [
            'users' => $users,
            'roles' => UserRole::cases(),
            'statuses' => UserStatus::cases(),
        ]);
    }

    /**
     * ユーザー登録画面を表示する。
     */
    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => UserRole::cases(),
            'statuses' => UserStatus::cases(),
        ]);
    }

    /**
     * ユーザーを登録する。
     */
    public function store(
        StoreUserRequest $request,
        CreateUserAction $action,
    ): RedirectResponse {
        $user = $action->execute($request->validated());

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('success', 'ユーザーを登録しました。');
    }

    /**
     * ユーザー編集画面を表示する。
     */
    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => UserRole::cases(),
            'statuses' => UserStatus::cases(),
        ]);
    }

    /**
     * ユーザー情報を更新する。
     */
    public function update(
        UpdateUserRequest $request,
        User $user,
        UpdateUserAction $action,
    ): RedirectResponse {
        $action->execute($user, $request->validated());

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('success', 'ユーザー情報を更新しました。');
    }

    /**
     * ユーザーの利用状態を変更する。
     */
    public function updateStatus(
        UpdateUserStatusRequest $request,
        User $user,
        ChangeUserStatusAction $action,
    ): RedirectResponse {
        $status = $request->status();

        $action->execute($user, $status);

        return back()->with(
            'success',
            "ユーザーの利用状態を「{$status->label()}」へ変更しました。",
        );
    }
}
