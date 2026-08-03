<?php

namespace App\Models;

use Database\Factories\OperationLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $target_table
 * @property int|null $target_id
 * @property array<string, mixed>|null $detail
 * @property Carbon $created_at
 * @property-read User|null $user
 */
#[Fillable([
    'user_id',
    'action',
    'target_table',
    'target_id',
    'detail',
])]
final class OperationLog extends Model
{
    /** @use HasFactory<OperationLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * 操作を行ったユーザーを返す。
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * モデル属性のキャスト定義を返す。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'detail' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
