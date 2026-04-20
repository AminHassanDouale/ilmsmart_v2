<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'module_id', 'title', 'title_ar', 'title_fr', 'title_en',
        'content', 'lesson_meta', 'type', 'video_url', 'video_provider',
        'duration_minutes', 'is_free_preview', 'order', 'status',
    ];

    protected function casts(): array
    {
        return ['is_free_preview' => 'boolean', 'lesson_meta' => 'array'];
    }

    public function getTranslatedTitleAttribute(): string
    {
        $lang = app()->getLocale();
        return $this->{"title_{$lang}"} ?? $this->title;
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function course()
    {
        return $this->hasOneThrough(Course::class, Module::class, 'id', 'id', 'module_id', 'course_id');
    }

    public function documents()
    {
        return $this->hasMany(LessonDocument::class)->orderBy('order');
    }

    public function quiz()
    {
        return $this->hasOne(Quiz::class);
    }

    public function assignment()
    {
        return $this->hasOne(Assignment::class);
    }

    public function progress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'video'      => 'o-play-circle',
            'document'   => 'o-document-text',
            'quiz'       => 'o-clipboard-document-list',
            'assignment' => 'o-pencil-square',
            'live'       => 'o-video-camera',
            default      => 'o-book-open',
        };
    }
}
