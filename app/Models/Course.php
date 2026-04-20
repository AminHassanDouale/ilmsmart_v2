<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subject_id', 'teacher_id', 'academic_year_id', 'title',
        'title_ar', 'title_fr', 'title_en', 'description', 'description_ar',
        'description_fr', 'description_en', 'thumbnail', 'status', 'type',
        'level', 'is_islamic', 'price', 'duration_hours', 'order',
    ];

    protected function casts(): array
    {
        return ['price' => 'float', 'is_islamic' => 'boolean'];
    }

    public function getTranslatedTitleAttribute(): string
    {
        $lang = app()->getLocale();
        return $this->{"title_{$lang}"} ?? $this->title;
    }

    public function getTranslatedDescriptionAttribute(): ?string
    {
        $lang = app()->getLocale();
        return $this->{"description_{$lang}"} ?? $this->description;
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function modules()
    {
        return $this->hasMany(Module::class)->orderBy('order');
    }

    public function lessons()
    {
        return $this->hasManyThrough(Lesson::class, Module::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'enrollments')
                    ->withPivot('progress_percent', 'status', 'enrolled_at')
                    ->withTimestamps();
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function liveClasses()
    {
        return $this->hasMany(LiveClass::class);
    }

    public function getEnrolledCountAttribute(): int
    {
        return $this->enrollments()->count();
    }
}
