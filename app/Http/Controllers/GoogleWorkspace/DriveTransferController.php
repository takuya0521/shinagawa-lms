<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Exceptions\GoogleDrive\GoogleDriveException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Http\Requests\GoogleWorkspace\DriveUploadRequest;
use App\Services\GoogleDrive\GoogleDriveDownloadService;
use App\Services\GoogleDrive\GoogleDriveMutationService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Google DriveとLMS間のアップロード・ダウンロードを担当する。
 */
final class DriveTransferController extends GoogleWorkspaceController
{
    /**
     * ローカルファイルをGoogle Driveへアップロードする。
     *
     * @param  DriveUploadRequest  $request  検証済みアップロード入力
     * @param  GoogleDriveMutationService  $service  Drive更新処理
     * @return RedirectResponse 一覧画面へのリダイレクト
     */
    public function upload(DriveUploadRequest $request, GoogleDriveMutationService $service): RedirectResponse
    {
        $user = $this->resolveUser($request);

        try {
            $service->upload($user, $request->uploadedFile(), $request->parentId());
        } catch (ConnectionException|RequestException|GoogleDriveException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_drive_file_upload',
                'Google Driveへファイルをアップロードできませんでした。',
            );
        }

        return back()->with('success', 'Google Driveへファイルをアップロードしました。');
    }

    /**
     * DriveファイルをダウンロードまたはGoogle形式からエクスポートする。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $fileId  Google DriveファイルID
     * @param  GoogleDriveDownloadService  $service  Driveダウンロード処理
     * @return StreamedResponse|RedirectResponse ダウンロード応答または詳細画面へのリダイレクト
     */
    public function download(
        Request $request,
        string $fileId,
        GoogleDriveDownloadService $service,
    ): StreamedResponse|RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $download = $service->download($user, $fileId);
        } catch (ConnectionException|RequestException|GoogleDriveException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_drive_file_download',
                'Google Driveのファイルをダウンロードできませんでした。',
            );
        }

        return response()->streamDownload(
            static function () use ($download): void {
                echo $download['body'];
            },
            $download['filename'],
            ['Content-Type' => $download['content_type']],
        );
    }
}
