<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramSessionAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_session_id', 'user_id', 'session_date', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['session_date' => 'date'];
    }

    public function session() { return $this->belongsTo(ProgramSession::class, 'program_session_id'); }
    public function user()    { return $this->belongsTo(User::class); }
}
