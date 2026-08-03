<?php

namespace App\Models;

use App\Enums\Grade;
use App\Enums\StudentStatus;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Grade $grade
 * @property StudentStatus $status
 */
#[Fillable([
    'user_id',
    'student_no',
    'student_name',
    'grade',
    'affiliation',
    'partner_school',
    'class_group_id',
    'status',
])]
final class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, SoftDeletes;

    /**
     * 生徒に紐付くログインユーザーを返す。
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 生徒が所属するクラスグループを返す。
     *
     * @return BelongsTo<ClassGroup, $this>
     */
    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    /**
     * この生徒の出欠記録を返す。
     *
     * @return HasMany<AttendanceRecord, $this>
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * この生徒に登録された最終評価を返す。
     *
     * @return HasMany<FinalEvaluation, $this>
     */
    public function finalEvaluations(): HasMany
    {
        return $this->hasMany(FinalEvaluation::class);
    }

    /**
     * この生徒に登録された面談記録を返す。
     *
     * @return HasMany<InterviewRecord, $this>
     */
    public function interviewRecords(): HasMany
    {
        return $this->hasMany(InterviewRecord::class);
    }

    /**
     * モデル属性のキャスト定義を返す。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grade' => Grade::class,
            'status' => StudentStatus::class,
            'deleted_at' => 'datetime',
        ];
    }
}
