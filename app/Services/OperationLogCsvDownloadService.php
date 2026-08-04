<?php

namespace App\Services;

use App\Data\Admin\OperationLogFilters;
use App\Models\OperationLog;
use App\Models\User;
use App\Queries\Admin\OperationLogListQuery;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 検索条件に一致する操作ログのCSVダウンロード応答を生成する。
 */
final class OperationLogCsvDownloadService
{
    /**
     * CSVダウンロード処理を生成する。
     *
     * @param  OperationLogListQuery  $listQuery  操作ログ検索処理
     * @param  OperationLogCsvExporter  $csvExporter  CSV書き込み処理
     * @param  OperationLogWriter  $operationLogWriter  CSV出力の監査ログ記録処理
     */
    public function __construct(
        private readonly OperationLogListQuery $listQuery,
        private readonly OperationLogCsvExporter $csvExporter,
        private readonly OperationLogWriter $operationLogWriter,
    ) {}

    /**
     * 操作ログCSVのダウンロード応答を生成する。
     *
     * @param  User  $actor  CSV出力を実行した管理者
     * @param  OperationLogFilters  $filters  CSV出力へ適用する検索条件
     * @param  string|null  $ipAddress  操作元IPアドレス
     * @return StreamedResponse CSVダウンロード応答
     *
     * @throws ValidationException 出力上限を超えた場合
     */
    public function download(
        User $actor,
        OperationLogFilters $filters,
        ?string $ipAddress,
    ): StreamedResponse {
        $maxId = (int) (OperationLog::query()->max('id') ?? 0);
        $exportQuery = $this->listQuery
            ->build($filters)
            ->where('operation_logs.id', '<=', $maxId);
        $exportCount = (clone $exportQuery)->count();

        if ($exportCount > OperationLogCsvExporter::MAX_ROWS) {
            throw ValidationException::withMessages([
                'export' => sprintf(
                    '出力対象が%d件を超えています。検索条件を絞り込んでください。',
                    OperationLogCsvExporter::MAX_ROWS,
                ),
            ]);
        }

        $this->operationLogWriter->writeForTarget(
            actor: $actor,
            action: 'export_operation_logs',
            targetTable: 'operation_logs',
            targetId: null,
            detail: [
                'filters' => $filters->toAuditData(),
                'export_count' => $exportCount,
                'max_exported_id' => $maxId,
            ],
            ipAddress: $ipAddress,
        );

        return response()->streamDownload(
            function () use ($exportQuery): void {
                $stream = fopen('php://output', 'wb');

                if ($stream === false) {
                    throw new RuntimeException('CSV出力先を開けませんでした。');
                }

                try {
                    $this->csvExporter->write(
                        $stream,
                        $exportQuery
                            ->reorder('operation_logs.id')
                            ->lazyById(500, 'operation_logs.id', 'id'),
                    );
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
            },
            sprintf('operation_logs_%s.csv', now()->format('Ymd_His')),
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache',
            ],
        );
    }
}
