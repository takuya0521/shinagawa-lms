<?php

namespace App\Actions\Admin;

use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateUserAction
{
    /**
     * ユーザーと必要な関連情報を登録する。
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data): User {
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
                    'subject_notes' => null,
                    'status' => MasterStatus::Active,
                ]);
            }

            return $user->load([
                'student.classGroup',
                'teacher',
            ]);
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

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== ''
            ? $value
            : null;
    }
}
