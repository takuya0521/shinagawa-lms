<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateUserAction;
use App\Actions\Admin\UpdateUserAction;
use App\Enums\MasterStatus;
use App\Enums\StudentStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\StudentIndexRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\User;
use App\Queries\Admin\StudentListQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class StudentController extends Controller
{
    /**
     * 管理者向け生徒一覧を表示する。
     */
    public function index(
        StudentIndexRequest $request,
        StudentListQuery $query,
    ): View {
        $students = $query->execute(
            keyword: $request->keyword(),
            grade: $request->grade(),
            affiliation: $request->affiliation(),
            classGroupId: $request->classGroupId(),
            status: $request->status(),
        );

        $classGroups = ClassGroup::query()
            ->where('status', MasterStatus::Active->value)
            ->orderBy('class_code')
            ->get();

        return view('admin.students.index', [
            'students' => $students,
            'classGroups' => $classGroups,
            'statuses' => StudentStatus::cases(),
        ]);
    }

    /**
     * 生徒登録画面を表示する。
     */
    public function create(): View
    {
        return view('admin.students.create', [
            ...$this->formData(),
            'student' => new Student,
            'user' => new User,
        ]);
    }

    /**
     * 生徒とログインアカウントを登録する。
     */
    public function store(
        StoreUserRequest $request,
        CreateUserAction $action,
    ): RedirectResponse {
        $user = $action->execute($request->validated());

        $student = $user
            ->student()
            ->firstOrFail();

        return redirect()
            ->route('admin.students.edit', $student)
            ->with('success', '生徒を登録しました。');
    }

    /**
     * 管理者向け生徒詳細を表示する。
     */
    public function show(Student $student): View
    {
        $student->loadMissing([
            'user',
            'classGroup',
        ]);

        return view('admin.students.show', [
            'student' => $student,
        ]);
    }

    /**
     * 生徒編集画面を表示する。
     */
    public function edit(Student $student): View
    {
        $student->loadMissing([
            'user',
            'classGroup',
        ]);

        return view('admin.students.edit', [
            ...$this->formData(),
            'student' => $student,
            'user' => $student->user,
        ]);
    }

    /**
     * 生徒とログインアカウントを更新する。
     */
    public function update(
        UpdateUserRequest $request,
        Student $student,
        UpdateUserAction $action,
    ): RedirectResponse {
        $action->execute(
            user: $student->user,
            data: $request->validated(),
        );

        return redirect()
            ->route('admin.students.edit', $student)
            ->with('success', '生徒情報を更新しました。');
    }

    /**
     * 生徒登録・編集画面で利用する選択肢を返す。
     *
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'accountStatuses' => UserStatus::cases(),
            'studentStatuses' => StudentStatus::cases(),
            'classGroups' => ClassGroup::query()
                ->where('status', MasterStatus::Active->value)
                ->orderBy('class_code')
                ->get(),
        ];
    }
}
