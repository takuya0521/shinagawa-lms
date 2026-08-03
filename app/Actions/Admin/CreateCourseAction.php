<?php

namespace App\Actions\Admin;

use App\Models\Course;
use App\Models\User;
use App\Services\OperationLogWriter;
use Illuminate\Support\Facades\DB;

final class CreateCourseAction
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
     * 授業を登録する。
     *
     * @param  array<string, mixed>  $attributes
     *
     * @param User $actor 操作を実行するユーザー
     * @param ?string $ipAddress 操作元IPアドレス
     * @return Course 処理結果
     */
    public function execute(
        array $attributes,
        User $actor,
        ?string $ipAddress,
    ): Course {
        return DB::transaction(function () use (
            $attributes,
            $actor,
            $ipAddress,
        ): Course {
            $course = Course::query()->create($attributes);

            $this->operationLogWriter->write(
                actor: $actor,
                action: 'create_course',
                target: $course,
                detail: $this->snapshot($course),
                ipAddress: $ipAddress,
            );

            return $course;
        });
    }

    /**
     * 操作ログへ記録するスナップショットを生成する。
     *
     * @param Course $course 対象授業
     * @return array<string, mixed>
     */
    private function snapshot(Course $course): array
    {
        return [
            'course_name' => $course->course_name,
            'academic_year' => $course->academic_year,
            'grade' => $course->grade->value,
            'subject_id' => $course->subject_id,
            'class_group_id' => $course->class_group_id,
            'teacher_id' => $course->teacher_id,
            'google_classroom_url' => $course->google_classroom_url,
            'google_classroom_id' => $course->google_classroom_id,
            'status' => $course->status->value,
        ];
    }
}
