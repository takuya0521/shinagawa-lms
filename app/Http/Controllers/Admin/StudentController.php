<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\CreateUserAction;
use App\Actions\Admin\UpdateUserAction;
use App\Enums\Grade;
use App\Enums\StudentStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\StudentIndexRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\User;
use App\Queries\Admin\StudentDetailQuery;
use App\Queries\Admin\StudentListQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class StudentController extends Controller
{
    /**
     * 管理者向け生徒一覧を表示する。
     *
     * @param  StudentIndexRequest  $request  HTTPリクエスト
     * @param  StudentListQuery  $query  検索処理
     * @return View 表示する画面
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
            ->active()
            ->orderBy('class_code')
            ->get();

        return view('admin.students.index', [
            'students' => $students,
            'grades' => Grade::cases(),
            'classGroups' => $classGroups,
            'statuses' => StudentStatus::cases(),
        ]);
    }

    /**
     * 生徒登録画面を表示する。
     *
     * @return View 表示する画面
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
     *
     * @param  StoreUserRequest  $request  HTTPリクエスト
     * @param  CreateUserAction  $action  業務処理
     * @return RedirectResponse リダイレクトレスポンス
     */
    public function store(
        StoreUserRequest $request,
        CreateUserAction $action,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $user = $action->execute(
            data: $request->validated(),
            actor: $actor,
            ipAddress: $request->ip(),
        );

        $student = $user
            ->student()
            ->firstOrFail();

        return redirect()
            ->route('admin.students.edit', $student)
            ->with('success', '生徒を登録しました。');
    }

    /**
     * 管理者向け生徒詳細を表示する。
     *
     * @param  Student  $student  対象生徒
     * @param  StudentDetailQuery  $detailQuery  データ取得処理
     * @return View 表示する画面
     */
    public function show(
        Student $student,
        StudentDetailQuery $detailQuery,
    ): View {
        $student->loadMissing([
            'user',
            'classGroup',
        ]);

        return view('admin.students.show', [
            'student' => $student,
            ...$detailQuery->execute($student),
        ]);
    }

    /**
     * 生徒編集画面を表示する。
     *
     * @param  Student  $student  対象生徒
     * @return View 表示する画面
     */
    public function edit(Student $student): View
    {
        $student->loadMissing([
            'user',
            'classGroup',
        ]);

        return view('admin.students.edit', [
            ...$this->formData(
                $student->class_group_id,
            ),
            'student' => $student,
            'user' => $student->user,
        ]);
    }

    /**
     * 生徒とログインアカウントを更新する。
     *
     * @param  UpdateUserRequest  $request  HTTPリクエスト
     * @param  Student  $student  対象生徒
     * @param  UpdateUserAction  $action  業務処理
     * @return RedirectResponse リダイレクトレスポンス
     */
    public function update(
        UpdateUserRequest $request,
        Student $student,
        UpdateUserAction $action,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $action->execute(
            user: $student->user,
            data: $request->validated(),
            actor: $actor,
            ipAddress: $request->ip(),
        );

        return redirect()
            ->route('admin.students.edit', $student)
            ->with('success', '生徒情報を更新しました。');
    }

    /**
     * 生徒登録・編集画面で利用する選択肢を返す。
     *
     * 新規登録時は有効なクラスのみ返す。
     * 編集時は、現在所属している無効クラスも選択肢へ残す。
     *
     * @param  ?int  $currentClassGroupId  現在所属しているクラスID
     * @return array<string, mixed>
     */
    private function formData(
        ?int $currentClassGroupId = null,
    ): array {
        return [
            'accountStatuses' => UserStatus::cases(),
            'studentStatuses' => StudentStatus::cases(),
            'grades' => Grade::cases(),
            'classGroups' => ClassGroup::query()
                ->selectable($currentClassGroupId)
                ->orderBy('class_code')
                ->get(),
        ];
    }
}
