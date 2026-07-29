<?php

namespace App\Actions\Admin;

use App\Models\Subject;

final class UpdateSubjectAction
{
    /**
     * 科目を更新する。
     *
     * @param  array<string, mixed>  $attributes
     */
    public function execute(
        Subject $subject,
        array $attributes,
    ): Subject {
        $subject->update(
            $attributes,
        );

        return $subject->refresh();
    }
}
