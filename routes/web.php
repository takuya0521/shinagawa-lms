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
        });
});
