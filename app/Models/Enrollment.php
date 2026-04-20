<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'course_id', 'enrolled_at', 'completed_at', 'progress_percent', 'status',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at'      => 'datetime',
            'completed_at'     => 'datetime',
            'progress_percent' => 'float',
        ];
    }

    public function student() { return $this->belongsTo(Student::class); }
    public function course()  { return $this->belongsTo(Course::class); }
}
