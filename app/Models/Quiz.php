<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id', 'course_id', 'teacher_id', 'title', 'description',
        'duration_minutes', 'passing_score', 'max_attempts', 'show_answers',
        'shuffle_questions', 'status', 'available_from', 'available_until',
    ];

    protected function casts(): array
    {
        return [
            'show_answers'      => 'boolean',
            'shuffle_questions' => 'boolean',
            'available_from'    => 'datetime',
            'available_until'   => 'datetime',
        ];
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function getTotalPointsAttribute(): int
    {
        return $this->questions()->sum('points');
    }
}
