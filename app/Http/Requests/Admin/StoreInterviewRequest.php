<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseInterviewRequest;

/**
 * 管理者による面談記録登録時の入力を検証する。
 */
final class StoreInterviewRequest extends BaseInterviewRequest
{
    /**
     * 管理者画面では担当教員を入力として受け付ける。
     *
     * @return bool 常にtrue
     */
    protected function acceptsTeacherSelection(): bool
    {
        return true;
    }
}
