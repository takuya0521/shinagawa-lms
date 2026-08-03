<?php

namespace App\Actions\Admin;

use App\Enums\DayOfWeek;
use App\Enums\MasterStatus;
use App\Models\Course;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Queries\Admin\TimetableConflictQuery;
use App\Services\OperationLogWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateTimetableSlotAction
{
    /**
     * 必要な依存関係と初期値を受け取って初期化する。
     *
     * @param TimetableConflictQuery $conflictQuery データ取得処理
     * @param OperationLogWriter $operationLogWriter 操作ログ記録サービス
     */
    public function __construct(
        private readonly TimetableConflictQuery $conflictQuery,
        private readonly OperationLogWriter $operationLogWriter,
    ) {}

    /**
     * 時間割枠を更新する。
     *
     * @param  array<string, mixed>  $attributes
     *
     * @param TimetableSlot $timetableSlot 対象時間割
     * @param User $actor 操作を実行するユーザー
     * @param ?string $ipAddress 操作元IPアドレス
     * @return TimetableSlot 処理結果
     */
    public function execute(
        TimetableSlot $timetableSlot,
        array $attributes,
        User $actor,
        ?string $ipAddress,
    ): TimetableSlot {
        return DB::transaction(
            function () use (
                $timetableSlot,
                $attributes,
                $actor,
                $ipAddress,
            ): TimetableSlot {
                $lockedTimetableSlot = TimetableSlot::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $timetableSlot->id,
                    );

                $before = $this->snapshot($lockedTimetableSlot);

                $currentCourse = Course::query()
                    ->findOrFail(
                        $lockedTimetableSlot->course_id,
                    );

                $selectedCourse = Course::query()
                    ->findOrFail(
                        (int) $attributes['course_id'],
                    );

                $this->conflictQuery->lockConflictScopes([
                    $currentCourse,
                    $selectedCourse,
                ]);

                $currentCourse->refresh();
                $selectedCourse->refresh();

                if (
                    $selectedCourse->id !== $currentCourse->id
                    && $selectedCourse->status !== MasterStatus::Active
                ) {
                    throw ValidationException::withMessages([
                        'course_id' => '選択した授業は利用できません。',
                    ]);
                }

                $this->ensureNoConflict(
                    $lockedTimetableSlot,
                    $selectedCourse,
                    DayOfWeek::from(
                        (int) $attributes['day_of_week'],
                    ),
                    (int) $attributes['period_no'],
                    MasterStatus::from(
                        (string) $attributes['status'],
                    ),
                );

                $lockedTimetableSlot->update(
                    $attributes,
                );

                $lockedTimetableSlot->refresh();

                $this->operationLogWriter->write(
                    actor: $actor,
                    action: 'update_timetable_slot',
                    target: $lockedTimetableSlot,
                    detail: [
                        'before' => $before,
                        'after' => $this->snapshot($lockedTimetableSlot),
                    ],
                    ipAddress: $ipAddress,
                );

                return $lockedTimetableSlot;
            },
        );
    }

    /**
     * 更新直前に時間割の重複を再検証する。
     *
     * @param TimetableSlot $timetableSlot 対象時間割
     * @param Course $course 対象授業
     * @param DayOfWeek $dayOfWeek 曜日
     * @param int $periodNo 時限番号
     * @param MasterStatus $status 設定する状態
     * @return void 戻り値なし
     */
    private function ensureNoConflict(
        TimetableSlot $timetableSlot,
        Course $course,
        DayOfWeek $dayOfWeek,
        int $periodNo,
        MasterStatus $status,
    ): void {
        if (
            $this->conflictQuery->findCourseConflict(
                $course,
                $dayOfWeek,
                $periodNo,
                $timetableSlot->id,
            ) !== null
        ) {
            throw ValidationException::withMessages([
                'period_no' => '同じ授業の同じ曜日・時限は既に登録されています。',
            ]);
        }

        if ($status !== MasterStatus::Active) {
            return;
        }

        if (
            $this->conflictQuery->findClassConflict(
                $course,
                $dayOfWeek,
                $periodNo,
                $timetableSlot->id,
            ) !== null
        ) {
            throw ValidationException::withMessages([
                'period_no' => '同じ年度・学年・クラスの同じ曜日・時限に、別の授業が登録されています。',
            ]);
        }

        if (
            $this->conflictQuery->findTeacherConflict(
                $course,
                $dayOfWeek,
                $periodNo,
                $timetableSlot->id,
            ) !== null
        ) {
            throw ValidationException::withMessages([
                'period_no' => '担当教員は同じ年度の同じ曜日・時限に、別の授業を担当しています。',
            ]);
        }
    }

    /**
     * 操作ログへ記録するスナップショットを生成する。
     *
     * @param TimetableSlot $timetableSlot 対象時間割
     * @return array<string, mixed>
     */
    private function snapshot(TimetableSlot $timetableSlot): array
    {
        return [
            'course_id' => $timetableSlot->course_id,
            'day_of_week' => $timetableSlot->day_of_week->value,
            'period_no' => $timetableSlot->period_no,
            'start_time' => $timetableSlot->start_time,
            'end_time' => $timetableSlot->end_time,
            'status' => $timetableSlot->status->value,
        ];
    }
}
