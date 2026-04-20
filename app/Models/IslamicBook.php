<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class IslamicBook extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'author', 'category', 'description',
        'year', 'language', 'pages',
        'cover_path', 'file_path', 'external_url',
        'is_published', 'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order'   => 'integer',
    ];

    public static array $categories = [
        'quran'     => 'Quran & Tafsir',
        'hadith'    => 'Hadith',
        'aqidah'    => 'Aqidah (Theology)',
        'fiqh'      => 'Fiqh (Law)',
        'seerah'    => 'Seerah (Biography)',
        'spiritual' => 'Spirituality',
        'history'   => 'Islamic History',
        'arabic'    => 'Arabic Language',
        'other'     => 'Other',
    ];

    public function getCoverUrlAttribute(): ?string
    {
        if (!$this->cover_path) return null;
        return Storage::url($this->cover_path);
    }

    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) return null;
        // Files in public/files/ are served directly
        if (str_starts_with($this->file_path, 'public-files:')) {
            return '/files/' . rawurlencode(substr($this->file_path, 13));
        }
        return Storage::url($this->file_path);
    }

    public function getFileNameAttribute(): ?string
    {
        if (!$this->file_path) return null;
        if (str_starts_with($this->file_path, 'public-files:')) {
            return substr($this->file_path, 13);
        }
        return basename($this->file_path);
    }

    public function getCategoryLabelAttribute(): string
    {
        return static::$categories[$this->category] ?? ucfirst($this->category);
    }
}
