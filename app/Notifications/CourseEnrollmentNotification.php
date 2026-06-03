<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourseEnrollmentNotification extends Notification
{
    use Queueable;

    public function __construct(public Course $course) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/student/courses/' . $this->course->id);

        return (new MailMessage)
            ->subject('Welcome to ' . $this->course->title)
            ->greeting('Assalamu Alaikum, ' . ($notifiable->full_name ?? $notifiable->name) . '!')
            ->line('You have successfully enrolled in **' . $this->course->title . '**.')
            ->when($this->course->description, fn($m) => $m->line($this->course->description))
            ->line('Level: ' . ucfirst($this->course->level ?? 'beginner'))
            ->line('Modules: ' . $this->course->modules()->count() . ' · Lessons: ' . $this->course->lessons()->count())
            ->action('Start Learning', $url)
            ->line('May Allah bless your journey of seeking knowledge.')
            ->salutation('Barakallahu feek, ' . config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'course_enrolled',
            'title'        => 'Enrolled in course',
            'message'      => 'You are now enrolled in ' . $this->course->title,
            'course_id'    => $this->course->id,
            'course_title' => $this->course->title,
            'level'        => $this->course->level,
            'icon'         => 'o-academic-cap',
            'url'          => '/student/courses/' . $this->course->id,
        ];
    }
}
