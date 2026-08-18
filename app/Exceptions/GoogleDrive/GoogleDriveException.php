<?php

namespace App\Exceptions\GoogleDrive;

use RuntimeException;

/**
 * Google Drive連携で利用者向けの復旧案内へ置き換える必要がある例外の基底クラス。
 *
 * アクセストークンなどの機密値を例外メッセージへ含めず、Controller側で安全な
 * 共通メッセージへ変換できるようにする。
 */
abstract class GoogleDriveException extends RuntimeException {}
