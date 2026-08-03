<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use App\Enums\MasterStatus;
use Database\Factories\TimetableSlotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $course_id
 * @property DayOfWeek $day_of_week
 * @property int $period_no
 * @property string|null $start_time
 * @property string|null $end_time
 * @property MasterStatus $status
 */
#[Fillable([
    'course_id',
    'day_of_week',
    'period_no',
    'start_time',
    'end_time',
    'status',
])]
final class TimetableSlot extends Model
{
    /** @use HasFactory<TimetableSlotFactory> */
    use HasFactory;

    /**
     * 時間割枠に設定された授業を返す。
     *
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * この時間割枠から発生した授業実施日を返す。
     *
     * @return HasMany<LessonSession, $this>
     */
    public function lessonSessions(): HasMany
    {
        return $this->hasMany(LessonSession::class);
    }

    /**
     * 有効な時間割枠だけへ絞り込む。
     *
     * @param  Builder<TimetableSlot>  $query
     * @return Builder<TimetableSlot>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            'status',
            MasterStatus::Active->value,
        );
    }

    /**
     * 曜日・時限順へ並べる。
     *
     * @param  Builder<TimetableSlot>  $query
     * @return Builder<TimetableSlot>
     */
    public function scopeTimetableOrder(Builder $query): Builder
    {
        return $query
            ->orderBy('day_of_week')
            ->orderBy('period_no');
    }

    /**
     * モデル属性のキャスト定義を返す。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeek::class,
            'period_no' => 'integer',
            'status' => MasterStatus::class,
        ];
    }
}
