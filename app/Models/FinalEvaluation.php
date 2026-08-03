<?php

namespace App\Models;

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationTerm;
use Database\Factories\FinalEvaluationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $student_id
 * @property int $course_id
 * @property int $academic_year
 * @property EvaluationTerm $term_name
 * @property string $submission_score
 * @property string $attendance_score
 * @property string $attitude_score
 * @property string $total_score
 * @property int|null $grade_level
 * @property int $evaluated_by
 * @property EvaluationStatus $status
 */
#[Fillable([
    'student_id',
    'course_id',
    'academic_year',
    'term_name',
    'submission_score',
    'attendance_score',
    'attitude_score',
    'total_score',
    'grade_level',
    'evaluated_by',
    'status',
])]
final class FinalEvaluation extends Model
{
    /** @use HasFactory<FinalEvaluationFactory> */
    use HasFactory;

    /**
     * 評価対象の生徒を返す。
     *
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * 評価対象の授業を返す。
     *
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * 評価を入力したユーザーを返す。
     *
     * @return BelongsTo<User, $this>
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'evaluated_by',
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
            'academic_year' => 'integer',
            'term_name' => EvaluationTerm::class,
            'submission_score' => 'decimal:2',
            'attendance_score' => 'decimal:2',
            'attitude_score' => 'decimal:2',
            'total_score' => 'decimal:2',
            'grade_level' => 'integer',
            'status' => EvaluationStatus::class,
        ];
    }
}
