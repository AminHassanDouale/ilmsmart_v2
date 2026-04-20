<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'plan_id', 'starts_at', 'ends_at', 'auto_renew', 'status'];

    protected function casts(): array
    {
        return [
            'starts_at'  => 'datetime',
            'ends_at'    => 'datetime',
            'auto_renew' => 'boolean',
        ];
    }

    public function user()     { return $this->belongsTo(User::class); }
    public function plan()     { return $this->belongsTo(Plan::class); }
    public function payments() { return $this->hasMany(Payment::class); }

    public function isActive(): bool
    {
        return $this->status === 'active' && ($this->ends_at === null || $this->ends_at->isFuture());
    }
}
