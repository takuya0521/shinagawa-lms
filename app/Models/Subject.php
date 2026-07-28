<?php

namespace App\Models;

use App\Enums\MasterStatus;
use Database\Factories\SubjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'subject_code',
    'subject_name',
    'status',
])]
final class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use HasFactory;

    /**
     * この科目に紐付く授業を返す。
     *
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * 有効な科目だけへ絞り込む。
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            'status',
            MasterStatus::Active->value,
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
            'status' => MasterStatus::class,
        ];
    }
}
