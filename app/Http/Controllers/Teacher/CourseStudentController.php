<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\CourseStudentIndexRequest;
use App\Models\Course;
use App\Models\User;
use App\Queries\Teacher\CourseStudentListQuery;
use App\Services\TeacherContextService;
use Illuminate\View\View;

final class CourseStudentController extends Controller
{
    /**
     * 担当授業の対象生徒一覧を表示する。
     *
     * @param CourseStudentIndexRequest $request HTTPリクエスト
     * @param Course $course 対象授業
     * @param CourseStudentListQuery $courseStudentListQuery データ取得処理
     * @param TeacherContextService $teacherContextService 共通サービス
     * @return View 表示する画面
     */
    public function index(
        CourseStudentIndexRequest $request,
        Course $course,
        CourseStudentListQuery $courseStudentListQuery,
        TeacherContextService $teacherContextService,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $teacherContextService->assertAssignedCourse(
            $user,
            $course,
        );

        $course->load([
            'subject',
            'classGroup',
            'timetableSlots',
        ]);

        return view('teacher.courses.students', [
            'course' => $course,
            'students' => $courseStudentListQuery->execute(
                $course,
                $request->keyword(),
                $request->status(),
            ),
            'statuses' => StudentStatus::cases(),
            'keyword' => $request->keyword(),
            'selectedStatus' => $request->status(),
        ]);
    }
}
