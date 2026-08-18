<?php

namespace App\Queries\Dashboard;

use App\Data\Dashboard\TodayLessonSummary;
use App\Enums\DayOfWeek;
use App\Enums\LessonStatus;
use App\Enums\MasterStatus;
use App\Models\Course;
use App\Models\LessonSession;
use App\Models\TimetableSlot;
use App\Queries\Attendance\TargetStudentQuery;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * 当日の時間割と出欠登録状況を取得する。
 */
final readonly class TodayLessonSummaryQuery
{
    /**
     * 必要な検索処理を受け取る。
     *
     * @param  TargetStudentQuery  $targetStudentQuery  授業対象の在籍生徒数を取得する検索処理
     */
    public function __construct(
        private TargetStudentQuery $targetStudentQuery,
    ) {}

    /**
     * 指定日の授業と出欠登録状況を集計する。
     *
     * @param  CarbonInterface  $today  集計対象日
     * @param  int  $academicYear  集計対象日が属する年度
     * @param  int|null  $teacherId  指定時は担当教員の授業だけへ絞り込む
     * @return TodayLessonSummary 当日の授業集計結果
     */
    public function execute(
        CarbonInterface $today,
        int $academicYear,
        ?int $teacherId = null,
    ): TodayLessonSummary {
        $slots = $this->slots(
            today: $today,
            academicYear: $academicYear,
            teacherId: $teacherId,
        );

        $unregisteredAttendanceCount = 0;
        $targetCounts = $this->targetStudentQuery->counts(
            $slots
                ->map(static fn (TimetableSlot $slot) => $slot->course)
                ->filter(static fn (?Course $course): bool => $course instanceof Course)
                ->unique('id')
                ->values(),
        );

        $slots->each(function (TimetableSlot $slot) use (
            &$unregisteredAttendanceCount,
            $targetCounts,
        ): void {
            $targetCount = $targetCounts[$slot->course->id] ?? 0;
            $lessonSession = $slot->lessonSessions->first();
            $isCancelled = $lessonSession instanceof LessonSession
                && $lessonSession->status === LessonStatus::Cancelled;
            $recordedCount = $lessonSession instanceof LessonSession
                ? (int) $lessonSession->getAttribute('attendance_records_count')
                : 0;
            $missingCount = $isCancelled
                ? 0
                : max($targetCount - $recordedCount, 0);

            $slot->setAttribute('target_count', $targetCount);
            $slot->setAttribute('recorded_count', $recordedCount);
            $slot->setAttribute('missing_count', $missingCount);
            $slot->setAttribute('is_cancelled', $isCancelled);

            if ($missingCount > 0) {
                $unregisteredAttendanceCount++;
            }
        });

        $lessonCount = $slots
            ->filter(
                static fn (TimetableSlot $slot): bool => ! (bool) $slot->getAttribute('is_cancelled'),
            )
            ->count();

        return new TodayLessonSummary(
            today: $today,
            academicYear: $academicYear,
            slots: $slots,
            lessonCount: $lessonCount,
            unregisteredAttendanceCount: $unregisteredAttendanceCount,
        );
    }

    /**
     * 指定日の有効な時間割枠を取得する。
     *
     * @param  CarbonInterface  $today  集計対象日
     * @param  int  $academicYear  集計対象日が属する年度
     * @param  int|null  $teacherId  指定時は担当教員の授業だけへ絞り込む
     * @return Collection<int, TimetableSlot> 時限順の時間割枠一覧
     */
    private function slots(
        CarbonInterface $today,
        int $academicYear,
        ?int $teacherId,
    ): Collection {
        $dayOfWeek = DayOfWeek::tryFrom($today->dayOfWeekIso);

        return TimetableSlot::query()
            ->with([
                'course.subject',
                'course.classGroup',
                'course.teacher.user',
                'lessonSessions' => static function (Relation $relation) use ($today): void {
                    $relation->getQuery()
                        ->whereDate('lesson_date', $today->format('Y-m-d'))
                        ->withCount('attendanceRecords');
                },
            ])
            ->where('status', MasterStatus::Active->value)
            ->when(
                $dayOfWeek !== null,
                static fn (Builder $query): Builder => $query->where(
                    'day_of_week',
                    $dayOfWeek->value,
                ),
                static fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
            )
            ->whereHas(
                'course',
                static function (Builder $query) use (
                    $academicYear,
                    $teacherId,
                ): void {
                    $query
                        ->where('status', MasterStatus::Active->value)
                        ->where('academic_year', $academicYear)
                        ->when(
                            $teacherId !== null,
                            static fn (Builder $courseQuery): Builder => $courseQuery->where(
                                'teacher_id',
                                $teacherId,
                            ),
                        );
                },
            )
            ->orderBy('period_no')
            ->get();
    }
}
