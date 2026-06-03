<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role', 'status',
        'avatar', 'timezone', 'language', 'first_name', 'last_name',
        'username', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // Role helpers
    public function isAdmin(): bool    { return $this->role === 'admin'; }
    public function isTeacher(): bool  { return in_array($this->role, ['teacher', 'tutor']); }
    public function isStudent(): bool    { return $this->role === 'student'; }
    public function isParent(): bool     { return $this->role === 'parent'; }
    public function isTutor(): bool      { return $this->role === 'tutor'; }
    public function isIndividual(): bool { return $this->role === 'individual'; }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}") ?: $this->name;
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar
            ? asset('storage/' . $this->avatar)
            : 'https://ui-avatars.com/api/?name=' . urlencode($this->full_name) . '&background=random';
    }

    // Relationships
    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function parentProfile()
    {
        return $this->hasOne(ParentModel::class);
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class);
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
                    ->withPivot('last_read_at')
                    ->withTimestamps();
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class);
    }

    public function programEnrollments()
    {
        return $this->hasMany(ProgramEnrollment::class);
    }

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'program_enrollments')
                    ->withPivot('status','progress_percent','enrolled_at')
                    ->withTimestamps();
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function subscriptionHistories()
    {
        return $this->hasMany(SubscriptionHistory::class)->latest('occurred_at');
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active');
    }
}
