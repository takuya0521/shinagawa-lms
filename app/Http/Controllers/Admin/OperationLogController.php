<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OperationLogIndexRequest;
use App\Models\OperationLog;
use App\Models\User;
use App\Queries\Admin\OperationLogIndexDataQuery;
use App\Services\OperationLogCsvDownloadService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 管理者向け操作ログ画面の表示とCSV出力を制御する。
 */
final class OperationLogController extends Controller
{
    /**
     * 管理者向け操作ログ一覧を表示する。
     *
     * @param OperationLogIndexRequest $request 検証済み検索条件を含むリクエスト
     * @param OperationLogIndexDataQuery $indexDataQuery 一覧画面の表示データ取得処理
     * @return View 操作ログ一覧画面
     */
    public function index(
        OperationLogIndexRequest $request,
        OperationLogIndexDataQuery $indexDataQuery,
    ): View {
        return view(
            'admin.operation-logs.index',
            $indexDataQuery
                ->execute($request->filters())
                ->toViewData(),
        );
    }

    /**
     * 操作ログ詳細を表示する。
     *
     * @param OperationLog $operationLog 表示対象の操作ログ
     * @return View 操作ログ詳細画面
     */
    public function show(
        OperationLog $operationLog,
    ): View {
        $operationLog->loadMissing('user');

        return view('admin.operation-logs.show', [
            'operationLog' => $operationLog,
        ]);
    }

    /**
     * 検索条件に一致する操作ログをCSV出力する。
     *
     * @param OperationLogIndexRequest $request 検証済み検索条件を含むリクエスト
     * @param OperationLogCsvDownloadService $downloadService CSVダウンロード応答の生成処理
     * @return StreamedResponse CSVダウンロード応答
     */
    public function export(
        OperationLogIndexRequest $request,
        OperationLogCsvDownloadService $downloadService,
    ): StreamedResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $downloadService->download(
            $user,
            $request->filters(),
            $request->ip(),
        );
    }
}
