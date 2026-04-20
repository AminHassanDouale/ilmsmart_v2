<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonDocument extends Model
{
    use HasFactory;

    protected $fillable = ['lesson_id', 'title', 'file_path', 'file_type', 'file_size', 'order'];

    public function lesson() { return $this->belongsTo(Lesson::class); }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function getIconAttribute(): string
    {
        return match(true) {
            str_contains($this->file_type, 'pdf')   => 'o-document',
            str_contains($this->file_type, 'word')  => 'o-document-text',
            str_contains($this->file_type, 'image') => 'o-photo',
            str_contains($this->file_type, 'video') => 'o-film',
            default                                  => 'o-paper-clip',
        };
    }
}
