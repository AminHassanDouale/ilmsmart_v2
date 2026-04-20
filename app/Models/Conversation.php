<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'type'];

    public function participants()
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
                    ->withPivot('last_read_at')
                    ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    public function unreadCount(int $userId): int
    {
        $pivot = $this->participants()->where('user_id', $userId)->first()?->pivot;
        if (!$pivot || !$pivot->last_read_at) return $this->messages()->count();
        return $this->messages()->where('created_at', '>', $pivot->last_read_at)->count();
    }
}
