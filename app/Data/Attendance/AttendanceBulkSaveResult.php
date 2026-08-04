<?php

namespace App\Data\Attendance;

use App\Models\LessonSession;

/**
 * 出欠一括保存の結果を保持する。
 */
final readonly class AttendanceBulkSaveResult
{
    /**
     * 出欠一括保存結果を生成する。
     *
     * @param  LessonSession  $lessonSession  保存対象の授業実施日
     * @param  int  $savedCount  保存した出欠記録数
     */
    public function __construct(
        public LessonSession $lessonSession,
        public int $savedCount,
    ) {}
}
