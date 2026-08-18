<?php

namespace App\Exceptions\GoogleDrive;

/**
 * Google APIの応答がLMS側の必須契約を満たさない場合に送出する例外。
 */
final class GoogleDriveResponseException extends GoogleDriveException {}
