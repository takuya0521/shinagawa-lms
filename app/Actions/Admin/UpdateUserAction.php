<?php

namespace App\Actions\Admin;

use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\OperationLogWriter;
use Illuminate\Support\Facades\DB;

final class UpdateUserAction
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
     * ユーザーと関連情報を更新する。
     *
     * @param  array<string, mixed>  $data
     *
     * @param User $user 対象ユーザー
     * @param User $actor 操作を実行するユーザー
     * @param ?string $ipAddress 操作元IPアドレス
     * @return User 処理結果
     */
    public function execute(
        User $user,
        array $data,
        User $actor,
        ?string $ipAddress,
    ): User {
        return DB::transaction(function () use (
            $user,
            $data,
            $actor,
            $ipAddress,
        ): User {
            $user->load([
                'student.classGroup',
                'teacher',
            ]);

            $before = $this->snapshot($user);

            $userAttributes = [
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $data['role'],
                'status' => $data['status'],
            ];

            if (
                isset($data['password'])
                && is_string($data['password'])
                && $data['password'] !== ''
            ) {
                $userAttributes['password'] = $data['password'];
            }

            $user->update($userAttributes);

            $this->synchronizeStudent(
                user: $user,
                data: $data,
            );

            $this->synchronizeTeacher(
                user: $user,
                data: $data,
            );

            $user
                ->refresh()
                ->load([
                    'student.classGroup',
                    'teacher',
                ]);

            $this->operationLogWriter->write(
                actor: $actor,
                action: 'update_user',
                target: $user,
                detail: [
                    'before' => $before,
                    'after' => $this->snapshot($user),
                    'password_changed' => isset($userAttributes['password']),
                ],
                ipAddress: $ipAddress,
            );

            return $user;
        });
    }

    /**
     * ロールに応じて生徒情報を作成・更新・論理削除する。
     *
     * @param  array<string, mixed>  $data
     *
     * @param User $user 対象ユーザー
     * @return void 戻り値なし
     */
    private function synchronizeStudent(
        User $user,
        array $data,
    ): void {
        $student = Student::withTrashed()
            ->firstOrNew([
                'user_id' => $user->id,
            ]);

        if ($data['role'] !== UserRole::Student->value) {
            if ($student->exists && ! $student->trashed()) {
                $student->delete();
            }

            return;
        }

        if ($student->exists && $student->trashed()) {
            $student->restore();
        }

        $student->fill([
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
        ]);

        $student->save();
    }

    /**
     * ロールに応じて教員情報を作成・更新・論理削除する。
     *
     * @param  array<string, mixed>  $data
     *
     * @param User $user 対象ユーザー
     * @return void 戻り値なし
     */
    private function synchronizeTeacher(
        User $user,
        array $data,
    ): void {
        $teacher = Teacher::withTrashed()
            ->firstOrNew([
                'user_id' => $user->id,
            ]);

        if ($data['role'] !== UserRole::Teacher->value) {
            if ($teacher->exists && ! $teacher->trashed()) {
                // 教員ロールを外す前に、既存授業の担当を未設定へ戻す。
                $teacher->courses()->update([
                    'teacher_id' => null,
                ]);

                $teacher->delete();
            }

            return;
        }

        if ($teacher->exists && $teacher->trashed()) {
            $teacher->restore();
        }

        if (! $teacher->exists) {
            $teacher->fill([
                'subject_notes' => null,
                'status' => MasterStatus::Active->value,
            ]);
        }

        if (array_key_exists('subject_notes', $data)) {
            $teacher->subject_notes = $this->nullableString(
                $data['subject_notes'],
            );
        }

        if (array_key_exists('teacher_status', $data)) {
            $teacher->status = $data['teacher_status'];
        }

        $teacher->save();
    }

    /**
     * パスワードを含めず、監査用のユーザー状態を返す。
     *
     * @param User $user 対象ユーザー
     * @return array<string, mixed>
     */
    private function snapshot(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
            'status' => $user->status->value,
            'student' => $user->student === null
                ? null
                : [
                    'id' => $user->student->id,
                    'student_no' => $user->student->student_no,
                    'student_name' => $user->student->student_name,
                    'grade' => $user->student->grade->value,
                    'class_group_id' => $user->student->class_group_id,
                    'status' => $user->student->status->value,
                ],
            'teacher' => $user->teacher === null
                ? null
                : [
                    'id' => $user->teacher->id,
                    'subject_notes' => $user->teacher->subject_notes,
                    'status' => $user->teacher->status->value,
                ],
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
