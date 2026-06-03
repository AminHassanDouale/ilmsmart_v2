<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'title_ar', 'title_fr', 'title_en', 'description', 'thumbnail',
        'level', 'type', 'price', 'duration_weeks', 'capacity',
        'start_date', 'end_date', 'schedule_type', 'is_islamic',
        'audience', 'status', 'order',
    ];

    protected function casts(): array
    {
        return [
            'price'       => 'float',
            'is_islamic'  => 'boolean',
            'start_date'  => 'date',
            'end_date'    => 'date',
        ];
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'program_courses')
                    ->withPivot('order','is_required')
                    ->orderBy('program_courses.order')
                    ->withTimestamps();
    }

    public function sessions()
    {
        return $this->hasMany(ProgramSession::class)->orderBy('order');
    }

    public function enrollments()
    {
        return $this->hasMany(ProgramEnrollment::class);
    }

    public function activeEnrollments()
    {
        return $this->enrollments()->where('status', 'active');
    }

    public function enrolledUsers()
    {
        return $this->belongsToMany(User::class, 'program_enrollments')
                    ->withPivot('status','progress_percent','enrolled_at')
                    ->withTimestamps();
    }

    public function getTranslatedTitleAttribute(): string
    {
        $lang = app()->getLocale();
        return $this->{"title_{$lang}"} ?? $this->title;
    }

    public function getEnrolledCountAttribute(): int
    {
        return $this->enrollments()->where('status', 'active')->count();
    }

    public function getIsFullAttribute(): bool
    {
        return $this->capacity && $this->enrolled_count >= $this->capacity;
    }

    public function getTotalLessonsAttribute(): int
    {
        return $this->courses->sum(fn($c) => $c->lessons()->count());
    }
}
