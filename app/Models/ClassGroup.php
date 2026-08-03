<?php

namespace App\Models;

use App\Enums\MasterStatus;
use Database\Factories\ClassGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property MasterStatus $status
 */
#[Fillable([
    'class_code',
    'class_name',
    'description',
    'status',
])]
final class ClassGroup extends Model
{
    /** @use HasFactory<ClassGroupFactory> */
    use HasFactory;

    /**
     * このクラスに所属する生徒を返す。
     *
     * @return HasMany<Student, $this>
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * このクラスを対象とする授業を返す。
     *
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * 有効なクラスだけへ絞り込む。
     *
     * @param  Builder<ClassGroup>  $query
     * @return Builder<ClassGroup>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            'status',
            MasterStatus::Active->value,
        );
    }

    /**
     * 選択可能なクラスへ絞り込む。
     *
     * 編集中の生徒が無効なクラスへ所属している場合は、
     * 現在の所属クラスだけ選択肢へ残す。
     *
     * @param  Builder<ClassGroup>  $query
     *
     * @param ?int $currentClassGroupId 現在所属しているクラスID
     * @return Builder<ClassGroup>
     */
    public function scopeSelectable(
        Builder $query,
        ?int $currentClassGroupId = null,
    ): Builder {
        return $query->where(
            function (Builder $statusQuery) use (
                $currentClassGroupId,
            ): void {
                $statusQuery->where(
                    'status',
                    MasterStatus::Active->value,
                );

                if ($currentClassGroupId !== null) {
                    $statusQuery->orWhere(
                        $statusQuery
                            ->getModel()
                            ->getQualifiedKeyName(),
                        $currentClassGroupId,
                    );
                }
            },
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
