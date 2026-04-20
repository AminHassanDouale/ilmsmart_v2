<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentGrade extends Model
{
    use HasFactory;

    protected $table = 'student_grades';

    protected $fillable = [
        'student_id', 'subject_id', 'academic_year_id',
        'period', 'score', 'max_score', 'grade_letter', 'comments',
    ];

    protected function casts(): array
    {
        return ['score' => 'float'];
    }

    public function student()      { return $this->belongsTo(Student::class); }
    public function subject()      { return $this->belongsTo(Subject::class); }
    public function academicYear() { return $this->belongsTo(AcademicYear::class); }

    public function getPercentageAttribute(): float
    {
        return $this->max_score > 0 ? round(($this->score / $this->max_score) * 100, 1) : 0;
    }

    public function getGradeLetterAutoAttribute(): string
    {
        $pct = $this->percentage;
        if ($pct >= 90) return 'A+';
        if ($pct >= 80) return 'A';
        if ($pct >= 70) return 'B';
        if ($pct >= 60) return 'C';
        if ($pct >= 50) return 'D';
        return 'F';
    }
}
