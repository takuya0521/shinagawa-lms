<?php

namespace App\Services;

use App\Models\OperationLog;
use App\Models\User;
use App\Support\OperationLogPresenter;
use JsonException;
use RuntimeException;

final class OperationLogCsvExporter
{
    public const MAX_ROWS = 10000;

    /**
     * 操作ログをExcelで開けるUTF-8 CSVとして出力する。
     *
     * @param  resource  $stream
     * @param  iterable<OperationLog>  $operationLogs
     *
     * @return void 戻り値なし
     * @throws JsonException
     */
    public function write(
        mixed $stream,
        iterable $operationLogs,
    ): void {
        if (! is_resource($stream)) {
            throw new RuntimeException(
                'CSV出力先を開けませんでした。',
            );
        }

        fwrite(
            $stream,
            "\xEF\xBB\xBF",
        );

        fputcsv(
            $stream,
            [
                '操作日時',
                '操作者ID',
                '操作者名',
                'メールアドレス',
                '操作コード',
                '操作名',
                '対象テーブル',
                '対象名',
                '対象ID',
                '詳細JSON',
            ],
            ',',
            '"',
            '',
        );

        foreach ($operationLogs as $operationLog) {
            $user = $operationLog->getRelation('user');
            $actorName = $user instanceof User
                ? $user->name
                : '削除済みユーザー / システム';
            $actorEmail = $user instanceof User
                ? $user->email
                : '';

            fputcsv(
                $stream,
                [
                    $operationLog->created_at
                        ->format('Y-m-d H:i:s'),
                    $operationLog->user_id,
                    $this->safeCell(
                        $actorName,
                    ),
                    $this->safeCell(
                        $actorEmail,
                    ),
                    $this->safeCell(
                        $operationLog->action,
                    ),
                    $this->safeCell(
                        OperationLogPresenter::actionLabel(
                            $operationLog->action,
                        ),
                    ),
                    $this->safeCell(
                        $operationLog->target_table
                            ?? '',
                    ),
                    $this->safeCell(
                        OperationLogPresenter::targetLabel(
                            $operationLog->target_table,
                        ),
                    ),
                    $operationLog->target_id,
                    $this->safeCell(
                        json_encode(
                            $operationLog->detail,
                            JSON_THROW_ON_ERROR
                                | JSON_UNESCAPED_UNICODE
                                | JSON_UNESCAPED_SLASHES,
                        ),
                    ),
                ],
                ',',
                '"',
                '',
            );
        }
    }

    /**
     * Excel数式として解釈され得る値を文字列へ固定する。
     *
     * @param string $value 処理対象値
     * @return string 取得した文字列
     */
    private function safeCell(
        string $value,
    ): string {
        return preg_match(
            '/^[=\-+@\t\r]/u',
            $value,
        ) === 1
            ? "'".$value
            : $value;
    }
}
