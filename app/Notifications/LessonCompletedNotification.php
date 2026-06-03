<?php

namespace App\Notifications;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LessonCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Lesson $lesson,
        public Course $course,
        public float  $progressPercent = 0
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'lesson_completed',
            'title'        => 'Lesson completed',
            'message'      => 'You completed "' . $this->lesson->title . '"',
            'course_id'    => $this->course->id,
            'course_title' => $this->course->title,
            'lesson_id'    => $this->lesson->id,
            'progress'     => round($this->progressPercent, 1),
            'icon'         => 'o-check-circle',
            'url'          => '/student/courses/' . $this->course->id,
        ];
    }
}
