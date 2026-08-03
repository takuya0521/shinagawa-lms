<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 既定のファイル保存先
    |--------------------------------------------------------------------------
    |
    | ファイル保存先が未指定の場合に使用するディスクを指定する。
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | ファイル保存先
    |--------------------------------------------------------------------------
    |
    | ローカルやクラウドなど、利用可能なファイル保存先を定義する。対応ドライバはlocal、ftp、sftp、s3。
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim((string) env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | シンボリックリンク
    |--------------------------------------------------------------------------
    |
    | storage:link実行時に作成するリンク元とリンク先を指定する。
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
