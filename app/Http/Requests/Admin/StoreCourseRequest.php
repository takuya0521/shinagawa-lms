<?php

namespace App\Http\Requests\Admin;

use App\Models\Course;

/**
 * 授業登録時の入力を検証する。
 */
final class StoreCourseRequest extends BaseCourseRequest
{
    /**
     * 登録処理では更新対象の授業を持たない。
     *
     * @return Course|null 常にnull
     */
    protected function currentCourse(): ?Course
    {
        return null;
    }
}
