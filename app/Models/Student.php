<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'grade_id', 'academic_year_id', 'student_number',
        'birth_date', 'gender', 'school_name', 'address', 'nationality', 'student_type',
    ];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function parents()
    {
        return $this->belongsToMany(ParentModel::class, 'parent_student', 'student_id', 'parent_id')
                    ->withPivot('relationship')
                    ->withTimestamps();
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'enrollments')
                    ->withPivot('progress_percent', 'status', 'enrolled_at', 'completed_at')
                    ->withTimestamps();
    }

    public function lessonProgress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function quizAttempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function assignmentSubmissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function grades()
    {
        return $this->hasMany(StudentGrade::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'student_badges')->withPivot('awarded_at');
    }

    public function liveClasses()
    {
        return $this->belongsToMany(LiveClass::class, 'live_class_students')
                    ->withPivot('attended', 'joined_at', 'left_at');
    }
}
