<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'subscription_id', 'plan_id', 'event',
        'amount', 'currency', 'metadata', 'notes', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata'    => 'array',
            'amount'      => 'float',
            'occurred_at' => 'datetime',
        ];
    }

    public function user()         { return $this->belongsTo(User::class); }
    public function subscription() { return $this->belongsTo(Subscription::class); }
    public function plan()         { return $this->belongsTo(Plan::class); }

    public function getEventLabelAttribute(): string
    {
        return ucfirst($this->event);
    }

    public function getEventColorAttribute(): string
    {
        return match($this->event) {
            'created','reactivated'    => 'badge-success',
            'renewed'                   => 'badge-info',
            'upgraded'                  => 'badge-primary',
            'downgraded'                => 'badge-warning',
            'cancelled','expired'       => 'badge-error',
            'refunded'                  => 'badge-ghost',
            default                     => 'badge-ghost',
        };
    }

    public function getEventIconAttribute(): string
    {
        return match($this->event) {
            'created'      => 'o-plus-circle',
            'reactivated'  => 'o-arrow-path',
            'renewed'      => 'o-arrow-path-rounded-square',
            'upgraded'     => 'o-arrow-trending-up',
            'downgraded'   => 'o-arrow-trending-down',
            'cancelled'    => 'o-x-circle',
            'expired'      => 'o-clock',
            'refunded'     => 'o-banknotes',
            default        => 'o-document',
        };
    }
}
