<?php

namespace App\Http\Requests\Admin;

use App\Models\Course;

/**
 * 授業更新時の入力を検証する。
 */
final class UpdateCourseRequest extends BaseCourseRequest
{
    /**
     * ルートへバインドされた更新対象の授業を返す。
     *
     * @return Course 更新対象の授業
     */
    protected function currentCourse(): Course
    {
        /** @var Course $course */
        $course = $this->route('course');

        return $course;
    }
}
