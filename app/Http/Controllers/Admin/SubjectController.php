<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateSubjectAction;
use App\Actions\Admin\UpdateSubjectAction;
use App\Enums\MasterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubjectRequest;
use App\Http\Requests\Admin\UpdateSubjectRequest;
use App\Models\Subject;
use App\Queries\Admin\SubjectListQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SubjectController extends Controller
{
    /**
     * 科目一覧を表示する。
     */
    public function index(
        Request $request,
        SubjectListQuery $subjectListQuery,
    ): View {
        $keyword = trim(
            (string) $request->query('keyword', ''),
        );

        $status = MasterStatus::tryFrom(
            (string) $request->query('status', ''),
        );

        return view('admin.subjects.index', [
            'subjects' => $subjectListQuery->execute(
                $keyword,
                $status,
            ),
            'statuses' => MasterStatus::cases(),
            'keyword' => $keyword,
            'selectedStatus' => $status?->value,
        ]);
    }

    /**
     * 科目登録画面を表示する。
     */
    public function create(): View
    {
        return view('admin.subjects.create', [
            'statuses' => MasterStatus::cases(),
        ]);
    }

    /**
     * 科目を登録する。
     */
    public function store(
        StoreSubjectRequest $request,
        CreateSubjectAction $createSubjectAction,
    ): RedirectResponse {
        $createSubjectAction->execute(
            $request->validated(),
        );

        return redirect()
            ->route('admin.subjects.index')
            ->with(
                'status',
                '科目を登録しました。',
            );
    }

    /**
     * 科目編集画面を表示する。
     */
    public function edit(Subject $subject): View
    {
        return view('admin.subjects.edit', [
            'subject' => $subject,
            'statuses' => MasterStatus::cases(),
        ]);
    }

    /**
     * 科目を更新する。
     */
    public function update(
        UpdateSubjectRequest $request,
        Subject $subject,
        UpdateSubjectAction $updateSubjectAction,
    ): RedirectResponse {
        $updateSubjectAction->execute(
            $subject,
            $request->validated(),
        );

        return redirect()
            ->route('admin.subjects.index')
            ->with(
                'status',
                '科目を更新しました。',
            );
    }
}
