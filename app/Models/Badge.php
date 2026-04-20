<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'name_ar', 'name_fr', 'icon', 'color', 'description'];

    public function getTranslatedNameAttribute(): string
    {
        $lang = app()->getLocale();
        return $this->{"name_{$lang}"} ?? $this->name;
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_badges')->withPivot('awarded_at');
    }
}
