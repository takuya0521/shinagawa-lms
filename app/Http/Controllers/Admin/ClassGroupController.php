<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateClassGroupAction;
use App\Actions\Admin\UpdateClassGroupAction;
use App\Enums\MasterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClassGroupRequest;
use App\Http\Requests\Admin\UpdateClassGroupRequest;
use App\Models\ClassGroup;
use App\Queries\Admin\ClassGroupListQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ClassGroupController extends Controller
{
    /**
     * 管理者向けクラス一覧を表示する。
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
     */
    public function store(
        StoreClassGroupRequest $request,
        CreateClassGroupAction $action,
    ): RedirectResponse {
        $classGroup = $action->execute(
            $request->validated(),
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
     */
    public function update(
        UpdateClassGroupRequest $request,
        ClassGroup $classGroup,
        UpdateClassGroupAction $action,
    ): RedirectResponse {
        $action->execute(
            classGroup: $classGroup,
            data: $request->validated(),
        );

        return redirect()
            ->route(
                'admin.class-groups.edit',
                $classGroup,
            )
            ->with('success', 'クラス情報を更新しました。');
    }
}
