<?php

namespace App\Models;

use App\Enums\LessonStatus;
use Database\Factories\LessonSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $timetable_slot_id
 * @property Carbon $lesson_date
 * @property LessonStatus $status
 * @property int|null $created_by
 */
#[Fillable([
    'timetable_slot_id',
    'lesson_date',
    'status',
    'created_by',
])]
final class LessonSession extends Model
{
    /** @use HasFactory<LessonSessionFactory> */
    use HasFactory;

    /**
     * 授業実施日の元となる時間割枠を返す。
     *
     * @return BelongsTo<TimetableSlot, $this>
     */
    public function timetableSlot(): BelongsTo
    {
        return $this->belongsTo(TimetableSlot::class);
    }

    /**
     * この授業実施日に登録された出欠記録を返す。
     *
     * @return HasMany<AttendanceRecord, $this>
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * 授業実施日を作成したユーザーを返す。
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by',
        );
    }

    /**
     * モデル属性のキャスト定義を返す。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lesson_date' => 'date',
            'status' => LessonStatus::class,
        ];
    }
}
