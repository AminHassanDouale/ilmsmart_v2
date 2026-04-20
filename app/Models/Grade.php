<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = ['level_id', 'name', 'name_ar', 'name_fr', 'name_en', 'order'];

    public function getTranslatedNameAttribute(): string
    {
        $lang = app()->getLocale();
        return $this->{"name_{$lang}"} ?? $this->name;
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
