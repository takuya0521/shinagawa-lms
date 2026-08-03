<?php

namespace App\Rules;

use App\Enums\MasterStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Teacher;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;

final class AvailableTeacher implements ValidationRule
{
    /**
     * 必要な依存関係と初期値を受け取って初期化する。
     *
     * @param ?int $currentTeacherId 対象データの識別子
     */
    public function __construct(
        private readonly ?int $currentTeacherId = null,
    ) {}

    /**
     * 選択した教員を授業へ設定できるか検証する。
     *
     * 編集中の授業で現在設定されている教員だけは、
     * 教員情報が無効またはアカウントが利用停止でも保持を許可する。
     *
     * @param string $attribute 検証対象の項目名
     * @param mixed $value 処理対象値
     * @param Closure $fail 検証失敗時の通知処理
     * @return void 戻り値なし
     */
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail,
    ): void {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_numeric($value)) {
            return;
        }

        $teacherId = (int) $value;

        $query = Teacher::query()
            ->whereKey($teacherId)
            ->whereHas(
                'user',
                static function (Builder $userQuery): void {
                    $userQuery->where(
                        'role',
                        UserRole::Teacher->value,
                    );
                },
            );

        if ($teacherId === $this->currentTeacherId) {
            if ($query->exists()) {
                return;
            }

            $fail('現在の担当教員を保持できません。別の教員を選択してください。');

            return;
        }

        $isAvailable = $query
            ->where(
                'status',
                MasterStatus::Active->value,
            )
            ->whereHas(
                'user',
                static function (Builder $userQuery): void {
                    $userQuery->where(
                        'status',
                        UserStatus::Active->value,
                    );
                },
            )
            ->exists();

        if (! $isAvailable) {
            $fail('選択した担当教員は利用できません。');
        }
    }
}
