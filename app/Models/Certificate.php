<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'course_id', 'certificate_number', 'template', 'issued_at'];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime'];
    }

    public function student() { return $this->belongsTo(Student::class); }
    public function course()  { return $this->belongsTo(Course::class); }
}
