<?php

namespace App\Exceptions\GoogleWorkspace;

/**
 * 必要な権限または更新用トークンがなく、利用者の再連携が必要な場合に使用する例外。
 */
final class GoogleWorkspaceReauthenticationRequiredException extends GoogleWorkspaceException {}
