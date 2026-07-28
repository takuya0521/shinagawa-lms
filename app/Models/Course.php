<?php

namespace App\Models;

use App\Enums\MasterStatus;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'subject_id',
    'class_group_id',
    'grade',
    'teacher_id',
    'course_name',
    'academic_year',
    'google_classroom_url',
    'google_classroom_id',
    'status',
])]
final class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory, SoftDeletes;

    /**
     * 授業の科目を返す。
     *
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * 授業対象のクラスを返す。
     *
     * @return BelongsTo<ClassGroup, $this>
     */
    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    /**
     * 授業の担当講師を返す。
     *
     * @return BelongsTo<Teacher, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * 授業に設定された時間割枠を返す。
     *
     * @return HasMany<TimetableSlot, $this>
     */
    public function timetableSlots(): HasMany
    {
        return $this->hasMany(TimetableSlot::class);
    }

    /**
     * 有効な授業だけへ絞り込む。
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            'status',
            MasterStatus::Active->value,
        );
    }

    /**
     * 指定された年度・学年・クラスの授業へ絞り込む。
     */
    public function scopeForTarget(
        Builder $query,
        int $academicYear,
        string $grade,
        int $classGroupId,
    ): Builder {
        return $query
            ->where('academic_year', $academicYear)
            ->where('grade', $grade)
            ->where('class_group_id', $classGroupId);
    }

    /**
     * モデル属性のキャスト定義を返す。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'academic_year' => 'integer',
            'status' => MasterStatus::class,
            'deleted_at' => 'datetime',
        ];
    }
}
