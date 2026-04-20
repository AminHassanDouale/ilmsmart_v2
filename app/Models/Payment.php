<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'subscription_id', 'course_id', 'payment_reference',
        'amount', 'currency', 'method', 'status', 'notes', 'paid_at', 'received_by',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'amount'  => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $payment) {
            $payment->payment_reference ??= strtoupper(uniqid('PAY-'));
        });
    }

    public function user()         { return $this->belongsTo(User::class); }
    public function subscription() { return $this->belongsTo(Subscription::class); }
    public function course()       { return $this->belongsTo(Course::class); }
    public function receivedBy()   { return $this->belongsTo(User::class, 'received_by'); }
    public function invoice()      { return $this->hasOne(Invoice::class); }
}
