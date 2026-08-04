<?php

namespace App\Actions\Interview;

use App\Models\InterviewRecord;
use App\Models\User;
use App\Services\OperationLogWriter;
use Illuminate\Support\Facades\DB;

final class UpdateInterviewRecordAction
{
    /**
     * 操作ログ記録サービスを受け取る。
     *
     * @param  OperationLogWriter  $operationLogWriter  操作ログ記録サービス
     */
    public function __construct(
        private readonly OperationLogWriter $operationLogWriter,
    ) {}

    /**
     * 面談記録を更新し、変更前後を操作ログへ記録する。
     *
     * @param  array<string, mixed>  $attributes
     * @param  InterviewRecord  $interviewRecord  対象面談記録
     * @param  User  $user  対象ユーザー
     * @param  ?string  $ipAddress  操作元IPアドレス
     * @return InterviewRecord 処理結果
     */
    public function execute(
        InterviewRecord $interviewRecord,
        array $attributes,
        User $user,
        ?string $ipAddress,
    ): InterviewRecord {
        return DB::transaction(
            function () use (
                $interviewRecord,
                $attributes,
                $user,
                $ipAddress,
            ): InterviewRecord {
                $lockedRecord = InterviewRecord::query()
                    ->lockForUpdate()
                    ->findOrFail($interviewRecord->id);
                $before = self::snapshot($lockedRecord);

                $lockedRecord->update([
                    ...$attributes,
                    'updated_by' => $user->id,
                ]);
                $lockedRecord->refresh();

                $this->operationLogWriter->write(
                    actor: $user,
                    action: 'update_interview',
                    target: $lockedRecord,
                    detail: ['before' => $before, 'after' => self::snapshot($lockedRecord)],
                    ipAddress: $ipAddress,
                );

                return $lockedRecord;
            },
        );
    }

    /**
     * 操作ログへ保存する面談記録の内容を返す。
     *
     * @param  InterviewRecord  $interviewRecord  対象面談記録
     * @return array<string, mixed>
     */
    private static function snapshot(
        InterviewRecord $interviewRecord,
    ): array {
        return [
            'student_id' => $interviewRecord->student_id,
            'teacher_id' => $interviewRecord->teacher_id,
            'interview_date' => $interviewRecord->interview_date->format('Y-m-d'),
            'interview_type' => $interviewRecord->interview_type,
            'memo' => $interviewRecord->memo,
            'next_action' => $interviewRecord->next_action,
            'drive_url' => $interviewRecord->drive_url,
            'meet_url' => $interviewRecord->meet_url,
        ];
    }
}
