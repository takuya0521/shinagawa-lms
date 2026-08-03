<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\TimetableIndexRequest;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use App\Queries\Student\TimetableQuery;
use App\Support\AcademicYear;
use Illuminate\View\View;

final class TimetableController extends Controller
{
    /**
     * 生徒本人の週間時間割を表示する。
     *
     * @param TimetableIndexRequest $request HTTPリクエスト
     * @param TimetableQuery $timetableQuery データ取得処理
     * @return View 表示する画面
     */
    public function index(
        TimetableIndexRequest $request,
        TimetableQuery $timetableQuery,
    ): View {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $student = Student::query()
            ->with('classGroup')
            ->where('user_id', $user->id)
            ->first();
        abort_if($student === null, 403);

        $currentAcademicYear = AcademicYear::forDate();

        $academicYears = Course::query()
            ->where('grade', $student->grade->value)
            ->where('class_group_id', $student->class_group_id)
            ->select('academic_year')
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year')
            ->push($currentAcademicYear)
            ->unique()
            ->sortDesc()
            ->values();

        $selectedAcademicYear = $request->academicYear()
            ?? $currentAcademicYear;

        return view('student.timetable.index', [
            'student' => $student,
            'academicYears' => $academicYears,
            'selectedAcademicYear' => $selectedAcademicYear,
            'timetableSlots' => $timetableQuery->execute(
                $student,
                $selectedAcademicYear,
            ),
        ]);
    }
}
