<?php

namespace Database\Seeders;

use App\Enums\MasterStatus;
use App\Models\ClassGroup;
use Illuminate\Database\Seeder;

/**
 * 初期クラスデータを登録するSeeder。
 */
final class ClassGroupSeeder extends Seeder
{
    /**
     * 午前クラスと午後クラスを登録する。
     */
    public function run(): void
    {
        ClassGroup::query()->updateOrCreate(
            [
                'class_code' => 'AM',
            ],
            [
                'class_name' => '午前クラス',
                'description' => '午前の部',
                'status' => MasterStatus::Active,
            ],
        );

        ClassGroup::query()->updateOrCreate(
            [
                'class_code' => 'PM',
            ],
            [
                'class_name' => '午後クラス',
                'description' => '午後の部',
                'status' => MasterStatus::Active,
            ],
        );
    }
}
