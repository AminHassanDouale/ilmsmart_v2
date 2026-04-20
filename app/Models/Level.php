<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'name_ar', 'name_fr', 'name_en', 'order'];

    public function getTranslatedNameAttribute(): string
    {
        $lang = app()->getLocale();
        return $this->{"name_{$lang}"} ?? $this->name;
    }

    public function grades()
    {
        return $this->hasMany(Grade::class)->orderBy('order');
    }
}
