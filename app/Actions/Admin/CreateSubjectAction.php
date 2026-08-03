<?php

namespace App\Actions\Admin;

use App\Models\Subject;
use App\Models\User;
use App\Services\OperationLogWriter;
use Illuminate\Support\Facades\DB;

final class CreateSubjectAction
{
    /**
     * 必要な依存関係と初期値を受け取って初期化する。
     *
     * @param OperationLogWriter $operationLogWriter 操作ログ記録サービス
     */
    public function __construct(
        private readonly OperationLogWriter $operationLogWriter,
    ) {}

    /**
     * 科目を登録する。
     *
     * @param  array<string, mixed>  $attributes
     *
     * @param User $actor 操作を実行するユーザー
     * @param ?string $ipAddress 操作元IPアドレス
     * @return Subject 処理結果
     */
    public function execute(
        array $attributes,
        User $actor,
        ?string $ipAddress,
    ): Subject {
        return DB::transaction(function () use (
            $attributes,
            $actor,
            $ipAddress,
        ): Subject {
            $subject = Subject::query()->create(
                $attributes,
            );

            $this->operationLogWriter->write(
                actor: $actor,
                action: 'create_subject',
                target: $subject,
                detail: $this->snapshot($subject),
                ipAddress: $ipAddress,
            );

            return $subject;
        });
    }

    /**
     * 操作ログへ記録するスナップショットを生成する。
     *
     * @param Subject $subject 対象科目
     * @return array<string, mixed>
     */
    private function snapshot(Subject $subject): array
    {
        return [
            'subject_code' => $subject->subject_code,
            'subject_name' => $subject->subject_name,
            'status' => $subject->status->value,
        ];
    }
}
