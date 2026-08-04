<?php

namespace App\Models;

use App\Enums\AnnouncementNoticeType;
use App\Enums\AnnouncementStatus;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $title
 * @property string $body
 * @property AnnouncementNoticeType $notice_type
 * @property bool $is_important
 * @property AnnouncementStatus $status
 * @property int $created_by
 * @property int|null $updated_by
 */
#[Fillable([
    'title',
    'body',
    'notice_type',
    'is_important',
    'publish_start_at',
    'publish_end_at',
    'status',
    'created_by',
    'updated_by',
])]
final class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory, SoftDeletes;

    /**
     * お知らせの作成者を返す。
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * お知らせの最終更新者を返す。
     *
     * @return BelongsTo<User, $this>
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * お知らせに設定された公開対象を返す。
     *
     * @return HasMany<AnnouncementTarget, $this>
     */
    public function targets(): HasMany
    {
        return $this->hasMany(AnnouncementTarget::class);
    }

    /**
     * 指定日時に公開中のお知らせへ絞り込む。
     *
     * @param  Builder<Announcement>  $query
     * @param  \DateTimeInterface  $dateTime  判定対象日時
     * @return Builder<Announcement>
     */
    public function scopePublishedAt(
        Builder $query,
        \DateTimeInterface $dateTime,
    ): Builder {
        return $query
            ->where('status', AnnouncementStatus::Published->value)
            ->where(
                static function (Builder $startQuery) use ($dateTime): void {
                    $startQuery
                        ->whereNull('publish_start_at')
                        ->orWhere('publish_start_at', '<=', $dateTime);
                },
            )
            ->where(
                static function (Builder $endQuery) use ($dateTime): void {
                    $endQuery
                        ->whereNull('publish_end_at')
                        ->orWhere('publish_end_at', '>=', $dateTime);
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
            'notice_type' => AnnouncementNoticeType::class,
            'is_important' => 'boolean',
            'publish_start_at' => 'datetime',
            'publish_end_at' => 'datetime',
            'status' => AnnouncementStatus::class,
            'deleted_at' => 'datetime',
        ];
    }
}
