<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateUserAction;
use App\Actions\Admin\UpdateUserAction;
use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTeacherRequest;
use App\Http\Requests\Admin\TeacherIndexRequest;
use App\Http\Requests\Admin\UpdateTeacherRequest;
use App\Models\Teacher;
use App\Models\User;
use App\Queries\Admin\TeacherListQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class TeacherController extends Controller
{
    /**
     * 管理者向け教員一覧を表示する。
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
     */
    public function store(
        StoreTeacherRequest $request,
        CreateUserAction $action,
    ): RedirectResponse {
        $data = $request->validated();
        $data['role'] = UserRole::Teacher->value;

        $user = $action->execute($data);

        $teacher = $user
            ->teacher()
            ->firstOrFail();

        return redirect()
            ->route('admin.teachers.edit', $teacher)
            ->with('success', '教員を登録しました。');
    }

    /**
     * 管理者向け教員詳細を表示する。
     */
    public function show(Teacher $teacher): View
    {
        $teacher->loadMissing('user');

        return view('admin.teachers.show', [
            'teacher' => $teacher,
        ]);
    }

    /**
     * 教員編集画面を表示する。
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
     */
    public function update(
        UpdateTeacherRequest $request,
        Teacher $teacher,
        UpdateUserAction $action,
    ): RedirectResponse {
        $data = $request->validated();
        $data['role'] = UserRole::Teacher->value;

        $action->execute(
            user: $teacher->user,
            data: $data,
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
