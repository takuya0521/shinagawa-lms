<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Exceptions\GoogleDrive\GoogleDriveException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Http\Requests\GoogleWorkspace\DriveCopyRequest;
use App\Http\Requests\GoogleWorkspace\DriveCreateRequest;
use App\Http\Requests\GoogleWorkspace\DriveUpdateRequest;
use App\Services\GoogleDrive\GoogleDriveMutationService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Google Drive項目の作成・更新・複製・削除状態変更を担当する。
 */
final class DriveItemController extends GoogleWorkspaceController
{
    /**
     * フォルダまたはGoogle形式ファイルを作成する。
     *
     * @param  DriveCreateRequest  $request  検証済み作成入力
     * @param  GoogleDriveMutationService  $service  Drive更新処理
     * @return RedirectResponse 一覧画面へのリダイレクト
     */
    public function store(DriveCreateRequest $request, GoogleDriveMutationService $service): RedirectResponse
    {
        $user = $this->resolveUser($request);

        try {
            if ($request->type() === 'folder') {
                $service->createFolder($user, $request->name(), $request->parentId());
            } else {
                $service->createGoogleFile(
                    $user,
                    $request->name(),
                    $request->type(),
                    $request->parentId(),
                );
            }
        } catch (ConnectionException|RequestException|GoogleDriveException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_drive_file_create',
                'Google Driveへ項目を作成できませんでした。入力内容と権限を確認してください。',
            );
        }

        return back()->with('success', 'Google Driveへ項目を作成しました。');
    }

    /**
     * Driveファイルの名称・説明・スター・配置先を更新する。
     *
     * @param  DriveUpdateRequest  $request  検証済み更新入力
     * @param  string  $fileId  Google DriveファイルID
     * @param  GoogleDriveMutationService  $service  Drive更新処理
     * @return RedirectResponse 詳細画面へのリダイレクト
     */
    public function update(
        DriveUpdateRequest $request,
        string $fileId,
        GoogleDriveMutationService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->updateFile($user, $fileId, $request->attributes());
        } catch (ConnectionException|RequestException|GoogleDriveException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_drive_file_update',
                'Google Driveのファイル情報を更新できませんでした。',
            );
        }

        return back()->with('success', 'Google Driveのファイル情報を更新しました。');
    }

    /**
     * Driveファイルを複製する。
     *
     * @param  DriveCopyRequest  $request  検証済み複製入力
     * @param  string  $fileId  Google DriveファイルID
     * @param  GoogleDriveMutationService  $service  Drive更新処理
     * @return RedirectResponse 詳細画面へのリダイレクト
     */
    public function copy(
        DriveCopyRequest $request,
        string $fileId,
        GoogleDriveMutationService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->copy($user, $fileId, $request->name(), $request->parentId());
        } catch (ConnectionException|RequestException|GoogleDriveException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_drive_file_copy',
                'Google Driveのファイルを複製できませんでした。',
            );
        }

        return back()->with('success', 'Google Driveのファイルを複製しました。');
    }

    /**
     * Driveファイルをゴミ箱へ移動する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $fileId  Google DriveファイルID
     * @param  GoogleDriveMutationService  $service  Drive更新処理
     * @return RedirectResponse 一覧画面へのリダイレクト
     */
    public function trash(Request $request, string $fileId, GoogleDriveMutationService $service): RedirectResponse
    {
        return $this->toggleTrash($request, $fileId, $service, true);
    }

    /**
     * Driveファイルをゴミ箱から復元する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $fileId  Google DriveファイルID
     * @param  GoogleDriveMutationService  $service  Drive更新処理
     * @return RedirectResponse 一覧画面へのリダイレクト
     */
    public function restore(Request $request, string $fileId, GoogleDriveMutationService $service): RedirectResponse
    {
        return $this->toggleTrash($request, $fileId, $service, false);
    }

    /**
     * Driveファイルを完全削除する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $fileId  Google DriveファイルID
     * @param  GoogleDriveMutationService  $service  Drive更新処理
     * @return RedirectResponse Drive一覧画面へのリダイレクト
     */
    public function destroy(Request $request, string $fileId, GoogleDriveMutationService $service): RedirectResponse
    {
        $user = $this->resolveUser($request);

        try {
            $service->delete($user, $fileId);
        } catch (ConnectionException|RequestException|GoogleDriveException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_drive_file_delete',
                'Google Driveのファイルを完全削除できませんでした。',
            );
        }

        return redirect()
            ->route('google-workspace.drive.index')
            ->with('success', 'Google Driveのファイルを完全削除しました。');
    }

    /**
     * Drive項目のゴミ箱状態を切り替える。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $fileId  Google DriveファイルID
     * @param  GoogleDriveMutationService  $service  Drive更新処理
     * @param  bool  $trash  ゴミ箱へ移動する場合はtrue
     * @return RedirectResponse 直前画面へのリダイレクト
     */
    private function toggleTrash(
        Request $request,
        string $fileId,
        GoogleDriveMutationService $service,
        bool $trash,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $trash
                ? $service->trash($user, $fileId)
                : $service->restore($user, $fileId);
        } catch (ConnectionException|RequestException|GoogleDriveException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                $trash ? 'google_drive_file_trash' : 'google_drive_file_restore',
                $trash
                    ? 'Google Driveのファイルをゴミ箱へ移動できませんでした。'
                    : 'Google Driveのファイルを復元できませんでした。',
            );
        }

        return back()->with(
            'success',
            $trash
                ? 'Google Driveのファイルをゴミ箱へ移動しました。'
                : 'Google Driveのファイルを復元しました。',
        );
    }
}
