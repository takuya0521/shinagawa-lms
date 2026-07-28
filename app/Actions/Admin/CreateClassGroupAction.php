<?php

namespace App\Actions\Admin;

use App\Models\ClassGroup;

final class CreateClassGroupAction
{
    /**
     * クラスグループを登録する。
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): ClassGroup
    {
        return ClassGroup::query()->create([
            'class_code' => $data['class_code'],
            'class_name' => $data['class_name'],
            'description' => $this->nullableString(
                $data['description'] ?? null,
            ),
            'status' => $data['status'],
        ]);
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== ''
            ? $value
            : null;
    }
}
