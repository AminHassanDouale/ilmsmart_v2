<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id', 'student_id', 'attempt_number', 'score', 'total_points',
        'earned_points', 'passed', 'time_taken_minutes', 'started_at', 'submitted_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'passed'       => 'boolean',
            'started_at'   => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function quiz()    { return $this->belongsTo(Quiz::class); }
    public function student() { return $this->belongsTo(Student::class); }
    public function answers() { return $this->hasMany(QuizAttemptAnswer::class, 'attempt_id'); }
}
