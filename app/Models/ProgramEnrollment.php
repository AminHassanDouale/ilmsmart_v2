<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id', 'user_id', 'enrolled_at', 'completed_at', 'cancelled_at',
        'progress_percent', 'status', 'subscription_id',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at'      => 'datetime',
            'completed_at'     => 'datetime',
            'cancelled_at'     => 'datetime',
            'progress_percent' => 'float',
        ];
    }

    public function program()     { return $this->belongsTo(Program::class); }
    public function user()        { return $this->belongsTo(User::class); }
    public function subscription(){ return $this->belongsTo(Subscription::class); }
}
