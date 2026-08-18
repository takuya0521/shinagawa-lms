<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 外部サービス
    |--------------------------------------------------------------------------
    |
    | Postmark、AWS、Slack、Google Workspaceなど外部サービスの認証情報を設定する。
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google_workspace' => [
        // 既存Drive設定をそのまま利用できるよう、旧環境変数名もフォールバックとして残す。
        'client_id' => env('GOOGLE_WORKSPACE_CLIENT_ID', env('GOOGLE_DRIVE_CLIENT_ID')),
        'client_secret' => env('GOOGLE_WORKSPACE_CLIENT_SECRET', env('GOOGLE_DRIVE_CLIENT_SECRET')),
        'authorization_uri' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_uri' => 'https://oauth2.googleapis.com/token',
        'revoke_uri' => 'https://oauth2.googleapis.com/revoke',

        // LMS内の各Googleサービスで必要となる操作権限を一括して要求する。
        'scopes' => [
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/calendar',
            'https://www.googleapis.com/auth/chat.spaces',
            'https://www.googleapis.com/auth/chat.memberships',
            'https://www.googleapis.com/auth/chat.messages',
            'https://www.googleapis.com/auth/chat.messages.reactions',
            'https://www.googleapis.com/auth/chat.delete',
            'https://www.googleapis.com/auth/meetings.space.created',
            'https://www.googleapis.com/auth/meetings.space.readonly',
            'https://www.googleapis.com/auth/classroom.courses.readonly',
            'https://www.googleapis.com/auth/classroom.coursework.me.readonly',
            'https://www.googleapis.com/auth/classroom.announcements.readonly',
        ],
    ],

    'google_drive' => [
        'files_uri' => 'https://www.googleapis.com/drive/v3/files',
        'upload_uri' => 'https://www.googleapis.com/upload/drive/v3/files',
        'drives_uri' => 'https://www.googleapis.com/drive/v3/drives',
        'scope' => 'https://www.googleapis.com/auth/drive',
        'page_size' => env('GOOGLE_DRIVE_PAGE_SIZE', 30),
        'upload_max_kb' => env('GOOGLE_DRIVE_UPLOAD_MAX_KB', 102400),
    ],

    'google_calendar' => [
        'api_uri' => 'https://www.googleapis.com/calendar/v3',
        'scope' => 'https://www.googleapis.com/auth/calendar',
        'page_size' => env('GOOGLE_CALENDAR_PAGE_SIZE', 50),
        'days_ahead' => env('GOOGLE_CALENDAR_DAYS_AHEAD', 180),
    ],

    'google_meet' => [
        'api_uri' => 'https://meet.googleapis.com/v2',
        'create_scope' => 'https://www.googleapis.com/auth/meetings.space.created',
        'read_scope' => 'https://www.googleapis.com/auth/meetings.space.readonly',
        'page_size' => env('GOOGLE_MEET_PAGE_SIZE', 25),
    ],

    'google_forms' => [
        'api_uri' => 'https://forms.googleapis.com/v1',
        'page_size' => env('GOOGLE_FORMS_PAGE_SIZE', 50),
    ],

    'google_classroom' => [
        'api_uri' => 'https://classroom.googleapis.com/v1',
        'scopes' => [
            'https://www.googleapis.com/auth/classroom.courses.readonly',
            'https://www.googleapis.com/auth/classroom.coursework.me.readonly',
            'https://www.googleapis.com/auth/classroom.announcements.readonly',
        ],
        'page_size' => env('GOOGLE_CLASSROOM_PAGE_SIZE', 50),
    ],

    'google_chat' => [
        'api_uri' => 'https://chat.googleapis.com/v1',
        'upload_uri' => 'https://chat.googleapis.com/upload/v1',
        'spaces_scope' => 'https://www.googleapis.com/auth/chat.spaces',
        'memberships_scope' => 'https://www.googleapis.com/auth/chat.memberships',
        'messages_scope' => 'https://www.googleapis.com/auth/chat.messages',
        'reactions_scope' => 'https://www.googleapis.com/auth/chat.messages.reactions',
        'delete_scope' => 'https://www.googleapis.com/auth/chat.delete',
        'space_page_size' => env('GOOGLE_CHAT_SPACE_PAGE_SIZE', 50),
        'message_page_size' => env('GOOGLE_CHAT_MESSAGE_PAGE_SIZE', 100),
        'upload_max_kb' => env('GOOGLE_CHAT_UPLOAD_MAX_KB', 204800),
    ],

];
