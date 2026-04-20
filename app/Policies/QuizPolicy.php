<?php

namespace App\Policies;

use App\Models\{Quiz, User};

class QuizPolicy
{
    public function viewAny(User $user): bool { return true; }

    public function view(User $user, Quiz $quiz): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->isTeacher() && $user->teacher?->id === $quiz->teacher_id) return true;
        if ($user->isStudent()) {
            $courseId = $quiz->course_id ?? $quiz->lesson?->module->course_id;
            return $user->student?->courses()->where('courses.id', $courseId)->exists();
        }
        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTeacher();
    }

    public function update(User $user, Quiz $quiz): bool
    {
        if ($user->isAdmin()) return true;
        return $user->isTeacher() && $user->teacher?->id === $quiz->teacher_id;
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $this->update($user, $quiz);
    }
}
