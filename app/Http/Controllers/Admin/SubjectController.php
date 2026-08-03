<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateSubjectAction;
use App\Actions\Admin\UpdateSubjectAction;
use App\Enums\MasterStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubjectRequest;
use App\Http\Requests\Admin\UpdateSubjectRequest;
use App\Models\Subject;
use App\Models\User;
use App\Queries\Admin\SubjectListQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SubjectController extends Controller
{
    /**
     * 科目一覧を表示する。
     *
     * @param Request $request HTTPリクエスト
     * @param SubjectListQuery $subjectListQuery データ取得処理
     * @return View 表示する画面
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
     *
     * @return View 表示する画面
     */
    public function create(): View
    {
        return view('admin.subjects.create', [
            'statuses' => MasterStatus::cases(),
        ]);
    }

    /**
     * 科目を登録する。
     *
     * @param StoreSubjectRequest $request HTTPリクエスト
     * @param CreateSubjectAction $createSubjectAction 業務処理
     * @return RedirectResponse リダイレクトレスポンス
     */
    public function store(
        StoreSubjectRequest $request,
        CreateSubjectAction $createSubjectAction,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $createSubjectAction->execute(
            attributes: $request->validated(),
            actor: $actor,
            ipAddress: $request->ip(),
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
     *
     * @param Subject $subject 対象科目
     * @return View 表示する画面
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
     *
     * @param UpdateSubjectRequest $request HTTPリクエスト
     * @param Subject $subject 対象科目
     * @param UpdateSubjectAction $updateSubjectAction 業務処理
     * @return RedirectResponse リダイレクトレスポンス
     */
    public function update(
        UpdateSubjectRequest $request,
        Subject $subject,
        UpdateSubjectAction $updateSubjectAction,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $updateSubjectAction->execute(
            subject: $subject,
            attributes: $request->validated(),
            actor: $actor,
            ipAddress: $request->ip(),
        );

        return redirect()
            ->route('admin.subjects.index')
            ->with(
                'status',
                '科目を更新しました。',
            );
    }
}
