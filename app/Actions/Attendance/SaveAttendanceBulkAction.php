<?php

namespace App\Actions\Attendance;

use App\Data\Attendance\AttendanceBulkSaveResult;
use App\Enums\LessonStatus;
use App\Models\AttendanceRecord;
use App\Models\LessonSession;
use App\Models\User;
use App\Queries\Attendance\TargetStudentQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * 対象生徒全員の出欠を一括保存する。
 */
final class SaveAttendanceBulkAction
{
    /**
     * 出欠一括保存処理を生成する。
     *
     * @param  TargetStudentQuery  $targetStudentQuery  授業の出欠対象生徒を取得するQuery
     */
    public function __construct(
        private readonly TargetStudentQuery $targetStudentQuery,
    ) {}

    /**
     * 対象生徒全員の出欠を一括保存する。
     *
     * @param  LessonSession  $lessonSession  保存対象の授業実施日
     * @param  list<array{student_id: int, attendance_status: string, note: string|null}>  $records  出欠入力一覧
     * @param  User  $user  保存を実行するユーザー
     * @param  bool  $isAdminCorrection  管理者による修正の場合はtrue
     * @return AttendanceBulkSaveResult 保存結果
     *
     * @throws ValidationException 送信された生徒一覧が対象生徒と一致しない場合
     */
    public function execute(
        LessonSession $lessonSession,
        array $records,
        User $user,
        bool $isAdminCorrection,
    ): AttendanceBulkSaveResult {
        return DB::transaction(function () use (
            $lessonSession,
            $records,
            $user,
            $isAdminCorrection,
        ): AttendanceBulkSaveResult {
            $lockedSession = $this->lockLessonSession($lessonSession);
            $this->assertSubmittedStudentsMatch(
                $lockedSession,
                $records,
            );

            $existingRecords = $this->lockExistingRecords($lockedSession);
            $this->saveRecords(
                $lockedSession,
                $existingRecords,
                $records,
                $user,
                $isAdminCorrection,
            );
            $this->completeLessonSession($lockedSession);

            return new AttendanceBulkSaveResult(
                lessonSession: $lockedSession,
                savedCount: count($records),
            );
        });
    }

    /**
     * 授業実施日を排他ロックして返す。
     *
     * @param  LessonSession  $lessonSession  保存対象の授業実施日
     * @return LessonSession 排他ロック済みの授業実施日
     */
    private function lockLessonSession(
        LessonSession $lessonSession,
    ): LessonSession {
        return LessonSession::query()
            ->with('timetableSlot.course')
            ->lockForUpdate()
            ->findOrFail($lessonSession->id);
    }

    /**
     * 送信された生徒一覧が授業の対象生徒一覧と一致することを確認する。
     *
     * @param  LessonSession  $lessonSession  排他ロック済みの授業実施日
     * @param  list<array{student_id: int, attendance_status: string, note: string|null}>  $records  出欠入力一覧
     * @return void 戻り値なし
     *
     * @throws ValidationException 生徒一覧が一致しない場合
     */
    private function assertSubmittedStudentsMatch(
        LessonSession $lessonSession,
        array $records,
    ): void {
        $targetStudentIds = $this->targetStudentQuery
            ->execute($lessonSession->timetableSlot->course)
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        $submittedStudentIds = collect($records)
            ->pluck('student_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($targetStudentIds !== $submittedStudentIds) {
            throw ValidationException::withMessages([
                'records' => '出欠対象の学年またはクラスが一致しません。画面を再読み込みしてください。',
            ]);
        }
    }

    /**
     * 既存の出欠記録を排他ロックして生徒IDごとに返す。
     *
     * @param  LessonSession  $lessonSession  排他ロック済みの授業実施日
     * @return Collection<int, AttendanceRecord> 生徒IDをキーにした出欠記録一覧
     */
    private function lockExistingRecords(
        LessonSession $lessonSession,
    ): Collection {
        return AttendanceRecord::query()
            ->where('lesson_session_id', $lessonSession->id)
            ->lockForUpdate()
            ->get()
            ->keyBy('student_id');
    }

    /**
     * 送信された出欠入力を既存記録へ反映する。
     *
     * @param  LessonSession  $lessonSession  保存対象の授業実施日
     * @param  Collection<int, AttendanceRecord>  $existingRecords  生徒IDをキーにした既存記録
     * @param  list<array{student_id: int, attendance_status: string, note: string|null}>  $records  出欠入力一覧
     * @param  User  $user  保存を実行するユーザー
     * @param  bool  $isAdminCorrection  管理者による修正の場合はtrue
     * @return void 戻り値なし
     */
    private function saveRecords(
        LessonSession $lessonSession,
        Collection $existingRecords,
        array $records,
        User $user,
        bool $isAdminCorrection,
    ): void {
        foreach ($records as $recordInput) {
            $existingRecord = $existingRecords->get(
                $recordInput['student_id'],
            );

            if ($existingRecord instanceof AttendanceRecord) {
                $this->updateRecord(
                    $existingRecord,
                    $recordInput,
                    $user,
                    $isAdminCorrection,
                );

                continue;
            }

            $this->createRecord(
                $lessonSession,
                $recordInput,
                $user,
                $isAdminCorrection,
            );
        }
    }

    /**
     * 既存の出欠記録を更新する。
     *
     * @param  AttendanceRecord  $attendanceRecord  更新対象の出欠記録
     * @param  array{student_id: int, attendance_status: string, note: string|null}  $recordInput  出欠入力
     * @param  User  $user  更新を実行するユーザー
     * @param  bool  $isAdminCorrection  管理者による修正の場合はtrue
     * @return void 戻り値なし
     */
    private function updateRecord(
        AttendanceRecord $attendanceRecord,
        array $recordInput,
        User $user,
        bool $isAdminCorrection,
    ): void {
        $attributes = [
            'attendance_status' => $recordInput['attendance_status'],
            'note' => $recordInput['note'],
        ];

        if ($isAdminCorrection) {
            $attributes['corrected_by'] = $user->id;
        } else {
            $attributes['recorded_by'] = $user->id;
        }

        $attendanceRecord->update($attributes);
    }

    /**
     * 新しい出欠記録を作成する。
     *
     * @param  LessonSession  $lessonSession  保存対象の授業実施日
     * @param  array{student_id: int, attendance_status: string, note: string|null}  $recordInput  出欠入力
     * @param  User  $user  登録を実行するユーザー
     * @param  bool  $isAdminCorrection  管理者による修正の場合はtrue
     * @return void 戻り値なし
     */
    private function createRecord(
        LessonSession $lessonSession,
        array $recordInput,
        User $user,
        bool $isAdminCorrection,
    ): void {
        AttendanceRecord::query()->create([
            'lesson_session_id' => $lessonSession->id,
            'student_id' => $recordInput['student_id'],
            'attendance_status' => $recordInput['attendance_status'],
            'recorded_by' => $user->id,
            'corrected_by' => $isAdminCorrection ? $user->id : null,
            'note' => $recordInput['note'],
        ]);
    }

    /**
     * 休講以外の授業実施日を実施済みへ更新する。
     *
     * @param  LessonSession  $lessonSession  更新対象の授業実施日
     * @return void 戻り値なし
     */
    private function completeLessonSession(
        LessonSession $lessonSession,
    ): void {
        if ($lessonSession->status === LessonStatus::Cancelled) {
            return;
        }

        $lessonSession->update([
            'status' => LessonStatus::Completed->value,
        ]);
    }
}
