<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/*
|--------------------------------------------------------------------------
| Public Home Page (accessible without authentication)
|--------------------------------------------------------------------------
*/
Volt::route('/', 'home.index')->name('home');

/*
|--------------------------------------------------------------------------
| Authentication (guest only)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Volt::route('/login',    'auth.login')->name('login');
    Volt::route('/register', 'auth.register')->name('register');
});

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/
Route::get('/logout', function () {
    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect('/login');
})->name('logout');

/*
|--------------------------------------------------------------------------
| Language switcher
|--------------------------------------------------------------------------
*/
Route::get('/language/{locale}', function ($locale) {
    if (in_array($locale, ['fr', 'ar', 'en'])) {
        session(['locale' => $locale]);
        if (auth()->check()) {
            auth()->user()->update(['language' => $locale]);
        }
    }
    return back();
})->name('language.switch');

/*
|--------------------------------------------------------------------------
| Protected routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // Dashboard & shared
    Volt::route('/dashboard', 'dashboard.index')->name('dashboard');
    Volt::route('/profile',   'profile.index')->name('profile');
    Volt::route('/messages',  'messages.index')->name('messages');

    /*
    |--------------------------------------------------------------------------
    | Admin routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:admin')->prefix('admin')->name('admin.')->group(function () {
        Volt::route('/users',          'admin.users.index')->name('users');
        Volt::route('/students',       'admin.students.index')->name('students');
        Volt::route('/teachers',       'admin.teachers.index')->name('teachers');
        Volt::route('/parents',        'admin.parents.index')->name('parents');
        Volt::route('/academic-years', 'admin.academic-years.index')->name('academic-years');
        Volt::route('/levels',         'admin.levels.index')->name('levels');
        Volt::route('/grades',         'admin.grades.index')->name('grades');
        Volt::route('/subjects',       'admin.subjects.index')->name('subjects');
        Volt::route('/courses',                  'admin.courses.index')->name('courses');
        Volt::route('/courses/{course}/manage', 'admin.courses.manage')->name('courses.manage');
        Volt::route('/live-classes',   'admin.live-classes.index')->name('live-classes');
        Volt::route('/payments',       'admin.payments.index')->name('payments');
        Volt::route('/subscriptions',  'admin.subscriptions.index')->name('subscriptions');
        Volt::route('/plans',          'admin.plans.index')->name('plans');
        Volt::route('/announcements',  'admin.announcements.index')->name('announcements');
        Volt::route('/reports',        'admin.reports.index')->name('reports');
        Volt::route('/islamic-api',    'admin.islamic-api.index')->name('islamic-api');
        Volt::route('/islamic/hijri',  'admin.islamic.hijri')->name('islamic.hijri');
        Volt::route('/islamic/quran',  'admin.islamic.quran')->name('islamic.quran');
        Volt::route('/islamic/hadith', 'admin.islamic.hadith')->name('islamic.hadith');
        Volt::route('/islamic/asma',   'admin.islamic.asma')->name('islamic.asma');
        Volt::route('/islamic/duas',   'admin.islamic.duas')->name('islamic.duas');
        Volt::route('/islamic/prayer', 'admin.islamic.prayer')->name('islamic.prayer');
        Volt::route('/islamic/qibla',  'admin.islamic.qibla')->name('islamic.qibla');
        Volt::route('/islamic/books',             'admin.islamic.books')->name('islamic.books');
        Volt::route('/islamic/courses',           'admin.islamic.courses.index')->name('islamic.courses');
        Volt::route('/islamic/courses/{course}/manage', 'admin.islamic.courses.manage')->name('islamic.courses.manage');
    });

    /*
    |--------------------------------------------------------------------------
    | Teacher routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:teacher')->prefix('teacher')->name('teacher.')->group(function () {
        Volt::route('/courses',     'courses.index')->name('courses');
        Volt::route('/students',    'teacher.students.index')->name('students');
        Volt::route('/live-classes','teacher.live-classes.index')->name('live-classes');
        Volt::route('/assignments', 'teacher.assignments.index')->name('assignments');
        Volt::route('/quizzes',     'teacher.quizzes.index')->name('quizzes');
        Volt::route('/attendance',  'teacher.attendance.index')->name('attendance');
    });

    /*
    |--------------------------------------------------------------------------
    | Student routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:student')->prefix('student')->name('student.')->group(function () {
        Volt::route('/courses',                              'courses.index')->name('courses');
        Volt::route('/courses/{course}',                     'student.courses.show')->name('courses.show');
        Volt::route('/courses/{course}/lessons/{lesson}',    'student.lessons.show')->name('lessons.show');
        Volt::route('/live-classes',                         'student.live-classes.index')->name('live-classes');
        Volt::route('/assignments',      'student.assignments.index')->name('assignments');
        Volt::route('/quizzes',          'student.quizzes.index')->name('quizzes');
        Volt::route('/progress',         'student.progress.index')->name('progress');
    });

    /*
    |--------------------------------------------------------------------------
    | Parent routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:parent')->prefix('parent')->name('parent.')->group(function () {
        Volt::route('/children',           'parent.children.index')->name('children');
        Volt::route('/progress',           'parent.progress.index')->name('progress');
        Volt::route('/schedule',           'parent.schedule.index')->name('schedule');
        Volt::route('/payments',           'parent.payments.index')->name('payments');
    });

    /*
    |--------------------------------------------------------------------------
    | Shared course viewer
    |--------------------------------------------------------------------------
    */
    Volt::route('/courses',          'courses.index')->name('courses');
    Volt::route('/courses/{course}', 'courses.show')->name('courses.show');
});
