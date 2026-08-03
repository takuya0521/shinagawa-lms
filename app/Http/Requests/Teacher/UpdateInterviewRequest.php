<?php

namespace App\Http\Requests\Teacher;

use App\Http\Requests\BaseInterviewRequest;

/**
 * 教員による面談記録更新時の入力を検証する。
 */
final class UpdateInterviewRequest extends BaseInterviewRequest
{
    /**
     * 教員画面ではログイン教員を使用するため担当教員を入力として受け付けない。
     *
     * @return bool 常にfalse
     */
    protected function acceptsTeacherSelection(): bool
    {
        return false;
    }
}
