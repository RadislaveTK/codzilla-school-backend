<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Public\CourseController as PublicCourseController;
use App\Http\Controllers\Api\Public\ApplicationController;
use App\Http\Controllers\Api\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Api\Admin\GroupController;
use App\Http\Controllers\Api\Admin\AttendanceController;
use App\Http\Controllers\Api\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Api\Admin\NotificationSettingController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Parent\ChildController;
use App\Http\Controllers\Api\Public\StatisticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public routes
    Route::prefix('public')->group(function () {
        Route::get('/courses', [PublicCourseController::class, 'index']);
        Route::get('/courses/{slug}', [PublicCourseController::class, 'show']);
        Route::get('/courses/{slug}/lessons', [PublicCourseController::class, 'lessons']);
        Route::post('/applications', [ApplicationController::class, 'store']);
        Route::post('/feedback', [ApplicationController::class, 'feedback']);
        Route::get('/statistics', [StatisticsController::class, 'index']);
    });

    // Auth
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Admin panel
        Route::middleware('admin')->prefix('admin')->group(function () {

            // Courses
            Route::get('/courses/icons', [AdminCourseController::class, 'getIcons']);
            Route::apiResource('courses', AdminCourseController::class);

            // Groups
            Route::apiResource('groups', GroupController::class);
            Route::post('/groups/{group}/add-student', [GroupController::class, 'addStudent']);
            Route::delete('/groups/{group}/remove-student/{student}', [GroupController::class, 'removeStudent']);
            Route::get('/groups/{group}/students', [GroupController::class, 'students']);

            // Users
            Route::apiResource('users', AdminUserController::class);

            // Lessons with group schedule
            Route::get('/lessons', [AdminLessonController::class, 'index']);
            Route::post('/lessons', [AdminLessonController::class, 'store']);
            Route::get('/lessons/{schedule}', [AdminLessonController::class, 'show']);
            Route::put('/lessons/{schedule}', [AdminLessonController::class, 'update']);
            Route::patch('/lessons/{schedule}', [AdminLessonController::class, 'update']);
            Route::delete('/lessons/{schedule}', [AdminLessonController::class, 'destroy']);

            // Notification settings
            Route::get('/notification-settings', [NotificationSettingController::class, 'show']);
            Route::put('/notification-settings', [NotificationSettingController::class, 'update']);

            // Attendance
            Route::get('/attendance/schedule/{schedule}', [AttendanceController::class, 'getScheduleForMarking']);
            Route::post('/attendance/schedule/{schedule}/mark', [AttendanceController::class, 'markAttendance']);
            Route::get('/attendance/student/{student}', [AttendanceController::class, 'studentHistory']);

        });

        // Parent account
        Route::middleware('parent')->prefix('parent')->group(function () {
            Route::get('/children', [ChildController::class, 'index']);
            Route::get('/children/{student}', [ChildController::class, 'show']);
            Route::get('/children/{student}/attendance', [ChildController::class, 'attendance']);
        });

    });
});
