<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'start_date', 'end_date', 'is_current'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'is_current' => 'boolean',
        ];
    }

    public static function current(): ?self
    {
        return static::where('is_current', true)->first();
    }

    public function levels()
    {
        return $this->hasManyThrough(Level::class, Grade::class, 'id', 'id', 'id', 'level_id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
