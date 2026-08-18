<?php

namespace App\Http\Controllers\GoogleWorkspace;

use App\Exceptions\GoogleChat\GoogleChatException;
use App\Exceptions\GoogleWorkspace\GoogleWorkspaceException;
use App\Services\GoogleChat\GoogleChatAttachmentService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Google Chat添付ファイルの認証付きダウンロードを担当する。
 */
final class ChatAttachmentController extends GoogleWorkspaceController
{
    /**
     * Google Chat添付を取得し、LMSからダウンロードさせる。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $spaceId  Google ChatスペースID
     * @param  string  $messageId  Google ChatメッセージID
     * @param  string  $attachmentId  Google Chat添付ID
     * @param  GoogleChatAttachmentService  $service  Chat添付処理
     * @return BinaryFileResponse|RedirectResponse 添付ファイルまたは詳細画面へのリダイレクト
     */
    public function download(
        Request $request,
        string $spaceId,
        string $messageId,
        string $attachmentId,
        GoogleChatAttachmentService $service,
    ): BinaryFileResponse|RedirectResponse {
        $user = $this->resolveUser($request);

        try {
            $download = $service->download($user, $spaceId, $messageId, $attachmentId);
        } catch (ConnectionException|RequestException|GoogleChatException|GoogleWorkspaceException $exception) {
            return $this->operationFailure(
                $exception,
                $user,
                'google_chat_attachment_download',
                'Google Chatの添付ファイルをダウンロードできませんでした。',
            );
        }

        return response()
            ->download(
                $download['path'],
                $download['filename'],
                ['Content-Type' => $download['content_type']],
            )
            ->deleteFileAfterSend(true);
    }
}
