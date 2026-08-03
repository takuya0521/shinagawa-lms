<?php

namespace App\Http\Requests\Admin;

use App\Models\TimetableSlot;

/**
 * 時間割登録時の入力を検証する。
 */
final class StoreTimetableSlotRequest extends BaseTimetableSlotRequest
{
    /**
     * 登録処理では更新対象の時間割枠を持たない。
     *
     * @return TimetableSlot|null 常にnull
     */
    protected function currentTimetableSlot(): ?TimetableSlot
    {
        return null;
    }
}
