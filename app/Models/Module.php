<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'title', 'title_ar', 'title_fr', 'title_en', 'description', 'order'];

    public function getTranslatedTitleAttribute(): string
    {
        $lang = app()->getLocale();
        return $this->{"title_{$lang}"} ?? $this->title;
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }
}
