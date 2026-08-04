<?php

namespace App\Actions\Interview;

use App\Models\InterviewRecord;
use App\Models\User;
use App\Services\OperationLogWriter;
use Illuminate\Support\Facades\DB;

final class CreateInterviewRecordAction
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
     * 面談記録を登録し、操作ログへ記録する。
     *
     * @param  array<string, mixed>  $attributes
     * @param  User  $user  対象ユーザー
     * @param  ?string  $ipAddress  操作元IPアドレス
     * @return InterviewRecord 処理結果
     */
    public function execute(
        array $attributes,
        User $user,
        ?string $ipAddress,
    ): InterviewRecord {
        return DB::transaction(
            function () use (
                $attributes,
                $user,
                $ipAddress,
            ): InterviewRecord {
                $interviewRecord = InterviewRecord::query()->create([
                    ...$attributes,
                    'created_by' => $user->id,
                    'updated_by' => null,
                ]);

                $this->operationLogWriter->write(
                    actor: $user,
                    action: 'create_interview',
                    target: $interviewRecord,
                    detail: ['after' => self::snapshot($interviewRecord)],
                    ipAddress: $ipAddress,
                );

                return $interviewRecord->refresh();
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
