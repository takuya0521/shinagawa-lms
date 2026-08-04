<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\Grade;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\AssignedCourseIndexRequest;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Subject;
use App\Models\User;
use App\Queries\Teacher\AssignedCourseListQuery;
use App\Services\TeacherContextService;
use App\Support\AcademicYear;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

final class AssignedCourseController extends Controller
{
    /**
     * 教員の担当授業一覧を表示する。
     *
     * @param  AssignedCourseIndexRequest  $request  HTTPリクエスト
     * @param  AssignedCourseListQuery  $assignedCourseListQuery  データ取得処理
     * @param  TeacherContextService  $teacherContextService  共通サービス
     * @return View 表示する画面
     */
    public function index(
        AssignedCourseIndexRequest $request,
        AssignedCourseListQuery $assignedCourseListQuery,
        TeacherContextService $teacherContextService,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $teacher = $teacherContextService->resolveTeacher($user);
        $currentAcademicYear = AcademicYear::forDate();
        $selectedAcademicYear = $request->academicYear()
            ?? $currentAcademicYear;

        $academicYears = Course::query()
            ->where('teacher_id', $teacher->id)
            ->select('academic_year')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year')
            ->push($currentAcademicYear)
            ->unique()
            ->sortDesc()
            ->values();

        $classGroups = ClassGroup::query()
            ->whereHas(
                'courses',
                fn (Builder $query): Builder => $query->where('teacher_id', $teacher->id),
            )
            ->orderBy('class_code')
            ->get();

        $subjects = Subject::query()
            ->whereHas(
                'courses',
                fn (Builder $query): Builder => $query->where('teacher_id', $teacher->id),
            )
            ->orderBy('subject_code')
            ->get();

        return view('teacher.courses.index', [
            'courses' => $assignedCourseListQuery->execute(
                $teacher,
                $selectedAcademicYear,
                $request->grade(),
                $request->classGroupId(),
                $request->subjectId(),
            ),
            'academicYears' => $academicYears,
            'grades' => Grade::cases(),
            'classGroups' => $classGroups,
            'subjects' => $subjects,
            'selectedAcademicYear' => $selectedAcademicYear,
            'selectedGrade' => $request->grade(),
            'selectedClassGroupId' => $request->classGroupId(),
            'selectedSubjectId' => $request->subjectId(),
        ]);
    }
}
