<?php

namespace App\Actions\Attendance;

use App\Enums\LessonStatus;
use App\Models\LessonSession;
use App\Models\TimetableSlot;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EnsureLessonSessionAction
{
    /**
     * 時間割枠と日付から授業実施日を取得し、未作成の場合は生成する。
     *
     * @param  TimetableSlot  $timetableSlot  対象時間割
     * @param  CarbonInterface  $lessonDate  授業日
     * @param  User  $createdBy  作成者
     * @return LessonSession 処理結果
     */
    public function execute(
        TimetableSlot $timetableSlot,
        CarbonInterface $lessonDate,
        User $createdBy,
    ): LessonSession {
        if (
            $lessonDate->dayOfWeekIso
            !== $timetableSlot->day_of_week->value
        ) {
            throw ValidationException::withMessages([
                'lesson_date' => '選択した日付の曜日が、時間割枠の曜日と一致しません。',
            ]);
        }

        $lessonDateValue = $lessonDate->format('Y-m-d');

        try {
            return DB::transaction(
                static fn (): LessonSession => LessonSession::query()
                    ->firstOrCreate(
                        [
                            'timetable_slot_id' => $timetableSlot->id,
                            'lesson_date' => $lessonDateValue,
                        ],
                        [
                            'status' => LessonStatus::Scheduled->value,
                            'created_by' => $createdBy->id,
                        ],
                    ),
            );
        } catch (QueryException $exception) {
            $lessonSession = LessonSession::query()
                ->where(
                    'timetable_slot_id',
                    $timetableSlot->id,
                )
                ->whereDate(
                    'lesson_date',
                    $lessonDateValue,
                )
                ->first();

            if ($lessonSession !== null) {
                return $lessonSession;
            }

            throw $exception;
        }
    }
}
