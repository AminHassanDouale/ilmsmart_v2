<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'course_id', 'live_class_id', 'academic_year_id', 'date', 'status', 'note',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function student()      { return $this->belongsTo(Student::class); }
    public function course()       { return $this->belongsTo(Course::class); }
    public function liveClass()    { return $this->belongsTo(LiveClass::class); }
    public function academicYear() { return $this->belongsTo(AcademicYear::class); }

    public function getBadgeClassAttribute(): string
    {
        return match($this->status) {
            'present' => 'badge-success',
            'absent'  => 'badge-error',
            'late'    => 'badge-warning',
            'excused' => 'badge-info',
            default   => 'badge-neutral',
        };
    }
}
