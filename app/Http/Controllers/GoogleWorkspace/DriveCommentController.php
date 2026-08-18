<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Exceptions\GoogleDrive\GoogleDriveException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Http\Requests\GoogleWorkspace\TextContentRequest;
use App\Services\GoogleDrive\GoogleDriveCommentService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Google Driveファイルのコメント・返信追加とコメント削除を担当する。
 */
final class DriveCommentController extends GoogleWorkspaceController
{
    /**
     * Driveファイルへコメントを追加する。
     *
     * @param  TextContentRequest  $request  検証済み本文
     * @param  string  $fileId  Google DriveファイルID
     * @param  GoogleDriveCommentService  $service  Driveコメント処理
     * @return RedirectResponse 詳細画面へのリダイレクト
     */
    public function store(
        TextContentRequest $request,
        string $fileId,
        GoogleDriveCommentService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->createComment($user, $fileId, $request->content());
        } catch (ConnectionException|RequestException|GoogleDriveException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_drive_comment_create',
                'Google Driveへコメントを追加できませんでした。',
            );
        }

        return back()->with('success', 'Google Driveへコメントを追加しました。');
    }

    /**
     * Driveコメントへ返信する。
     *
     * @param  TextContentRequest  $request  検証済み本文
     * @param  string  $fileId  Google DriveファイルID
     * @param  string  $commentId  Google DriveコメントID
     * @param  GoogleDriveCommentService  $service  Driveコメント処理
     * @return RedirectResponse 詳細画面へのリダイレクト
     */
    public function storeReply(
        TextContentRequest $request,
        string $fileId,
        string $commentId,
        GoogleDriveCommentService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->createReply($user, $fileId, $commentId, $request->content());
        } catch (ConnectionException|RequestException|GoogleDriveException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_drive_comment_reply_create',
                'Google Driveへ返信を追加できませんでした。',
            );
        }

        return back()->with('success', 'Google Driveへ返信を追加しました。');
    }

    /**
     * Driveコメントを削除する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $fileId  Google DriveファイルID
     * @param  string  $commentId  Google DriveコメントID
     * @param  GoogleDriveCommentService  $service  Driveコメント処理
     * @return RedirectResponse 詳細画面へのリダイレクト
     */
    public function destroy(
        Request $request,
        string $fileId,
        string $commentId,
        GoogleDriveCommentService $service,
    ): RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $service->deleteComment($user, $fileId, $commentId);
        } catch (ConnectionException|RequestException|GoogleDriveException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_drive_comment_delete',
                'Google Driveのコメントを削除できませんでした。',
            );
        }

        return back()->with('success', 'Google Driveのコメントを削除しました。');
    }
}
