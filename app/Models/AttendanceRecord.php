<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Database\Factories\AttendanceRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $lesson_session_id
 * @property int $student_id
 * @property AttendanceStatus $attendance_status
 * @property int $recorded_by
 * @property int|null $corrected_by
 * @property string|null $note
 */
#[Fillable([
    'lesson_session_id',
    'student_id',
    'attendance_status',
    'recorded_by',
    'corrected_by',
    'note',
])]
final class AttendanceRecord extends Model
{
    /** @use HasFactory<AttendanceRecordFactory> */
    use HasFactory;

    /**
     * 出欠対象の授業実施日を返す。
     *
     * @return BelongsTo<LessonSession, $this>
     */
    public function lessonSession(): BelongsTo
    {
        return $this->belongsTo(LessonSession::class);
    }

    /**
     * 出欠対象の生徒を返す。
     *
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * 出欠を登録したユーザーを返す。
     *
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'recorded_by',
        );
    }

    /**
     * 出欠を修正した管理者を返す。
     *
     * @return BelongsTo<User, $this>
     */
    public function corrector(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'corrected_by',
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
            'attendance_status' => AttendanceStatus::class,
        ];
    }
}
