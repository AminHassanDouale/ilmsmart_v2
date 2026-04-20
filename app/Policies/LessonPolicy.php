<?php

namespace App\Policies;

use App\Models\{Lesson, User};

class LessonPolicy
{
    public function view(User $user, Lesson $lesson): bool
    {
        if ($user->isAdmin()) return true;
        // Free preview available to all
        if ($lesson->is_free_preview) return true;
        // Teacher owns the course
        if ($user->isTeacher() && $user->teacher?->id === $lesson->module->course->teacher_id) return true;
        // Student enrolled in course
        if ($user->isStudent()) {
            return $user->student?->courses()->where('courses.id', $lesson->module->course_id)->exists();
        }
        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function update(User $user, Lesson $lesson): bool
    {
        if ($user->isAdmin()) return true;
        return $user->isTeacher() && $user->teacher?->id === $lesson->module->course->teacher_id;
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $this->update($user, $lesson);
    }
}
