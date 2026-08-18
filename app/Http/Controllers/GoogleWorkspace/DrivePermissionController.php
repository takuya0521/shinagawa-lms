<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Exceptions\GoogleDrive\GoogleDriveException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Http\Requests\GoogleWorkspace\DrivePermissionRequest;
use App\Services\GoogleDrive\GoogleDrivePermissionService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Google Driveファイルの共有権限追加・削除を担当する。
 */
final class DrivePermissionController extends GoogleWorkspaceController
{
    /**
     * Driveファイルへ共有権限を追加する。
     *
     * @param  DrivePermissionRequest  $request  検証済み共有入力
     * @param  string  $fileId  Google DriveファイルID
     * @param  GoogleDrivePermissionService  $service  Drive共有権限処理
     * @return RedirectResponse 詳細画面へのリダイレクト
     */
    public function store(
        DrivePermissionRequest $request,
        string $fileId,
        GoogleDrivePermissionService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->createPermission(
                $user,
                $fileId,
                $request->email(),
                $request->role(),
                $request->sendsNotification(),
            );
        } catch (ConnectionException|RequestException|GoogleDriveException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_drive_permission_create',
                'Google Driveの共有権限を追加できませんでした。',
            );
        }

        return back()->with('success', 'Google Driveの共有権限を追加しました。');
    }

    /**
     * Driveファイルの共有権限を削除する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $fileId  Google DriveファイルID
     * @param  string  $permissionId  Google Drive権限ID
     * @param  GoogleDrivePermissionService  $service  Drive共有権限処理
     * @return RedirectResponse 詳細画面へのリダイレクト
     */
    public function destroy(
        Request $request,
        string $fileId,
        string $permissionId,
        GoogleDrivePermissionService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->deletePermission($user, $fileId, $permissionId);
        } catch (ConnectionException|RequestException|GoogleDriveException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_drive_permission_delete',
                'Google Driveの共有権限を削除できませんでした。',
            );
        }

        return back()->with('success', 'Google Driveの共有権限を削除しました。');
    }
}
