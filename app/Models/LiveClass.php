<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id', 'teacher_id', 'title', 'description',
        'start_time', 'end_time', 'meeting_link', 'meeting_id',
        'meeting_password', 'platform', 'recording_url', 'status', 'max_students',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time'   => 'datetime',
        ];
    }

    public function course()   { return $this->belongsTo(Course::class); }
    public function teacher()  { return $this->belongsTo(Teacher::class); }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'live_class_students')
                    ->withPivot('attended', 'joined_at', 'left_at')
                    ->withTimestamps();
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->start_time->isFuture();
    }

    public function getIsLiveNowAttribute(): bool
    {
        return now()->between($this->start_time, $this->end_time);
    }

    public function getDurationMinutesAttribute(): int
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }
}
