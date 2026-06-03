<?php

namespace App\Services;

use App\Models\{Course, Enrollment, Lesson, LessonProgress, Student};
use App\Notifications\CourseCompletedNotification;
use App\Notifications\LessonCompletedNotification;

class ProgressService
{
    /**
     * Recalculate and update a student's course progress percentage.
     */
    public function updateCourseProgress(Student $student, Course $course): float
    {
        $totalLessons = $course->lessons()->where('status', 'published')->count();

        if ($totalLessons === 0) return 0;

        $completed = LessonProgress::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('completed', true)
            ->count();

        $percent = round(($completed / $totalLessons) * 100, 2);

        $status = $percent >= 100 ? 'completed' : 'active';

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->first();
        $wasAlreadyCompleted = $enrollment?->status === 'completed';

        Enrollment::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->update([
                'progress_percent' => $percent,
                'status'           => $status,
                'completed_at'     => $percent >= 100 ? now() : null,
            ]);

        // Award certificate + notify when crossing 100%
        if ($percent >= 100) {
            $this->issueCertificate($student, $course);

            if (!$wasAlreadyCompleted && $student->user) {
                $student->user->notify(new CourseCompletedNotification($course));
            }
        }

        return $percent;
    }

    /**
     * Mark a lesson as complete for a student.
     */
    public function markLessonComplete(Student $student, int $lessonId, int $courseId): void
    {
        $progress = LessonProgress::where('student_id', $student->id)
            ->where('lesson_id', $lessonId)
            ->first();
        $alreadyCompleted = $progress && $progress->completed;

        LessonProgress::updateOrCreate(
            ['student_id' => $student->id, 'lesson_id' => $lessonId],
            [
                'course_id'          => $courseId,
                'completed'          => true,
                'completion_percent' => 100,
                'completed_at'       => now(),
            ]
        );

        $course = Course::find($courseId);
        if ($course) {
            $percent = $this->updateCourseProgress($student, $course);

            // Only notify on the first completion of this lesson
            if (!$alreadyCompleted && $student->user) {
                $lesson = Lesson::find($lessonId);
                if ($lesson) {
                    $student->user->notify(new LessonCompletedNotification($lesson, $course, $percent));
                }
            }
        }
    }

    /**
     * Issue a certificate if not already exists.
     */
    private function issueCertificate(Student $student, Course $course): void
    {
        \App\Models\Certificate::firstOrCreate(
            ['student_id' => $student->id, 'course_id' => $course->id],
            [
                'certificate_number' => 'CERT-' . strtoupper(uniqid()),
                'template'           => 'default',
                'issued_at'          => now(),
            ]
        );
    }
}
