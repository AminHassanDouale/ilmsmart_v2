<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Use Tailwind/DaisyUI pagination views
        Paginator::defaultView('pagination::tailwind');
        Paginator::defaultSimpleView('pagination::simple-tailwind');

        // Define gates for role-based middleware
        Gate::define('admin', fn($user) => $user->isAdmin());
        Gate::define('teacher', fn($user) => $user->isTeacher());
        Gate::define('student', fn($user) => $user->isStudent());
        Gate::define('parent', fn($user) => $user->isParent());
        Gate::define('tutor', fn($user) => $user->isTutor());

        // Register policies
        Gate::policy(\App\Models\Course::class, \App\Policies\CoursePolicy::class);
        Gate::policy(\App\Models\Lesson::class, \App\Policies\LessonPolicy::class);
        Gate::policy(\App\Models\Quiz::class, \App\Policies\QuizPolicy::class);
        Gate::policy(\App\Models\Assignment::class, \App\Policies\AssignmentPolicy::class);
    }
}
