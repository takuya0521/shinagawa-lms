<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// アプリケーションがメンテナンス中かを確認する。
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Composerのオートローダーを登録する。
require __DIR__.'/../vendor/autoload.php';

// Laravelを起動し、受信したリクエストを処理する。
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
