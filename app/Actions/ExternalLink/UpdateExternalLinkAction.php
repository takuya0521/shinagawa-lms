<?php

namespace App\Actions\ExternalLink;

use App\Models\ExternalLink;
use App\Models\User;
use App\Services\OperationLogWriter;
use Illuminate\Support\Facades\DB;

final class UpdateExternalLinkAction
{
    /**
     * 操作ログ記録サービスを受け取る。
     *
     * @param OperationLogWriter $operationLogWriter 操作ログ記録サービス
     */
    public function __construct(
        private readonly OperationLogWriter $operationLogWriter,
    ) {}

    /**
     * 外部リンクを更新し、変更前後を操作ログへ保存する。
     *
     * @param  array<string, mixed>  $attributes
     *
     * @param ExternalLink $externalLink 対象外部リンク
     * @param User $user 対象ユーザー
     * @param ?string $ipAddress 操作元IPアドレス
     * @return ExternalLink 処理結果
     */
    public function execute(
        ExternalLink $externalLink,
        array $attributes,
        User $user,
        ?string $ipAddress,
    ): ExternalLink {
        return DB::transaction(
            function () use (
                $externalLink,
                $attributes,
                $user,
                $ipAddress,
            ): ExternalLink {
                $before = [
                    'link_name' => $externalLink->link_name,
                    'link_type' => $externalLink->link_type->value,
                    'url' => $externalLink->url,
                    'scope_type' => $externalLink->scope_type->value,
                    'scope_id' => $externalLink->scope_id,
                    'display_order' => $externalLink->display_order,
                    'status' => $externalLink->status->value,
                ];

                $externalLink->update($attributes);
                $externalLink->refresh();

                $this->operationLogWriter->write(
                    actor: $user,
                    action: 'update_external_link',
                    target: $externalLink,
                    detail: ['before' => $before, 'after' => ['link_name' => $externalLink->link_name, 'link_type' => $externalLink->link_type->value, 'url' => $externalLink->url, 'scope_type' => $externalLink->scope_type->value, 'scope_id' => $externalLink->scope_id, 'display_order' => $externalLink->display_order, 'status' => $externalLink->status->value]],
                    ipAddress: $ipAddress,
                );

                return $externalLink;
            },
        );
    }
}
