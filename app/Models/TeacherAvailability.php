<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherAvailability extends Model
{
    use HasFactory;

    protected $table = 'teacher_availability';

    protected $fillable = ['teacher_id', 'day', 'start_time', 'end_time', 'is_available'];

    protected function casts(): array
    {
        return ['is_available' => 'boolean'];
    }

    public function teacher() { return $this->belongsTo(Teacher::class); }

    public function getDayLabelAttribute(): string
    {
        return match($this->day) {
            'monday'    => __('days.monday'),
            'tuesday'   => __('days.tuesday'),
            'wednesday' => __('days.wednesday'),
            'thursday'  => __('days.thursday'),
            'friday'    => __('days.friday'),
            'saturday'  => __('days.saturday'),
            'sunday'    => __('days.sunday'),
            default     => ucfirst($this->day),
        };
    }
}
