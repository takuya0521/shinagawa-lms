<?php

namespace App\Exceptions\GoogleDrive;

/**
 * 保存済み認証情報ではGoogle Driveへ接続できず、利用者の再連携が必要な場合に送出する例外。
 */
final class GoogleDriveReauthenticationRequiredException extends GoogleDriveException {}
