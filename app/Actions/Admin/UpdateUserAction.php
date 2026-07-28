<?php

namespace App\Actions\Admin;

use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateUserAction
{
    /**
     * ユーザーと関連情報を更新する。
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
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

            return $user
                ->refresh()
                ->load([
                    'student.classGroup',
                    'teacher',
                ]);
        });
    }

    /**
     * ロールに応じて生徒情報を作成・更新・論理削除する。
     *
     * @param  array<string, mixed>  $data
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
     * ロールに応じて教員情報を作成・復元・論理削除する。
     *
     * ユーザー編集では教員固有情報を変更せず、
     * 教員管理画面で変更する。
     *
     * @param  array<string, mixed>  $data
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
                'status' => MasterStatus::Active,
            ]);
        }

        $teacher->save();
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== ''
            ? $value
            : null;
    }
}
