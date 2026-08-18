<?php

namespace App\Exceptions\GoogleDrive;

/**
 * Google Drive APIの必須設定が不足している場合に送出する例外。
 */
final class GoogleDriveConfigurationException extends GoogleDriveException {}
