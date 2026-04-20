<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{
    use HasFactory;

    protected $table = 'lesson_progress';

    protected $fillable = [
        'student_id', 'lesson_id', 'course_id', 'completed',
        'watch_time_seconds', 'completion_percent', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed'          => 'boolean',
            'completion_percent' => 'float',
            'completed_at'       => 'datetime',
        ];
    }

    public function student() { return $this->belongsTo(Student::class); }
    public function lesson()  { return $this->belongsTo(Lesson::class); }
    public function course()  { return $this->belongsTo(Course::class); }
}
