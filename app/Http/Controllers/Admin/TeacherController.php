<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateTeacherAction;
use App\Actions\Admin\UpdateTeacherAction;
use App\Enums\MasterStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeacherRequest;
use App\Http\Requests\Admin\TeacherIndexRequest;
use App\Http\Requests\Admin\UpdateTeacherRequest;
use App\Models\Teacher;
use App\Models\User;
use App\Queries\Admin\TeacherAssignedCourseQuery;
use App\Queries\Admin\TeacherListQuery;
use App\Queries\Admin\TeacherTargetStudentQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class TeacherController extends Controller
{
    /**
     * 管理者向け教員一覧を表示する。
     *
     * @param  TeacherIndexRequest  $request  HTTPリクエスト
     * @param  TeacherListQuery  $query  検索処理
     * @return View 表示する画面
     */
    public function index(
        TeacherIndexRequest $request,
        TeacherListQuery $query,
    ): View {
        $teachers = $query->execute(
            keyword: $request->keyword(),
            teacherStatus: $request->teacherStatus(),
            accountStatus: $request->accountStatus(),
        );

        return view('admin.teachers.index', [
            'teachers' => $teachers,
            'teacherStatuses' => MasterStatus::cases(),
            'accountStatuses' => UserStatus::cases(),
        ]);
    }

    /**
     * 教員登録画面を表示する。
     *
     * @return View 表示する画面
     */
    public function create(): View
    {
        return view('admin.teachers.create', [
            ...$this->formData(),
            'teacher' => new Teacher,
            'user' => new User,
        ]);
    }

    /**
     * 教員とログインアカウントを登録する。
     *
     * @param  StoreTeacherRequest  $request  HTTPリクエスト
     * @param  CreateTeacherAction  $action  業務処理
     * @return RedirectResponse リダイレクトレスポンス
     */
    public function store(
        StoreTeacherRequest $request,
        CreateTeacherAction $action,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $teacher = $action->execute(
            data: $request->validated(),
            actor: $actor,
            ipAddress: $request->ip(),
        );

        return redirect()
            ->route('admin.teachers.edit', $teacher)
            ->with('success', '教員を登録しました。');
    }

    /**
     * 管理者向け教員詳細を表示する。
     *
     * @param  Teacher  $teacher  対象教員
     * @param  TeacherAssignedCourseQuery  $assignedCourseQuery  データ取得処理
     * @param  TeacherTargetStudentQuery  $targetStudentQuery  データ取得処理
     * @return View 表示する画面
     */
    public function show(
        Teacher $teacher,
        TeacherAssignedCourseQuery $assignedCourseQuery,
        TeacherTargetStudentQuery $targetStudentQuery,
    ): View {
        $teacher->loadMissing('user');

        $assignedCourses = $assignedCourseQuery->execute($teacher);

        return view('admin.teachers.show', [
            'teacher' => $teacher,
            'assignedCourses' => $assignedCourses,
            'targetStudentsByCourse' => $targetStudentQuery->execute(
                $assignedCourses,
            ),
        ]);
    }

    /**
     * 教員編集画面を表示する。
     *
     * @param  Teacher  $teacher  対象教員
     * @return View 表示する画面
     */
    public function edit(Teacher $teacher): View
    {
        $teacher->loadMissing('user');

        return view('admin.teachers.edit', [
            ...$this->formData(),
            'teacher' => $teacher,
            'user' => $teacher->user,
        ]);
    }

    /**
     * 教員とログインアカウントを更新する。
     *
     * @param  UpdateTeacherRequest  $request  HTTPリクエスト
     * @param  Teacher  $teacher  対象教員
     * @param  UpdateTeacherAction  $action  業務処理
     * @return RedirectResponse リダイレクトレスポンス
     */
    public function update(
        UpdateTeacherRequest $request,
        Teacher $teacher,
        UpdateTeacherAction $action,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $action->execute(
            teacher: $teacher,
            data: $request->validated(),
            actor: $actor,
            ipAddress: $request->ip(),
        );

        return redirect()
            ->route('admin.teachers.edit', $teacher)
            ->with('success', '教員情報を更新しました。');
    }

    /**
     * 教員登録・編集画面で使用する選択肢を返す。
     *
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'teacherStatuses' => MasterStatus::cases(),
            'accountStatuses' => UserStatus::cases(),
        ];
    }
}
