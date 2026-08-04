<?php

namespace App\Queries\Admin;

use App\Data\Admin\AttendanceIndexData;
use App\Enums\AttendanceStatus;
use App\Enums\Grade;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\Student;
use App\Queries\Attendance\AttendanceStatisticsQuery;
use Carbon\CarbonImmutable;

/**
 * 管理者向け出欠一覧画面の表示データを取得する。
 */
final readonly class AttendanceIndexDataQuery
{
    /**
     * 必要な検索処理を受け取る。
     *
     * @param  AttendanceRecordListQuery  $attendanceRecordListQuery  出欠記録一覧と集計対象授業日の検索処理
     * @param  AttendanceStatisticsQuery  $attendanceStatisticsQuery  出欠集計処理
     */
    public function __construct(
        private AttendanceRecordListQuery $attendanceRecordListQuery,
        private AttendanceStatisticsQuery $attendanceStatisticsQuery,
    ) {}

    /**
     * 指定された検索条件で出欠一覧画面の表示データを取得する。
     *
     * @param  CarbonImmutable  $dateFrom  集計開始日
     * @param  CarbonImmutable  $dateTo  集計終了日
     * @param  int|null  $studentId  生徒ID
     * @param  int|null  $courseId  授業ID
     * @param  Grade|null  $grade  学年
     * @param  int|null  $classGroupId  クラスID
     * @param  AttendanceStatus|null  $attendanceStatus  出欠区分
     * @return AttendanceIndexData 出欠一覧画面の表示データ
     */
    public function execute(
        CarbonImmutable $dateFrom,
        CarbonImmutable $dateTo,
        ?int $studentId,
        ?int $courseId,
        ?Grade $grade,
        ?int $classGroupId,
        ?AttendanceStatus $attendanceStatus,
    ): AttendanceIndexData {
        $student = $studentId !== null
            ? Student::query()->find($studentId)
            : null;
        $statisticsSessions = $this->attendanceRecordListQuery
            ->sessionsForStatistics(
                $dateFrom,
                $dateTo,
                $student,
                $courseId,
                $grade,
                $classGroupId,
            );

        return new AttendanceIndexData(
            attendanceRecords: $this->attendanceRecordListQuery->execute(
                $dateFrom,
                $dateTo,
                $studentId,
                $courseId,
                $grade,
                $classGroupId,
                $attendanceStatus,
            ),
            statistics: $this->attendanceStatisticsQuery->execute(
                $statisticsSessions,
                $student,
            ),
            students: Student::query()
                ->orderByRaw('student_no IS NULL')
                ->orderBy('student_no')
                ->get(),
            courses: Course::query()
                ->with('subject')
                ->orderByDesc('academic_year')
                ->orderBy('course_name')
                ->get(),
            classGroups: ClassGroup::query()
                ->orderBy('class_code')
                ->get(),
            grades: Grade::cases(),
            attendanceStatuses: AttendanceStatus::cases(),
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            selectedStudentId: $studentId,
            selectedCourseId: $courseId,
            selectedGrade: $grade,
            selectedClassGroupId: $classGroupId,
            selectedAttendanceStatus: $attendanceStatus,
        );
    }
}
