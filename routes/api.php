<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Mobile & External App Ready
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get('/user', fn(Request $request) => $request->user());
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);

    // Courses
    Route::apiResource('courses', \App\Http\Controllers\Api\CourseController::class)->only(['index','show']);
    Route::get('/courses/{course}/lessons',  [\App\Http\Controllers\Api\CourseController::class, 'lessons']);
    Route::get('/courses/{course}/progress', [\App\Http\Controllers\Api\CourseController::class, 'progress']);
    Route::post('/courses/{course}/enroll',  [\App\Http\Controllers\Api\CourseController::class, 'enroll']);

    // Lessons
    Route::get('/lessons/{lesson}', [\App\Http\Controllers\Api\LessonController::class, 'show']);
    Route::post('/lessons/{lesson}/complete', [\App\Http\Controllers\Api\LessonController::class, 'markComplete']);

    // Quizzes
    Route::apiResource('quizzes', \App\Http\Controllers\Api\QuizController::class)->only(['index','show']);
    Route::post('/quizzes/{quiz}/start',  [\App\Http\Controllers\Api\QuizController::class, 'start']);
    Route::post('/quizzes/{quiz}/submit', [\App\Http\Controllers\Api\QuizController::class, 'submit']);

    // Assignments
    Route::apiResource('assignments', \App\Http\Controllers\Api\AssignmentController::class)->only(['index','show']);
    Route::post('/assignments/{assignment}/submit', [\App\Http\Controllers\Api\AssignmentController::class, 'submit']);

    // Progress
    Route::get('/progress',          [\App\Http\Controllers\Api\ProgressController::class, 'index']);
    Route::get('/progress/{course}', [\App\Http\Controllers\Api\ProgressController::class, 'show']);

    // Notifications
    Route::get('/notifications',               [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read',    [\App\Http\Controllers\Api\NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all',     [\App\Http\Controllers\Api\NotificationController::class, 'markAllRead']);

    // Messages
    Route::get('/conversations',                   [\App\Http\Controllers\Api\MessageController::class, 'conversations']);
    Route::get('/conversations/{id}/messages',     [\App\Http\Controllers\Api\MessageController::class, 'messages']);
    Route::post('/conversations/{id}/messages',    [\App\Http\Controllers\Api\MessageController::class, 'send']);

    // Live Classes
    Route::get('/live-classes',               [\App\Http\Controllers\Api\LiveClassController::class, 'index']);
    Route::get('/live-classes/upcoming',      [\App\Http\Controllers\Api\LiveClassController::class, 'upcoming']);

    // Payments
    Route::get('/payments', [\App\Http\Controllers\Api\PaymentController::class, 'index']);

    // Schedule
    Route::get('/schedule', [\App\Http\Controllers\Api\ScheduleController::class, 'index']);
});

// Public login for API
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
