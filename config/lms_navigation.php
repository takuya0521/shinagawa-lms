<?php

return [
    /*
     * ロール別メニューは画面レイアウトから分離して一元管理する。
     *
     * 画面追加・名称変更・ルート変更時は、この設定と routes/web.php を同時に更新する。
     */
    'roles' => [
        'admin' => [
            'workspace' => '管理者ワークスペース',
            'home_route' => 'admin.dashboard',
            'sections' => [
                [
                    'label' => '概要',
                    'items' => [
                        [

                            'label' => 'ダッシュボード',

                            'route' => 'admin.dashboard',

                            'active' => 'admin.dashboard',

                            'icon' => 'home',

                        ],
                    ],
                ],
                [
                    'label' => 'アカウント・学籍',
                    'items' => [
                        [

                            'label' => 'ユーザー管理',

                            'route' => 'admin.users.index',

                            'active' => 'admin.users.*',

                            'icon' => 'users',

                        ],
                        [

                            'label' => '生徒管理',

                            'route' => 'admin.students.index',

                            'active' => 'admin.students.*',

                            'icon' => 'student',

                        ],
                        [

                            'label' => '教員管理',

                            'route' => 'admin.teachers.index',

                            'active' => 'admin.teachers.*',

                            'icon' => 'teacher',

                        ],
                        [

                            'label' => 'クラス管理',

                            'route' => 'admin.class-groups.index',

                            'active' => 'admin.class-groups.*',

                            'icon' => 'class',

                        ],
                    ],
                ],
                [
                    'label' => '授業設定',
                    'items' => [
                        [

                            'label' => '科目管理',

                            'route' => 'admin.subjects.index',

                            'active' => 'admin.subjects.*',

                            'icon' => 'book',

                        ],
                        [

                            'label' => '授業管理',

                            'route' => 'admin.courses.index',

                            'active' => 'admin.courses.*',

                            'icon' => 'assignment',

                        ],
                        [

                            'label' => '担当教員設定',

                            'route' => 'admin.course-teacher-assignments.index',

                            'active' => 'admin.course-teacher-assignments.*',

                            'icon' => 'teacher',

                        ],
                        [

                            'label' => '時間割管理',

                            'route' => 'admin.timetable-slots.index',

                            'active' => 'admin.timetable-slots.*',

                            'icon' => 'calendar',

                        ],
                    ],
                ],
                [
                    'label' => '学校運用',
                    'items' => [
                        [

                            'label' => '出欠管理',

                            'route' => 'admin.attendance.index',

                            'active' => 'admin.attendance.*',

                            'icon' => 'check',

                        ],
                        [

                            'label' => '成績管理',

                            'route' => 'admin.evaluations.index',

                            'active' => 'admin.evaluations.*',

                            'icon' => 'chart',

                        ],
                        [

                            'label' => '面談記録',

                            'route' => 'admin.interviews.index',

                            'active' => 'admin.interviews.*',

                            'icon' => 'chat',

                        ],
                        [

                            'label' => 'お知らせ管理',

                            'route' => 'admin.announcements.index',

                            'active' => 'admin.announcements.*',

                            'icon' => 'bell',

                        ],
                        [

                            'label' => '外部リンク管理',

                            'route' => 'admin.external-links.index',

                            'active' => 'admin.external-links.*',

                            'icon' => 'link',

                        ],
                        [

                            'label' => '操作ログ',

                            'route' => 'admin.operation-logs.index',

                            'active' => 'admin.operation-logs.*',

                            'icon' => 'log',

                        ],
                    ],
                ],
                [
                    'label' => 'Google Workspace',
                    'items' => [
                        [
                            'label' => 'Google Drive',
                            'route' => 'google-workspace.drive.index',
                            'active' => 'google-workspace.drive.*',
                            'icon' => 'google-drive',
                        ],
                        [
                            'label' => 'Google Calendar',
                            'route' => 'google-workspace.calendar.index',
                            'active' => 'google-workspace.calendar.*',
                            'icon' => 'google-calendar',
                        ],
                        [
                            'label' => 'Google Chat',
                            'route' => 'google-workspace.chat.index',
                            'active' => 'google-workspace.chat.*',
                            'icon' => 'google-chat',
                        ],
                        [
                            'label' => 'Google Meet',
                            'route' => 'google-workspace.meet.index',
                            'active' => 'google-workspace.meet.*',
                            'icon' => 'google-meet',
                        ],
                        [
                            'label' => 'Google Forms',
                            'route' => 'google-workspace.forms.index',
                            'active' => 'google-workspace.forms.*',
                            'icon' => 'google-forms',
                        ],
                    ],
                ],
            ],
        ],
        'teacher' => [
            'workspace' => '教員ワークスペース',
            'home_route' => 'teacher.dashboard',
            'sections' => [
                [
                    'label' => '概要',
                    'items' => [
                        [

                            'label' => 'ダッシュボード',

                            'route' => 'teacher.dashboard',

                            'active' => 'teacher.dashboard',

                            'icon' => 'home',

                        ],
                    ],
                ],
                [
                    'label' => '授業運営',
                    'items' => [
                        [

                            'label' => '担当授業',

                            'route' => 'teacher.courses.index',

                            'active' => 'teacher.courses.*',

                            'icon' => 'book',

                        ],
                        [

                            'label' => '出欠登録',

                            'route' => 'teacher.attendance.index',

                            'active' => 'teacher.attendance.*',

                            'icon' => 'check',

                        ],
                        [

                            'label' => '評価入力',

                            'route' => 'teacher.evaluations.index',

                            'active' => 'teacher.evaluations.*',

                            'icon' => 'chart',

                        ],
                    ],
                ],
                [
                    'label' => '生徒支援',
                    'items' => [
                        [

                            'label' => '面談記録',

                            'route' => 'teacher.interviews.index',

                            'active' => 'teacher.interviews.*',

                            'icon' => 'chat',

                        ],
                        [

                            'label' => 'お知らせ',

                            'route' => 'teacher.announcements.index',

                            'active' => 'teacher.announcements.*',

                            'icon' => 'bell',

                        ],
                    ],
                ],
                [
                    'label' => 'Google Workspace',
                    'items' => [
                        [
                            'label' => 'Google Drive',
                            'route' => 'google-workspace.drive.index',
                            'active' => 'google-workspace.drive.*',
                            'icon' => 'google-drive',
                        ],
                        [
                            'label' => 'Google Calendar',
                            'route' => 'google-workspace.calendar.index',
                            'active' => 'google-workspace.calendar.*',
                            'icon' => 'google-calendar',
                        ],
                        [
                            'label' => 'Google Chat',
                            'route' => 'google-workspace.chat.index',
                            'active' => 'google-workspace.chat.*',
                            'icon' => 'google-chat',
                        ],
                        [
                            'label' => 'Google Meet',
                            'route' => 'google-workspace.meet.index',
                            'active' => 'google-workspace.meet.*',
                            'icon' => 'google-meet',
                        ],
                        [
                            'label' => 'Google Forms',
                            'route' => 'google-workspace.forms.index',
                            'active' => 'google-workspace.forms.*',
                            'icon' => 'google-forms',
                        ],
                    ],
                ],
            ],
        ],
        'student' => [
            'workspace' => '生徒ポータル',
            'home_route' => 'student.dashboard',
            'sections' => [
                [
                    'label' => '概要',
                    'items' => [
                        [

                            'label' => 'トップ',

                            'route' => 'student.dashboard',

                            'active' => 'student.dashboard',

                            'icon' => 'home',

                        ],
                    ],
                ],
                [
                    'label' => '学習情報',
                    'items' => [
                        [

                            'label' => '週間時間割',

                            'route' => 'student.timetable.index',

                            'active' => 'student.timetable.*',

                            'icon' => 'calendar',

                        ],
                        [

                            'label' => '成績',

                            'route' => 'student.evaluations.index',

                            'active' => 'student.evaluations.*',

                            'icon' => 'chart',

                        ],
                        [

                            'label' => 'お知らせ',

                            'route' => 'student.announcements.index',

                            'active' => 'student.announcements.*',

                            'icon' => 'bell',

                        ],
                    ],
                ],
                [
                    'label' => '学校サービス',
                    'items' => [
                        [

                            'label' => '年間予定',

                            'route' => 'student.annual-schedule',

                            'active' => 'student.annual-schedule',

                            'icon' => 'calendar',

                        ],
                        [

                            'label' => '面談希望申込',

                            'route' => 'student.interview-request',

                            'active' => 'student.interview-request',

                            'icon' => 'form',

                        ],
                        [

                            'label' => 'Google Drive',

                            'route' => 'google-workspace.drive.index',

                            'active' => 'google-workspace.drive.*',

                            'icon' => 'google-drive',

                        ],
                        [

                            'label' => 'Google Calendar',

                            'route' => 'google-workspace.calendar.index',

                            'active' => 'google-workspace.calendar.*',

                            'icon' => 'google-calendar',

                        ],
                        [

                            'label' => 'Google Chat',

                            'route' => 'google-workspace.chat.index',

                            'active' => 'google-workspace.chat.*',

                            'icon' => 'google-chat',

                        ],
                        [

                            'label' => 'Google Meet',

                            'route' => 'google-workspace.meet.index',

                            'active' => 'google-workspace.meet.*',

                            'icon' => 'google-meet',

                        ],
                        [

                            'label' => 'Google Forms',

                            'route' => 'google-workspace.forms.index',

                            'active' => 'google-workspace.forms.*',

                            'icon' => 'google-forms',

                        ],
                    ],
                ],
            ],
        ],
    ],
];
