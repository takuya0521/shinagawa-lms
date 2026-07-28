<?php

namespace App\Actions\Admin;

use App\Models\ClassGroup;

final class UpdateClassGroupAction
{
    /**
     * クラスグループを更新する。
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(
        ClassGroup $classGroup,
        array $data,
    ): ClassGroup {
        $classGroup->update([
            'class_code' => $data['class_code'],
            'class_name' => $data['class_name'],
            'description' => $this->nullableString(
                $data['description'] ?? null,
            ),
            'status' => $data['status'],
        ]);

        return $classGroup->refresh();
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== ''
            ? $value
            : null;
    }
}
