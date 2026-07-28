<?php

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

    Route::view('/admin/dashboard', 'dashboard.admin')
        ->middleware('role:admin')
        ->name('admin.dashboard');

    Route::view('/teacher/dashboard', 'dashboard.teacher')
        ->middleware('role:teacher')
        ->name('teacher.dashboard');

    Route::view('/student/dashboard', 'dashboard.student')
        ->middleware('role:student')
        ->name('student.dashboard');
});
