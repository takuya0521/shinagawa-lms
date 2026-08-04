<?php

namespace App\Queries\Teacher;

use App\Enums\AttendanceStatus;
use App\Enums\LessonStatus;
use App\Models\LessonSession;
use App\Models\Teacher;
use App\Queries\Attendance\TargetStudentQuery;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

final class AttendanceSessionListQuery
{
    /**
     * 必要な依存関係と初期値を受け取って初期化する。
     *
     * @param  TargetStudentQuery  $targetStudentQuery  データ取得処理
     */
    public function __construct(
        private readonly TargetStudentQuery $targetStudentQuery,
    ) {}

    /**
     * 担当授業の授業実施日へ登録状況を付与して取得する。
     *
     * @param  Teacher  $teacher  対象教員
     * @param  CarbonInterface  $dateFrom  検索開始日
     * @param  CarbonInterface  $dateTo  検索終了日
     * @param  ?int  $courseId  対象授業ID
     * @return Collection<int, LessonSession>
     */
    public function summaries(
        Teacher $teacher,
        CarbonInterface $dateFrom,
        CarbonInterface $dateTo,
        ?int $courseId,
    ): Collection {
        $lessonSessions = LessonSession::query()
            ->with([
                'timetableSlot.course.subject',
                'timetableSlot.course.classGroup',
                'attendanceRecords',
            ])
            ->whereBetween(
                'lesson_date',
                [
                    $dateFrom->format('Y-m-d'),
                    $dateTo->format('Y-m-d'),
                ],
            )
            ->whereHas(
                'timetableSlot.course',
                function (Builder $query) use (
                    $teacher,
                    $courseId,
                ): void {
                    $query->where(
                        'teacher_id',
                        $teacher->id,
                    );

                    if ($courseId !== null) {
                        $query->where('id', $courseId);
                    }
                },
            )
            ->orderByDesc('lesson_date')
            ->orderByDesc('id')
            ->get();

        $lessonSessions->each(function (LessonSession $lessonSession): void {
            $targetCount = $this->targetStudentQuery->count(
                $lessonSession->timetableSlot->course,
            );
            $recordedCount = $lessonSession->attendanceRecords->count();

            $lessonSession->setAttribute('target_count', $targetCount);
            $lessonSession->setAttribute('recorded_count', $recordedCount);
            $lessonSession->setAttribute(
                'missing_count',
                $lessonSession->status === LessonStatus::Cancelled
                    ? 0
                    : max($targetCount - $recordedCount, 0),
            );
        });

        return $lessonSessions;
    }

    /**
     * 担当授業の授業実施日と出欠登録状況を取得する。
     *
     * @param  Teacher  $teacher  対象教員
     * @param  CarbonInterface  $dateFrom  検索開始日
     * @param  CarbonInterface  $dateTo  検索終了日
     * @param  ?int  $courseId  対象授業ID
     * @param  ?AttendanceStatus  $attendanceStatus  出欠状態
     * @param  bool  $missingOnly  未登録のみを対象とするフラグ
     * @return LengthAwarePaginator<int, LessonSession>
     */
    public function execute(
        Teacher $teacher,
        CarbonInterface $dateFrom,
        CarbonInterface $dateTo,
        ?int $courseId,
        ?AttendanceStatus $attendanceStatus,
        bool $missingOnly,
    ): LengthAwarePaginator {
        $lessonSessions = $this->summaries(
            $teacher,
            $dateFrom,
            $dateTo,
            $courseId,
        );

        if ($attendanceStatus !== null) {
            $lessonSessions = $lessonSessions
                ->filter(
                    static fn (LessonSession $lessonSession): bool => $lessonSession
                        ->attendanceRecords
                        ->contains(
                            static fn ($record): bool => $record->attendance_status === $attendanceStatus,
                        ),
                )
                ->values();
        }

        if ($missingOnly) {
            $lessonSessions = $lessonSessions
                ->filter(
                    static fn (LessonSession $lessonSession): bool => (int) $lessonSession->getAttribute('missing_count') > 0,
                )
                ->values();
        }

        return $this->paginate($lessonSessions, 50);
    }

    /**
     * コレクションをLengthAwarePaginatorへ変換する。
     *
     * @param  Collection<int, LessonSession>  $items
     * @param  int  $perPage  1ページ当たりの表示件数
     * @return LengthAwarePaginator<int, LessonSession>
     */
    private function paginate(
        Collection $items,
        int $perPage,
    ): LengthAwarePaginator {
        $currentPage = Paginator::resolveCurrentPage();
        $pageItems = $items
            ->slice(
                ($currentPage - 1) * $perPage,
                $perPage,
            )
            ->values();

        return new ConcretePaginator(
            $pageItems,
            $items->count(),
            $perPage,
            $currentPage,
            [
                'path' => Paginator::resolveCurrentPath(),
                'query' => request()->query(),
            ],
        );
    }
}
