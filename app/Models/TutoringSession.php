<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TutoringSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id', 'student_id', 'subject_id', 'session_date',
        'duration_minutes', 'price', 'meeting_link', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'datetime',
            'price'        => 'float',
        ];
    }

    public function teacher() { return $this->belongsTo(Teacher::class); }
    public function student() { return $this->belongsTo(Student::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
}
