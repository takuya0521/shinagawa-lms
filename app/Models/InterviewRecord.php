<?php

namespace App\Models;

use Database\Factories\InterviewRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $student_id
 * @property int|null $teacher_id
 * @property Carbon $interview_date
 * @property string|null $interview_type
 * @property string|null $memo
 * @property string|null $next_action
 * @property string|null $drive_url
 * @property string|null $meet_url
 * @property int $created_by
 * @property int|null $updated_by
 */
#[Fillable([
    'student_id',
    'teacher_id',
    'interview_date',
    'interview_type',
    'memo',
    'next_action',
    'drive_url',
    'meet_url',
    'created_by',
    'updated_by',
])]
final class InterviewRecord extends Model
{
    /** @use HasFactory<InterviewRecordFactory> */
    use HasFactory;

    /**
     * 面談対象の生徒を返す。
     *
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * 面談を担当した教員を返す。
     *
     * @return BelongsTo<Teacher, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * 面談記録を作成したユーザーを返す。
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
     * 面談記録を最後に更新したユーザーを返す。
     *
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by',
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
            'interview_date' => 'date',
        ];
    }
}
