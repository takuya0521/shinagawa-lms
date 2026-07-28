<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserIndexRequest;
use App\Queries\Admin\UserListQuery;
use Illuminate\Contracts\View\View;

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
}
