<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id', 'course_id', 'teacher_id', 'title', 'description',
        'instructions', 'max_score', 'due_date', 'allow_late',
        'late_penalty_percent', 'status',
    ];

    protected function casts(): array
    {
        return [
            'due_date'   => 'datetime',
            'allow_late' => 'boolean',
        ];
    }

    public function lesson()      { return $this->belongsTo(Lesson::class); }
    public function course()      { return $this->belongsTo(Course::class); }
    public function teacher()     { return $this->belongsTo(Teacher::class); }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && now()->gt($this->due_date);
    }
}
