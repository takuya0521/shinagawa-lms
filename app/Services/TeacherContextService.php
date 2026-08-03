<?php

namespace App\Services;

use App\Enums\MasterStatus;
use App\Models\Course;
use App\Models\Teacher;
use App\Models\TimetableSlot;
use App\Models\User;

final class TeacherContextService
{
    /**
     * ログインユーザーに紐付く有効な教員情報を返す。
     *
     * @param User $user 対象ユーザー
     * @return Teacher 処理結果
     */
    public function resolveTeacher(User $user): Teacher
    {
        $teacher = Teacher::query()
            ->where('user_id', $user->id)
            ->where('status', MasterStatus::Active->value)
            ->first();

        abort_if(
            $teacher === null,
            403,
        );

        return $teacher;
    }

    /**
     * ログイン教員が担当する授業であることを確認する。
     *
     * @param User $user 対象ユーザー
     * @param Course $course 対象授業
     * @return Teacher 処理結果
     */
    public function assertAssignedCourse(
        User $user,
        Course $course,
    ): Teacher {
        $teacher = $this->resolveTeacher($user);

        abort_unless(
            $course->teacher_id === $teacher->id,
            403,
        );

        return $teacher;
    }

    /**
     * ログイン教員が担当する時間割枠であることを確認する。
     *
     * @param User $user 対象ユーザー
     * @param TimetableSlot $timetableSlot 対象時間割
     * @return Teacher 処理結果
     */
    public function assertAssignedSlot(
        User $user,
        TimetableSlot $timetableSlot,
    ): Teacher {
        $timetableSlot->loadMissing('course');

        return $this->assertAssignedCourse(
            $user,
            $timetableSlot->course,
        );
    }
}
