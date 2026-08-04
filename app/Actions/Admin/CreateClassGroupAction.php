<?php

namespace App\Actions\Admin;

use App\Models\ClassGroup;
use App\Models\User;
use App\Services\OperationLogWriter;
use Illuminate\Support\Facades\DB;

final class CreateClassGroupAction
{
    /**
     * 必要な依存関係と初期値を受け取って初期化する。
     *
     * @param  OperationLogWriter  $operationLogWriter  操作ログ記録サービス
     */
    public function __construct(
        private readonly OperationLogWriter $operationLogWriter,
    ) {}

    /**
     * クラスグループを登録する。
     *
     * @param  array<string, mixed>  $data
     * @param  User  $actor  操作を実行するユーザー
     * @param  ?string  $ipAddress  操作元IPアドレス
     * @return ClassGroup 処理結果
     */
    public function execute(
        array $data,
        User $actor,
        ?string $ipAddress,
    ): ClassGroup {
        return DB::transaction(function () use (
            $data,
            $actor,
            $ipAddress,
        ): ClassGroup {
            $classGroup = ClassGroup::query()->create([
                'class_code' => $data['class_code'],
                'class_name' => $data['class_name'],
                'description' => $this->nullableString(
                    $data['description'] ?? null,
                ),
                'status' => $data['status'],
            ]);

            $this->operationLogWriter->write(
                actor: $actor,
                action: 'create_class_group',
                target: $classGroup,
                detail: $this->snapshot($classGroup),
                ipAddress: $ipAddress,
            );

            return $classGroup;
        });
    }

    /**
     * 操作ログへ記録するスナップショットを生成する。
     *
     * @param  ClassGroup  $classGroup  対象クラス
     * @return array<string, mixed>
     */
    private function snapshot(ClassGroup $classGroup): array
    {
        return [
            'class_code' => $classGroup->class_code,
            'class_name' => $classGroup->class_name,
            'description' => $classGroup->description,
            'status' => $classGroup->status->value,
        ];
    }

    /**
     * 指定値を空文字を除外した文字列として取得する。
     *
     * @param  mixed  $value  処理対象値
     * @return ?string 取得した文字列。未指定時はnull
     */
    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== ''
            ? $value
            : null;
    }
}
