<?php

namespace App\Http\Controllers\Teacher;

use App\Actions\Interview\CreateInterviewRecordAction;
use App\Actions\Interview\UpdateInterviewRecordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\InterviewIndexRequest;
use App\Http\Requests\Teacher\StoreInterviewRequest;
use App\Http\Requests\Teacher\UpdateInterviewRequest;
use App\Models\InterviewRecord;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Queries\Interview\AssignedStudentQuery;
use App\Queries\Interview\InterviewTypeOptionsQuery;
use App\Queries\Teacher\InterviewRecordListQuery;
use App\Services\TeacherContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 教員向け面談記録画面の表示と登録・更新を制御する。
 */
final class InterviewController extends Controller
{
    /**
     * ログイン教員の担当生徒に関する面談履歴を表示する。
     *
     * @param InterviewIndexRequest $request 検証済み検索条件を含むリクエスト
     * @param TeacherContextService $teacherContextService ログイン教員の取得処理
     * @param AssignedStudentQuery $assignedStudentQuery 担当生徒の取得処理
     * @param InterviewRecordListQuery $interviewRecordListQuery 面談記録一覧の検索処理
     * @param InterviewTypeOptionsQuery $interviewTypeOptionsQuery 面談種別選択肢の取得処理
     * @return View 面談履歴一覧画面
     */
    public function index(
        InterviewIndexRequest $request,
        TeacherContextService $teacherContextService,
        AssignedStudentQuery $assignedStudentQuery,
        InterviewRecordListQuery $interviewRecordListQuery,
        InterviewTypeOptionsQuery $interviewTypeOptionsQuery,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $teacher = $teacherContextService->resolveTeacher($user);

        return view('teacher.interviews.index', [
            'interviewRecords' => $interviewRecordListQuery->execute(
                $teacher,
                $request->dateFrom(),
                $request->dateTo(),
                $request->keyword(),
                $request->nullableId('student_id'),
                $request->interviewType(),
            ),
            'students' => $assignedStudentQuery->get($teacher),
            'interviewTypes' => $interviewTypeOptionsQuery->execute($teacher),
            'dateFrom' => $request->dateFrom(),
            'dateTo' => $request->dateTo(),
            'keyword' => $request->keyword(),
            'selectedStudentId' => $request->nullableId('student_id'),
            'selectedInterviewType' => $request->interviewType(),
        ]);
    }

    /**
     * 教員向け面談記録登録画面を表示する。
     *
     * @param Request $request 初期選択する生徒IDを含むリクエスト
     * @param TeacherContextService $teacherContextService ログイン教員の取得処理
     * @param AssignedStudentQuery $assignedStudentQuery 担当生徒の取得処理
     * @param InterviewTypeOptionsQuery $interviewTypeOptionsQuery 面談種別選択肢の取得処理
     * @return View 面談記録登録画面
     */
    public function create(
        Request $request,
        TeacherContextService $teacherContextService,
        AssignedStudentQuery $assignedStudentQuery,
        InterviewTypeOptionsQuery $interviewTypeOptionsQuery,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $teacher = $teacherContextService->resolveTeacher($user);
        $students = $assignedStudentQuery->get($teacher);
        $requestedStudentId = $request->integer('student_id');
        $selectedStudentId = $students->contains(
            static fn (Student $student): bool => $student->id === $requestedStudentId,
        )
            ? $requestedStudentId
            : null;

        return view('teacher.interviews.create', [
            'interviewRecord' => new InterviewRecord,
            'teacher' => $teacher->loadMissing('user'),
            'students' => $students,
            'interviewTypes' => $interviewTypeOptionsQuery->execute($teacher),
            'selectedStudentId' => $selectedStudentId,
        ]);
    }

    /**
     * 担当生徒の面談記録を登録する。
     *
     * @param StoreInterviewRequest $request 検証済み面談記録を含むリクエスト
     * @param TeacherContextService $teacherContextService ログイン教員の取得処理
     * @param AssignedStudentQuery $assignedStudentQuery 担当生徒の取得処理
     * @param CreateInterviewRecordAction $createInterviewRecordAction 面談記録登録処理
     * @return RedirectResponse 登録後の面談記録編集画面へのリダイレクト
     */
    public function store(
        StoreInterviewRequest $request,
        TeacherContextService $teacherContextService,
        AssignedStudentQuery $assignedStudentQuery,
        CreateInterviewRecordAction $createInterviewRecordAction,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $teacher = $teacherContextService->resolveTeacher($user);
        $attributes = $request->interviewAttributes();
        $student = Student::query()->findOrFail($attributes['student_id']);

        abort_unless(
            $assignedStudentQuery->contains($teacher, $student),
            403,
        );

        $interviewRecord = $createInterviewRecordAction->execute(
            [
                ...$attributes,
                'teacher_id' => $teacher->id,
            ],
            $user,
            $request->ip(),
        );

        return redirect()
            ->route('teacher.interviews.edit', $interviewRecord)
            ->with('success', '面談記録を登録しました。');
    }

    /**
     * 教員向け面談記録編集画面を表示する。
     *
     * @param Request $request ログインユーザーを含むリクエスト
     * @param InterviewRecord $interviewRecord 編集対象の面談記録
     * @param TeacherContextService $teacherContextService ログイン教員の取得処理
     * @param AssignedStudentQuery $assignedStudentQuery 担当生徒の取得処理
     * @param InterviewTypeOptionsQuery $interviewTypeOptionsQuery 面談種別選択肢の取得処理
     * @return View 面談記録編集画面
     */
    public function edit(
        Request $request,
        InterviewRecord $interviewRecord,
        TeacherContextService $teacherContextService,
        AssignedStudentQuery $assignedStudentQuery,
        InterviewTypeOptionsQuery $interviewTypeOptionsQuery,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $teacher = $teacherContextService->resolveTeacher($user);
        $this->assertEditableRecord(
            $teacher,
            $interviewRecord,
            $assignedStudentQuery,
        );
        $interviewRecord->loadMissing([
            'student.classGroup',
            'teacher.user',
            'creator',
            'updater',
        ]);

        return view('teacher.interviews.edit', [
            'interviewRecord' => $interviewRecord,
            'teacher' => $teacher->loadMissing('user'),
            'students' => $assignedStudentQuery->get($teacher),
            'interviewTypes' => $interviewTypeOptionsQuery->execute($teacher),
            'selectedStudentId' => $interviewRecord->student_id,
        ]);
    }

    /**
     * 担当生徒の面談記録を更新する。
     *
     * @param UpdateInterviewRequest $request 検証済み面談記録を含むリクエスト
     * @param InterviewRecord $interviewRecord 更新対象の面談記録
     * @param TeacherContextService $teacherContextService ログイン教員の取得処理
     * @param AssignedStudentQuery $assignedStudentQuery 担当生徒の取得処理
     * @param UpdateInterviewRecordAction $updateInterviewRecordAction 面談記録更新処理
     * @return RedirectResponse 更新後の面談記録編集画面へのリダイレクト
     */
    public function update(
        UpdateInterviewRequest $request,
        InterviewRecord $interviewRecord,
        TeacherContextService $teacherContextService,
        AssignedStudentQuery $assignedStudentQuery,
        UpdateInterviewRecordAction $updateInterviewRecordAction,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $teacher = $teacherContextService->resolveTeacher($user);
        $this->assertEditableRecord(
            $teacher,
            $interviewRecord,
            $assignedStudentQuery,
        );
        $attributes = $request->interviewAttributes();
        $student = Student::query()->findOrFail($attributes['student_id']);

        abort_unless(
            $assignedStudentQuery->contains($teacher, $student),
            403,
        );

        $updateInterviewRecordAction->execute(
            $interviewRecord,
            [
                ...$attributes,
                'teacher_id' => $teacher->id,
            ],
            $user,
            $request->ip(),
        );

        return redirect()
            ->route('teacher.interviews.edit', $interviewRecord)
            ->with('success', '面談記録を更新しました。');
    }

    /**
     * 教員が編集可能な面談記録であることを確認する。
     *
     * @param Teacher $teacher ログイン教員
     * @param InterviewRecord $interviewRecord 確認対象の面談記録
     * @param AssignedStudentQuery $assignedStudentQuery 担当生徒の確認処理
     * @return void 戻り値なし
     */
    private function assertEditableRecord(
        Teacher $teacher,
        InterviewRecord $interviewRecord,
        AssignedStudentQuery $assignedStudentQuery,
    ): void {
        abort_unless(
            $interviewRecord->teacher_id === $teacher->id,
            403,
        );

        $student = Student::query()->findOrFail(
            $interviewRecord->student_id,
        );

        abort_unless(
            $assignedStudentQuery->contains($teacher, $student),
            403,
        );
    }
}
