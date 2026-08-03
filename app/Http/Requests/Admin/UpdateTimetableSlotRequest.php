<?php

namespace App\Http\Requests\Admin;

use App\Models\TimetableSlot;

/**
 * 時間割更新時の入力を検証する。
 */
final class UpdateTimetableSlotRequest extends BaseTimetableSlotRequest
{
    /**
     * ルートへバインドされた更新対象の時間割枠を返す。
     *
     * @return TimetableSlot 更新対象の時間割枠
     */
    protected function currentTimetableSlot(): TimetableSlot
    {
        /** @var TimetableSlot $timetableSlot */
        $timetableSlot = $this->route('timetableSlot');

        return $timetableSlot;
    }
}
