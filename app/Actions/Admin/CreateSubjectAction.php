<?php

namespace App\Actions\Admin;

use App\Models\Subject;

final class CreateSubjectAction
{
    /**
     * 科目を登録する。
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(array $attributes): Subject
    {
        return Subject::query()->create(
            $attributes,
        );
    }
}
