<?php

namespace App\Actions\Admin;

use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\OperationLogWriter;
use Illuminate\Support\Facades\DB;

final class CreateUserAction
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
     * ユーザーと必要な関連情報を登録する。
     *
     * @param  array<string, mixed>  $data
     *
     * @param User $actor 操作を実行するユーザー
     * @param ?string $ipAddress 操作元IPアドレス
     * @return User 処理結果
     */
    public function execute(
        array $data,
        User $actor,
        ?string $ipAddress,
    ): User {
        return DB::transaction(function () use (
            $data,
            $actor,
            $ipAddress,
        ): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'status' => $data['status'],
                'password' => $data['password'],
            ]);

            if ($data['role'] === UserRole::Student->value) {
                Student::query()->create([
                    'user_id' => $user->id,
                    ...$this->studentAttributes($data),
                ]);
            }

            if ($data['role'] === UserRole::Teacher->value) {
                Teacher::query()->create([
                    'user_id' => $user->id,
                    'subject_notes' => $this->nullableString(
                        $data['subject_notes'] ?? null,
                    ),
                    'status' => $data['teacher_status']
                        ?? MasterStatus::Active->value,
                ]);
            }

            $user->load([
                'student.classGroup',
                'teacher',
            ]);

            $this->operationLogWriter->write(
                actor: $actor,
                action: 'create_user',
                target: $user,
                detail: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'status' => $user->status->value,
                    'student_id' => $user->student?->id,
                    'teacher_id' => $user->teacher?->id,
                ],
                ipAddress: $ipAddress,
            );

            return $user;
        });
    }

    /**
     * 生徒情報として保存する値を返す。
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function studentAttributes(array $data): array
    {
        return [
            'student_no' => $this->nullableString(
                $data['student_no'] ?? null,
            ),
            'student_name' => $data['student_name'],
            'grade' => $data['grade'],
            'affiliation' => $this->nullableString(
                $data['affiliation'] ?? null,
            ),
            'partner_school' => $this->nullableString(
                $data['partner_school'] ?? null,
            ),
            'class_group_id' => (int) $data['class_group_id'],
            'status' => $data['student_status'],
        ];
    }

    /**
     * 指定値を空文字を除外した文字列として取得する。
     *
     * @param mixed $value 処理対象値
     * @return ?string 取得した文字列。未指定時はnull
     */
    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== ''
            ? $value
            : null;
    }
}
