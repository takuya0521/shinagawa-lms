<?php

use App\Http\Controllers\Account\PasswordController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\AttendanceEntryController as AdminAttendanceEntryController;
use App\Http\Controllers\Admin\ClassGroupController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\CourseTeacherAssignmentController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EvaluationController as AdminEvaluationController;
use App\Http\Controllers\Admin\EvaluationCorrectionController;
use App\Http\Controllers\Admin\ExternalLinkController;
use App\Http\Controllers\Admin\InterviewController as AdminInterviewController;
use App\Http\Controllers\Admin\OperationLogController;
use App\Http\Controllers\Admin\StudentAttendanceController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\TimetableSlotController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleWorkspace\CalendarAclController as GoogleWorkspaceCalendarAclController;
use App\Http\Controllers\GoogleWorkspace\CalendarController as GoogleWorkspaceCalendarController;
use App\Http\Controllers\GoogleWorkspace\CalendarEventController as GoogleWorkspaceCalendarEventController;
use App\Http\Controllers\GoogleWorkspace\CalendarFreeBusyController as GoogleWorkspaceCalendarFreeBusyController;
use App\Http\Controllers\GoogleWorkspace\ChatAttachmentController as GoogleWorkspaceChatAttachmentController;
use App\Http\Controllers\GoogleWorkspace\ChatController as GoogleWorkspaceChatController;
use App\Http\Controllers\GoogleWorkspace\ChatMemberController as GoogleWorkspaceChatMemberController;
use App\Http\Controllers\GoogleWorkspace\ChatMessageController as GoogleWorkspaceChatMessageController;
use App\Http\Controllers\GoogleWorkspace\ChatPinController as GoogleWorkspaceChatPinController;
use App\Http\Controllers\GoogleWorkspace\ChatReactionController as GoogleWorkspaceChatReactionController;
use App\Http\Controllers\GoogleWorkspace\ChatSpaceController as GoogleWorkspaceChatSpaceController;
use App\Http\Controllers\GoogleWorkspace\ClassroomController as GoogleWorkspaceClassroomController;
use App\Http\Controllers\GoogleWorkspace\DriveCommentController as GoogleWorkspaceDriveCommentController;
use App\Http\Controllers\GoogleWorkspace\DriveController as GoogleWorkspaceDriveController;
use App\Http\Controllers\GoogleWorkspace\DriveItemController as GoogleWorkspaceDriveItemController;
use App\Http\Controllers\GoogleWorkspace\DrivePermissionController as GoogleWorkspaceDrivePermissionController;
use App\Http\Controllers\GoogleWorkspace\DriveTransferController as GoogleWorkspaceDriveTransferController;
use App\Http\Controllers\GoogleWorkspace\FormsController as GoogleWorkspaceFormsController;
use App\Http\Controllers\GoogleWorkspace\MeetController as GoogleWorkspaceMeetController;
use App\Http\Controllers\GoogleWorkspace\OAuthController as GoogleWorkspaceOAuthController;
use App\Http\Controllers\Student\AnnouncementController as StudentAnnouncementController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\EvaluationController as StudentEvaluationController;
use App\Http\Controllers\Student\ExternalServiceController;
use App\Http\Controllers\Student\TimetableController as StudentTimetableController;
use App\Http\Controllers\Teacher\AnnouncementController as TeacherAnnouncementController;
use App\Http\Controllers\Teacher\AssignedCourseController;
use App\Http\Controllers\Teacher\AttendanceController as TeacherAttendanceController;
use App\Http\Controllers\Teacher\AttendanceEntryController as TeacherAttendanceEntryController;
use App\Http\Controllers\Teacher\CourseStudentController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\EvaluationController as TeacherEvaluationController;
use App\Http\Controllers\Teacher\EvaluationEntryController as TeacherEvaluationEntryController;
use App\Http\Controllers\Teacher\InterviewController as TeacherInterviewController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');

    Route::get(
        '/account/password',
        [PasswordController::class, 'edit'],
    )->name('account.password.edit');

    Route::put(
        '/account/password',
        [PasswordController::class, 'update'],
    )->name('account.password.update');

    /*
     * Google Workspaceはユーザー本人のGoogle権限で操作するため、全ロール共通で提供する。
     * Google側の所有権・共有権限に加え、LMSの認証・利用停止確認を必ず通過させる。
     */
    Route::prefix('google-workspace')
        ->name('google-workspace.')
        ->group(function (): void {
            Route::get('/connect', [GoogleWorkspaceOAuthController::class, 'connect'])
                ->name('connect');
            Route::delete('/connection', [GoogleWorkspaceOAuthController::class, 'disconnect'])
                ->name('disconnect');

            Route::prefix('drive')
                ->name('drive.')
                ->group(function (): void {
                    Route::get(
                        '/',
                        [GoogleWorkspaceDriveController::class, 'index'],
                    )->name('index');
                    Route::get(
                        '/content',
                        [GoogleWorkspaceDriveController::class, 'indexContent'],
                    )->name('content');
                    Route::post(
                        '/items',
                        [GoogleWorkspaceDriveItemController::class, 'store'],
                    )->name('store');
                    Route::post(
                        '/uploads',
                        [GoogleWorkspaceDriveTransferController::class, 'upload'],
                    )->name('upload');
                    Route::get(
                        '/files/{fileId}',
                        [GoogleWorkspaceDriveController::class, 'show'],
                    )->name('show');
                    Route::get(
                        '/files/{fileId}/content',
                        [GoogleWorkspaceDriveController::class, 'showContent'],
                    )->name('show-content');
                    Route::patch(
                        '/files/{fileId}',
                        [GoogleWorkspaceDriveItemController::class, 'update'],
                    )->name('update');
                    Route::post(
                        '/files/{fileId}/copy',
                        [GoogleWorkspaceDriveItemController::class, 'copy'],
                    )->name('copy');
                    Route::post(
                        '/files/{fileId}/trash',
                        [GoogleWorkspaceDriveItemController::class, 'trash'],
                    )->name('trash');
                    Route::post(
                        '/files/{fileId}/restore',
                        [GoogleWorkspaceDriveItemController::class, 'restore'],
                    )->name('restore');
                    Route::delete(
                        '/files/{fileId}',
                        [GoogleWorkspaceDriveItemController::class, 'destroy'],
                    )->name('destroy');
                    Route::get(
                        '/files/{fileId}/download',
                        [GoogleWorkspaceDriveTransferController::class, 'download'],
                    )->name('download');
                    Route::post(
                        '/files/{fileId}/permissions',
                        [GoogleWorkspaceDrivePermissionController::class, 'store'],
                    )->name('permissions.store');
                    Route::delete(
                        '/files/{fileId}/permissions/{permissionId}',
                        [GoogleWorkspaceDrivePermissionController::class, 'destroy'],
                    )->name('permissions.destroy');
                    Route::post(
                        '/files/{fileId}/comments',
                        [GoogleWorkspaceDriveCommentController::class, 'store'],
                    )->name('comments.store');
                    Route::post(
                        '/files/{fileId}/comments/{commentId}/replies',
                        [GoogleWorkspaceDriveCommentController::class, 'storeReply'],
                    )->name('replies.store');
                    Route::delete(
                        '/files/{fileId}/comments/{commentId}',
                        [GoogleWorkspaceDriveCommentController::class, 'destroy'],
                    )->name('comments.destroy');
                });

            Route::prefix('calendar')
                ->name('calendar.')
                ->group(function (): void {
                    Route::get(
                        '/',
                        [GoogleWorkspaceCalendarController::class, 'index'],
                    )->name('index');
                    Route::get(
                        '/content',
                        [GoogleWorkspaceCalendarController::class, 'indexContent'],
                    )->name('content');
                    Route::post(
                        '/calendars',
                        [GoogleWorkspaceCalendarController::class, 'store'],
                    )->name('calendars.store');
                    Route::put(
                        '/calendars/{calendarId}',
                        [GoogleWorkspaceCalendarController::class, 'update'],
                    )->name('calendars.update');
                    Route::delete(
                        '/calendars/{calendarId}',
                        [GoogleWorkspaceCalendarController::class, 'destroy'],
                    )->name('calendars.destroy');
                    Route::post(
                        '/events',
                        [GoogleWorkspaceCalendarEventController::class, 'store'],
                    )->name('events.store');
                    Route::get(
                        '/calendars/{calendarId}/events/{eventId}',
                        [GoogleWorkspaceCalendarEventController::class, 'show'],
                    )->name('events.show');
                    Route::get(
                        '/calendars/{calendarId}/events/{eventId}/content',
                        [GoogleWorkspaceCalendarEventController::class, 'showContent'],
                    )->name('events.show-content');
                    Route::put(
                        '/calendars/{calendarId}/events/{eventId}',
                        [GoogleWorkspaceCalendarEventController::class, 'update'],
                    )->name('events.update');
                    Route::delete(
                        '/calendars/{calendarId}/events/{eventId}',
                        [GoogleWorkspaceCalendarEventController::class, 'destroy'],
                    )->name('events.destroy');
                    Route::post(
                        '/calendars/{calendarId}/acl',
                        [GoogleWorkspaceCalendarAclController::class, 'store'],
                    )->name('acl.store');
                    Route::delete(
                        '/calendars/{calendarId}/acl/{ruleId}',
                        [GoogleWorkspaceCalendarAclController::class, 'destroy'],
                    )->name('acl.destroy');
                    Route::post(
                        '/free-busy',
                        [GoogleWorkspaceCalendarFreeBusyController::class, 'store'],
                    )->name('free-busy');
                });

            Route::prefix('chat')
                ->name('chat.')
                ->group(function (): void {
                    Route::get(
                        '/',
                        [GoogleWorkspaceChatController::class, 'index'],
                    )->name('index');
                    Route::get(
                        '/content',
                        [GoogleWorkspaceChatController::class, 'indexContent'],
                    )->name('content');
                    Route::post(
                        '/spaces',
                        [GoogleWorkspaceChatSpaceController::class, 'store'],
                    )->name('spaces.store');
                    Route::get(
                        '/spaces/{spaceId}',
                        [GoogleWorkspaceChatController::class, 'show'],
                    )->name('show');
                    Route::get(
                        '/spaces/{spaceId}/content',
                        [GoogleWorkspaceChatController::class, 'showContent'],
                    )->name('show-content');
                    Route::patch(
                        '/spaces/{spaceId}',
                        [GoogleWorkspaceChatSpaceController::class, 'update'],
                    )->name('spaces.update');
                    Route::delete(
                        '/spaces/{spaceId}',
                        [GoogleWorkspaceChatSpaceController::class, 'destroy'],
                    )->name('spaces.destroy');
                    Route::post(
                        '/spaces/{spaceId}/members',
                        [GoogleWorkspaceChatMemberController::class, 'store'],
                    )->name('members.store');
                    Route::delete(
                        '/spaces/{spaceId}/members/{membershipId}',
                        [GoogleWorkspaceChatMemberController::class, 'destroy'],
                    )->name('members.destroy');
                    Route::post(
                        '/spaces/{spaceId}/messages',
                        [GoogleWorkspaceChatMessageController::class, 'store'],
                    )->name('messages.store');
                    Route::patch(
                        '/spaces/{spaceId}/messages/{messageId}',
                        [GoogleWorkspaceChatMessageController::class, 'update'],
                    )->name('messages.update');
                    Route::delete(
                        '/spaces/{spaceId}/messages/{messageId}',
                        [GoogleWorkspaceChatMessageController::class, 'destroy'],
                    )->name('messages.destroy');
                    Route::get(
                        '/spaces/{spaceId}/messages/{messageId}/attachments/{attachmentId}',
                        [GoogleWorkspaceChatAttachmentController::class, 'download'],
                    )->name('attachments.download');
                    Route::post(
                        '/spaces/{spaceId}/messages/{messageId}/reactions',
                        [GoogleWorkspaceChatReactionController::class, 'store'],
                    )->name('reactions.store');
                    Route::delete(
                        '/spaces/{spaceId}/messages/{messageId}/reactions/{reactionId}',
                        [GoogleWorkspaceChatReactionController::class, 'destroy'],
                    )->name('reactions.destroy');
                    Route::post(
                        '/spaces/{spaceId}/messages/{messageId}/pin',
                        [GoogleWorkspaceChatPinController::class, 'store'],
                    )->name('messages.pin');
                    Route::delete(
                        '/spaces/{spaceId}/messages/{messageId}/pin',
                        [GoogleWorkspaceChatPinController::class, 'destroy'],
                    )->name('messages.unpin');
                });

            Route::prefix('classroom')
                ->name('classroom.')
                ->group(function (): void {
                    Route::get(
                        '/',
                        [GoogleWorkspaceClassroomController::class, 'index'],
                    )->name('index');
                    Route::get(
                        '/content',
                        [GoogleWorkspaceClassroomController::class, 'indexContent'],
                    )->name('content');
                    Route::get(
                        '/courses/{courseId}',
                        [GoogleWorkspaceClassroomController::class, 'show'],
                    )->name('show');
                    Route::get(
                        '/courses/{courseId}/content',
                        [GoogleWorkspaceClassroomController::class, 'showContent'],
                    )->name('show-content');
                });

            Route::prefix('meet')
                ->name('meet.')
                ->group(function (): void {
                    Route::get('/', [GoogleWorkspaceMeetController::class, 'index'])
                        ->name('index');
                    Route::get('/content', [GoogleWorkspaceMeetController::class, 'indexContent'])
                        ->name('content');
                    Route::post('/spaces', [GoogleWorkspaceMeetController::class, 'store'])
                        ->name('store');
                });

            Route::prefix('forms')
                ->name('forms.')
                ->group(function (): void {
                    Route::get('/', [GoogleWorkspaceFormsController::class, 'index'])
                        ->name('index');
                    Route::get('/content', [GoogleWorkspaceFormsController::class, 'indexContent'])
                        ->name('content');
                    Route::post('/', [GoogleWorkspaceFormsController::class, 'store'])
                        ->name('store');
                    Route::get('/{formId}', [GoogleWorkspaceFormsController::class, 'show'])
                        ->name('show');
                    Route::get('/{formId}/content', [GoogleWorkspaceFormsController::class, 'showContent'])
                        ->name('show-content');
                    Route::patch('/{formId}/publish', [GoogleWorkspaceFormsController::class, 'updatePublish'])
                        ->name('publish.update');
                });
        });

    // 既存Google Cloud設定のリダイレクトURIを変えずに全ロールのOAuth結果を受け取る。
    Route::get(
        '/student/google-drive/callback',
        [GoogleWorkspaceOAuthController::class, 'callback'],
    )->name('student.google-drive.callback');

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:admin')
        ->group(function (): void {
            Route::get('/dashboard', AdminDashboardController::class)
                ->name('dashboard');

            Route::resource('users', UserController::class)
                ->only([
                    'index',
                    'create',
                    'store',
                    'edit',
                    'update',
                ]);

            Route::patch(
                '/users/{user}/status',
                [UserController::class, 'updateStatus'],
            )->name('users.status.update');

            Route::resource('students', StudentController::class)
                ->only([
                    'index',
                    'create',
                    'store',
                    'show',
                    'edit',
                    'update',
                ]);

            Route::get(
                '/students/{student}/attendance',
                [StudentAttendanceController::class, 'show'],
            )->name('students.attendance.show');

            Route::resource('teachers', TeacherController::class)
                ->only([
                    'index',
                    'create',
                    'store',
                    'show',
                    'edit',
                    'update',
                ]);

            Route::resource(
                'class-groups',
                ClassGroupController::class,
            )
                ->parameters([
                    'class-groups' => 'classGroup',
                ])
                ->only([
                    'index',
                    'create',
                    'store',
                    'edit',
                    'update',
                ]);

            Route::resource(
                'subjects',
                SubjectController::class,
            )
                ->only([
                    'index',
                    'create',
                    'store',
                    'edit',
                    'update',
                ]);

            Route::resource(
                'courses',
                CourseController::class,
            )
                ->only([
                    'index',
                    'create',
                    'store',
                    'edit',
                    'update',
                ]);

            Route::get(
                '/course-teacher-assignments',
                [CourseTeacherAssignmentController::class, 'index'],
            )->name('course-teacher-assignments.index');

            Route::patch(
                '/course-teacher-assignments/{course}',
                [CourseTeacherAssignmentController::class, 'update'],
            )->name('course-teacher-assignments.update');

            Route::resource(
                'timetable-slots',
                TimetableSlotController::class,
            )
                ->parameters([
                    'timetable-slots' => 'timetableSlot',
                ])
                ->only([
                    'index',
                    'create',
                    'store',
                    'edit',
                    'update',
                ]);

            Route::get(
                '/evaluations',
                [AdminEvaluationController::class, 'index'],
            )->name('evaluations.index');

            Route::get(
                '/evaluations/{finalEvaluation}/edit',
                [EvaluationCorrectionController::class, 'edit'],
            )->name('evaluations.edit');

            Route::put(
                '/evaluations/{finalEvaluation}',
                [EvaluationCorrectionController::class, 'update'],
            )->name('evaluations.update');

            Route::resource(
                'interviews',
                AdminInterviewController::class,
            )
                ->parameters([
                    'interviews' => 'interviewRecord',
                ])
                ->only([
                    'index',
                    'create',
                    'store',
                    'edit',
                    'update',
                ]);

            Route::resource(
                'announcements',
                AdminAnnouncementController::class,
            )
                ->only([
                    'index',
                    'create',
                    'store',
                    'edit',
                    'update',
                    'destroy',
                ]);

            Route::resource(
                'external-links',
                ExternalLinkController::class,
            )
                ->parameters([
                    'external-links' => 'externalLink',
                ])
                ->only([
                    'index',
                    'create',
                    'store',
                    'edit',
                    'update',
                ]);

            Route::get(
                '/operation-logs/export',
                [OperationLogController::class, 'export'],
            )->name('operation-logs.export');

            Route::get(
                '/operation-logs',
                [OperationLogController::class, 'index'],
            )->name('operation-logs.index');

            Route::get(
                '/operation-logs/{operationLog}',
                [OperationLogController::class, 'show'],
            )->name('operation-logs.show');

            Route::get(
                '/attendance',
                [AdminAttendanceController::class, 'index'],
            )->name('attendance.index');

            Route::get(
                '/attendance/daily',
                [AdminAttendanceEntryController::class, 'edit'],
            )->name('attendance.edit');

            Route::put(
                '/attendance/{lessonSession}',
                [AdminAttendanceEntryController::class, 'update'],
            )->name('attendance.update');
        });

    Route::prefix('teacher')
        ->name('teacher.')
        ->middleware('role:teacher')
        ->group(function (): void {
            Route::get('/dashboard', TeacherDashboardController::class)
                ->name('dashboard');

            Route::get(
                '/courses',
                [AssignedCourseController::class, 'index'],
            )->name('courses.index');

            Route::get(
                '/courses/{course}/students',
                [CourseStudentController::class, 'index'],
            )->name('courses.students.index');

            Route::get(
                '/evaluations',
                [TeacherEvaluationController::class, 'index'],
            )->name('evaluations.index');

            Route::get(
                '/evaluations/entry',
                [TeacherEvaluationEntryController::class, 'edit'],
            )->name('evaluations.entry');

            Route::put(
                '/evaluations/entry',
                [TeacherEvaluationEntryController::class, 'update'],
            )->name('evaluations.save');

            Route::resource(
                'interviews',
                TeacherInterviewController::class,
            )
                ->parameters([
                    'interviews' => 'interviewRecord',
                ])
                ->only([
                    'index',
                    'create',
                    'store',
                    'edit',
                    'update',
                ]);

            Route::get(
                '/announcements',
                [TeacherAnnouncementController::class, 'index'],
            )->name('announcements.index');

            Route::get(
                '/announcements/{announcement}',
                [TeacherAnnouncementController::class, 'show'],
            )->name('announcements.show');

            Route::get(
                '/attendance',
                [TeacherAttendanceController::class, 'index'],
            )->name('attendance.index');

            Route::get(
                '/attendance/entry',
                [TeacherAttendanceEntryController::class, 'edit'],
            )->name('attendance.edit');

            Route::put(
                '/attendance/{lessonSession}',
                [TeacherAttendanceEntryController::class, 'update'],
            )->name('attendance.update');
        });

    Route::prefix('student')
        ->name('student.')
        ->middleware('role:student')
        ->group(function (): void {
            Route::get('/dashboard', StudentDashboardController::class)
                ->name('dashboard');

            Route::get(
                '/timetable',
                [StudentTimetableController::class, 'index'],
            )->name('timetable.index');

            Route::get(
                '/evaluations',
                [StudentEvaluationController::class, 'index'],
            )->name('evaluations.index');

            Route::get(
                '/announcements',
                [StudentAnnouncementController::class, 'index'],
            )->name('announcements.index');

            Route::get(
                '/announcements/{announcement}',
                [StudentAnnouncementController::class, 'show'],
            )->name('announcements.show');

            Route::get(
                '/annual-schedule',
                [ExternalServiceController::class, 'calendar'],
            )->name('annual-schedule');

            Route::get(
                '/interview-request',
                [ExternalServiceController::class, 'interviewForm'],
            )->name('interview-request');

            Route::get(
                '/external-resources',
                [ExternalServiceController::class, 'resources'],
            )->name('external-resources');

            // 旧URLは既存ブックマーク互換のため共通Google Workspace画面へ転送する。
            Route::redirect('/google-drive', '/google-workspace/drive')
                ->name('google-drive.index');

            // 旧画面・既存テストからの導線も共通OAuth処理へ集約する。
            Route::get('/google-drive/connect', [GoogleWorkspaceOAuthController::class, 'connect'])
                ->name('google-drive.connect');
            Route::delete('/google-drive', [GoogleWorkspaceOAuthController::class, 'disconnect'])
                ->name('google-drive.disconnect');

            Route::redirect('/google-calendar', '/google-workspace/calendar')
                ->name('google-calendar.index');

            Route::redirect('/google-chat', '/google-workspace/chat')
                ->name('google-chat.index');
            Route::get('/google-chat/spaces/{spaceId}', [GoogleWorkspaceChatController::class, 'show'])
                ->name('google-chat.show');
        });
});
