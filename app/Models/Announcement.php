<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'title', 'content', 'target', 'grade_id', 'is_pinned', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned'    => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function user()  { return $this->belongsTo(User::class); }
    public function grade() { return $this->belongsTo(Grade::class); }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }
}
