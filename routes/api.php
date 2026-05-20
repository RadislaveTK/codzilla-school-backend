<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Public\CourseController as PublicCourseController;
use App\Http\Controllers\Api\Public\ApplicationController;
use App\Http\Controllers\Api\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Api\Admin\GroupController;
use App\Http\Controllers\Api\Admin\AttendanceController;
use App\Http\Controllers\Api\Parent\ChildController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Публичные маршруты
    Route::prefix('public')->group(function () {
        Route::get('/courses', [PublicCourseController::class, 'index']);
        Route::get('/courses/{slug}', [PublicCourseController::class, 'show']);
        Route::get('/courses/{slug}/lessons', [PublicCourseController::class, 'lessons']);
        Route::post('/applications', [ApplicationController::class, 'store']);
    });

    // Авторизация
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Защищённые маршруты
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Админ-панель
        Route::middleware('admin')->prefix('admin')->group(function () {

            // Курсы
            Route::get('/courses/icons', [AdminCourseController::class, 'getIcons']);
            Route::apiResource('courses', AdminCourseController::class);

            // Группы
            Route::apiResource('groups', GroupController::class);
            Route::post('/groups/{group}/add-student', [GroupController::class, 'addStudent']);
            Route::delete('/groups/{group}/remove-student/{student}', [GroupController::class, 'removeStudent']);
            Route::get('/groups/{group}/students', [GroupController::class, 'students']);

            // Посещаемость
            Route::get('/attendance/schedule/{schedule}', [AttendanceController::class, 'getScheduleForMarking']);
            Route::post('/attendance/schedule/{schedule}/mark', [AttendanceController::class, 'markAttendance']);
            Route::get('/attendance/student/{student}', [AttendanceController::class, 'studentHistory']);

        });

        // Личный кабинет родителя
        Route::middleware('parent')->prefix('parent')->group(function () {
            Route::get('/children', [ChildController::class, 'index']);
            Route::get('/children/{student}', [ChildController::class, 'show']);
            Route::get('/children/{student}/attendance', [ChildController::class, 'attendance']);
        });

    });
});


