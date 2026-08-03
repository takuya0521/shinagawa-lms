<?php

return [
    'initial_admin' => [
        'name' => env('INITIAL_ADMIN_NAME'),
        'email' => env('INITIAL_ADMIN_EMAIL'),
        'password' => env('INITIAL_ADMIN_PASSWORD'),
    ],

    // 評価閾値・端数処理は設計上未決のため、環境設定が揃うまで確定を禁止する。
    'evaluation' => [
        'rounding_mode' => env('EVALUATION_ROUNDING_MODE'),
        'grade_thresholds' => [
            5 => env('EVALUATION_GRADE_5_MIN'),
            4 => env('EVALUATION_GRADE_4_MIN'),
            3 => env('EVALUATION_GRADE_3_MIN'),
            2 => env('EVALUATION_GRADE_2_MIN'),
            1 => env('EVALUATION_GRADE_1_MIN'),
        ],
    ],
];
