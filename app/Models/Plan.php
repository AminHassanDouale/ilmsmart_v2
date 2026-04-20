<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'name_ar', 'name_fr', 'name_en', 'description',
        'price', 'billing_cycle', 'courses_limit', 'unlimited_courses',
        'live_classes', 'private_tutoring', 'is_active', 'order',
    ];

    protected function casts(): array
    {
        return [
            'unlimited_courses' => 'boolean',
            'live_classes'      => 'boolean',
            'private_tutoring'  => 'boolean',
            'is_active'         => 'boolean',
            'price'             => 'float',
        ];
    }

    public function getTranslatedNameAttribute(): string
    {
        $lang = app()->getLocale();
        return $this->{"name_{$lang}"} ?? $this->name;
    }

    public function subscriptions() { return $this->hasMany(Subscription::class); }
}
