<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignmentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id', 'student_id', 'content', 'is_late', 'score',
        'teacher_feedback', 'submitted_at', 'graded_at', 'graded_by', 'status',
    ];

    protected function casts(): array
    {
        return [
            'is_late'      => 'boolean',
            'submitted_at' => 'datetime',
            'graded_at'    => 'datetime',
        ];
    }

    public function assignment() { return $this->belongsTo(Assignment::class); }
    public function student()    { return $this->belongsTo(Student::class); }
    public function gradedBy()   { return $this->belongsTo(User::class, 'graded_by'); }
    public function files()      { return $this->hasMany(SubmissionFile::class, 'submission_id'); }
}
