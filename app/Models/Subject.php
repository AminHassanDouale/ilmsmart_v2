<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = ['grade_id', 'name', 'name_ar', 'name_fr', 'name_en', 'icon', 'color', 'coefficient'];

    public function getTranslatedNameAttribute(): string
    {
        $lang = app()->getLocale();
        return $this->{"name_{$lang}"} ?? $this->name;
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_subjects')->withTimestamps();
    }
}
