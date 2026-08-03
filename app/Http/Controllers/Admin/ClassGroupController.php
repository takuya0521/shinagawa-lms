<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateClassGroupAction;
use App\Actions\Admin\UpdateClassGroupAction;
use App\Enums\MasterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClassGroupRequest;
use App\Http\Requests\Admin\UpdateClassGroupRequest;
use App\Models\ClassGroup;
use App\Models\User;
use App\Queries\Admin\ClassGroupListQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ClassGroupController extends Controller
{
    /**
     * 管理者向けクラス一覧を表示する。
     *
     * @param Request $request HTTPリクエスト
     * @param ClassGroupListQuery $query 検索処理
     * @return View 表示する画面
     */
    public function index(
        Request $request,
        ClassGroupListQuery $query,
    ): View {
        $keyword = $request->string('keyword')
            ->trim()
            ->toString();

        $statusValue = $request->string('status')
            ->toString();

        $status = $statusValue !== ''
            ? MasterStatus::tryFrom($statusValue)
            : null;

        $classGroups = $query->execute(
            keyword: $keyword !== '' ? $keyword : null,
            status: $status,
        );

        return view('admin.class-groups.index', [
            'classGroups' => $classGroups,
            'statuses' => MasterStatus::cases(),
        ]);
    }

    /**
     * クラス登録画面を表示する。
     *
     * @return View 表示する画面
     */
    public function create(): View
    {
        return view('admin.class-groups.create', [
            'classGroup' => new ClassGroup,
            'statuses' => MasterStatus::cases(),
        ]);
    }

    /**
     * クラスを登録する。
     *
     * @param StoreClassGroupRequest $request HTTPリクエスト
     * @param CreateClassGroupAction $action 業務処理
     * @return RedirectResponse リダイレクトレスポンス
     */
    public function store(
        StoreClassGroupRequest $request,
        CreateClassGroupAction $action,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $classGroup = $action->execute(
            data: $request->validated(),
            actor: $actor,
            ipAddress: $request->ip(),
        );

        return redirect()
            ->route(
                'admin.class-groups.edit',
                $classGroup,
            )
            ->with('success', 'クラスを登録しました。');
    }

    /**
     * クラス編集画面を表示する。
     *
     * @param ClassGroup $classGroup 対象クラス
     * @return View 表示する画面
     */
    public function edit(ClassGroup $classGroup): View
    {
        return view('admin.class-groups.edit', [
            'classGroup' => $classGroup,
            'statuses' => MasterStatus::cases(),
        ]);
    }

    /**
     * クラスを更新する。
     *
     * @param UpdateClassGroupRequest $request HTTPリクエスト
     * @param ClassGroup $classGroup 対象クラス
     * @param UpdateClassGroupAction $action 業務処理
     * @return RedirectResponse リダイレクトレスポンス
     */
    public function update(
        UpdateClassGroupRequest $request,
        ClassGroup $classGroup,
        UpdateClassGroupAction $action,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $action->execute(
            classGroup: $classGroup,
            data: $request->validated(),
            actor: $actor,
            ipAddress: $request->ip(),
        );

        return redirect()
            ->route(
                'admin.class-groups.edit',
                $classGroup,
            )
            ->with('success', 'クラス情報を更新しました。');
    }
}
