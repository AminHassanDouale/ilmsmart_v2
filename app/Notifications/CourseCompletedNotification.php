<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CourseCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(public Course $course) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🎉 Congratulations — You completed ' . $this->course->title)
            ->greeting('Mabrook, ' . ($notifiable->full_name ?? $notifiable->name) . '!')
            ->line('You have successfully completed **' . $this->course->title . '**.')
            ->line('This is a great achievement on your path of learning.')
            ->action('View Your Progress', url('/student/progress'))
            ->line('Continue your journey by enrolling in the next level.')
            ->salutation('Barakallahu feek, ' . config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'course_completed',
            'title'        => 'Course completed',
            'message'      => 'You completed ' . $this->course->title . ' 🎉',
            'course_id'    => $this->course->id,
            'course_title' => $this->course->title,
            'icon'         => 'o-trophy',
            'url'          => '/student/progress',
        ];
    }
}
