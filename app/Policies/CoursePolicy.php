<?php

namespace App\Policies;

use App\Models\{Course, User};

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Course $course): bool
    {
        if ($user->isAdmin() || $user->isStudent() || $user->isParent()) return true;
        if ($user->isTeacher()) return $user->teacher?->id === $course->teacher_id;
        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function update(User $user, Course $course): bool
    {
        if ($user->isAdmin()) return true;
        return $user->isTeacher() && $user->teacher?->id === $course->teacher_id;
    }

    public function delete(User $user, Course $course): bool
    {
        if ($user->isAdmin()) return true;
        return $user->isTeacher() && $user->teacher?->id === $course->teacher_id;
    }
}
