<?php

use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
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

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:admin')
        ->group(function (): void {
            Route::view('/dashboard', 'dashboard.admin')
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
                ]);
        });

    Route::prefix('teacher')
        ->name('teacher.')
        ->middleware('role:teacher')
        ->group(function (): void {
            Route::view('/dashboard', 'dashboard.teacher')
                ->name('dashboard');
        });

    Route::prefix('student')
        ->name('student.')
        ->middleware('role:student')
        ->group(function (): void {
            Route::view('/dashboard', 'dashboard.student')
                ->name('dashboard');
        });
});
