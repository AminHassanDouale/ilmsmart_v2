<?php

namespace App\Policies;

use App\Models\{Assignment, User};

class AssignmentPolicy
{
    public function viewAny(User $user): bool { return true; }

    public function view(User $user, Assignment $assignment): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->isTeacher() && $user->teacher?->id === $assignment->teacher_id) return true;
        if ($user->isStudent()) {
            $courseId = $assignment->course_id ?? $assignment->lesson?->module->course_id;
            return $user->student?->courses()->where('courses.id', $courseId)->exists();
        }
        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function update(User $user, Assignment $assignment): bool
    {
        if ($user->isAdmin()) return true;
        return $user->isTeacher() && $user->teacher?->id === $assignment->teacher_id;
    }

    public function delete(User $user, Assignment $assignment): bool
    {
        return $this->update($user, $assignment);
    }
}
