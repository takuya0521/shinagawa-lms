<?php

namespace App\Actions\ExternalLink;

use App\Models\ExternalLink;
use App\Models\User;
use App\Services\OperationLogWriter;
use Illuminate\Support\Facades\DB;

final class CreateExternalLinkAction
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
     * 外部リンクを登録し、操作ログを保存する。
     *
     * @param  array<string, mixed>  $attributes
     *
     * @param User $user 対象ユーザー
     * @param ?string $ipAddress 操作元IPアドレス
     * @return ExternalLink 処理結果
     */
    public function execute(
        array $attributes,
        User $user,
        ?string $ipAddress,
    ): ExternalLink {
        return DB::transaction(
            function () use (
                $attributes,
                $user,
                $ipAddress,
            ): ExternalLink {
                $externalLink = ExternalLink::query()->create($attributes);

                $this->operationLogWriter->write(
                    actor: $user,
                    action: 'create_external_link',
                    target: $externalLink,
                    detail: ['link_name' => $externalLink->link_name, 'link_type' => $externalLink->link_type->value, 'scope_type' => $externalLink->scope_type->value, 'scope_id' => $externalLink->scope_id],
                    ipAddress: $ipAddress,
                );

                return $externalLink;
            },
        );
    }
}
