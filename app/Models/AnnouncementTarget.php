<?php

namespace App\Models;

use App\Enums\AnnouncementTargetType;
use Database\Factories\AnnouncementTargetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $announcement_id
 * @property AnnouncementTargetType $target_type
 * @property string|null $target_value
 */
#[Fillable([
    'announcement_id',
    'target_type',
    'target_value',
])]
final class AnnouncementTarget extends Model
{
    /** @use HasFactory<AnnouncementTargetFactory> */
    use HasFactory;

    /**
     * 公開対象のお知らせを返す。
     *
     * @return BelongsTo<Announcement, $this>
     */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    /**
     * モデル属性のキャスト定義を返す。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_type' => AnnouncementTargetType::class,
        ];
    }
}
