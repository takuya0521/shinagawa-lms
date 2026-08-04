<?php

namespace App\Models;

use App\Enums\Grade;
use App\Enums\MasterStatus;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $subject_id
 * @property int $class_group_id
 * @property Grade $grade
 * @property int|null $teacher_id
 * @property string $course_name
 * @property int $academic_year
 * @property string|null $google_classroom_url
 * @property string|null $google_classroom_id
 * @property MasterStatus $status
 */
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
     * この授業の時間割枠から発生した授業実施日を返す。
     *
     * @return HasManyThrough<LessonSession, TimetableSlot, $this>
     */
    public function lessonSessions(): HasManyThrough
    {
        return $this->hasManyThrough(
            LessonSession::class,
            TimetableSlot::class,
        );
    }

    /**
     * この授業に登録された最終評価を返す。
     *
     * @return HasMany<FinalEvaluation, $this>
     */
    public function finalEvaluations(): HasMany
    {
        return $this->hasMany(FinalEvaluation::class);
    }

    /**
     * 有効な授業だけへ絞り込む。
     *
     * @param  Builder<Course>  $query
     * @return Builder<Course>
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
     *
     * @param  Builder<Course>  $query
     * @param  int  $academicYear  対象年度
     * @param  Grade  $grade  学年
     * @param  int  $classGroupId  対象データの識別子
     * @return Builder<Course>
     */
    public function scopeForTarget(
        Builder $query,
        int $academicYear,
        Grade $grade,
        int $classGroupId,
    ): Builder {
        return $query
            ->where('academic_year', $academicYear)
            ->where('grade', $grade->value)
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
            'grade' => Grade::class,
            'status' => MasterStatus::class,
            'deleted_at' => 'datetime',
        ];
    }
}
