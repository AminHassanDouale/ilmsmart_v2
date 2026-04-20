<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'bio', 'qualification', 'experience_years',
        'hourly_rate', 'rating', 'rating_count', 'is_tutor', 'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'is_tutor'    => 'boolean',
            'is_verified' => 'boolean',
            'rating'      => 'float',
            'hourly_rate' => 'float',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_subjects')->withTimestamps();
    }

    public function availability()
    {
        return $this->hasMany(TeacherAvailability::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function liveClasses()
    {
        return $this->hasMany(LiveClass::class);
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function tutoringSessions()
    {
        return $this->hasMany(TutoringSession::class);
    }
}
