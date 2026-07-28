<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
     * モデル属性のキャスト定義を返す。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StudentStatus::class,
            'deleted_at' => 'datetime',
        ];
    }
}
