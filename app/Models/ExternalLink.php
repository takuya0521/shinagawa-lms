<?php

namespace App\Models;

use App\Enums\ExternalLinkScopeType;
use App\Enums\ExternalLinkType;
use App\Enums\MasterStatus;
use Database\Factories\ExternalLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property ExternalLinkType $link_type
 * @property string $link_name
 * @property string $url
 * @property ExternalLinkScopeType $scope_type
 * @property int|null $scope_id
 * @property int $display_order
 * @property MasterStatus $status
 */
#[Fillable([
    'link_type',
    'link_name',
    'url',
    'scope_type',
    'scope_id',
    'display_order',
    'status',
])]
final class ExternalLink extends Model
{
    /** @use HasFactory<ExternalLinkFactory> */
    use HasFactory;

    /**
     * モデル属性のキャスト定義を返す。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'link_type' => ExternalLinkType::class,
            'scope_type' => ExternalLinkScopeType::class,
            'scope_id' => 'integer',
            'display_order' => 'integer',
            'status' => MasterStatus::class,
        ];
    }
}
