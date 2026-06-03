<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id', 'title', 'description',
        'day_of_week', 'start_time', 'end_time',
        'specific_date', 'recurrence', 'location',
        'meeting_link', 'teacher_id', 'order',
    ];

    protected function casts(): array
    {
        return [
            'specific_date' => 'date',
        ];
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function attendances()
    {
        return $this->hasMany(ProgramSessionAttendance::class);
    }

    public function getDayLabelAttribute(): ?string
    {
        return match($this->day_of_week) {
            'mon' => 'Monday',    'tue' => 'Tuesday', 'wed' => 'Wednesday',
            'thu' => 'Thursday',  'fri' => 'Friday',  'sat' => 'Saturday',
            'sun' => 'Sunday',    default => null,
        };
    }

    public function getTimeRangeAttribute(): string
    {
        if (!$this->start_time) return '—';
        return substr($this->start_time, 0, 5) . ($this->end_time ? ' – ' . substr($this->end_time, 0, 5) : '');
    }
}
