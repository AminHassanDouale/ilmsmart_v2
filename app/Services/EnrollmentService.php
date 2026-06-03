<?php

namespace App\Services;

use App\Models\{Course, Enrollment, Student};
use App\Notifications\CourseEnrollmentNotification;

class EnrollmentService
{
    /**
     * Enroll a student in a course.
     */
    public function enroll(Student $student, Course $course): Enrollment
    {
        $existing = Enrollment::where('student_id', $student->id)
                              ->where('course_id', $course->id)
                              ->first();

        if ($existing) {
            if ($existing->status === 'cancelled') {
                $existing->update(['status' => 'active']);
            }
            return $existing;
        }

        $enrollment = Enrollment::create([
            'student_id'       => $student->id,
            'course_id'        => $course->id,
            'enrolled_at'      => now(),
            'progress_percent' => 0,
            'status'           => 'active',
        ]);

        // Notify the student (mail + database)
        if ($student->user) {
            $student->user->notify(new CourseEnrollmentNotification($course));
        }

        return $enrollment;
    }

    /**
     * Unenroll a student from a course.
     */
    public function unenroll(Student $student, Course $course): void
    {
        Enrollment::where('student_id', $student->id)
                  ->where('course_id', $course->id)
                  ->update(['status' => 'cancelled']);
    }

    /**
     * Check if a student is enrolled in a course.
     */
    public function isEnrolled(Student $student, Course $course): bool
    {
        return Enrollment::where('student_id', $student->id)
                         ->where('course_id', $course->id)
                         ->where('status', 'active')
                         ->exists();
    }
}
