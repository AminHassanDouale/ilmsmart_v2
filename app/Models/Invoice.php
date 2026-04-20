<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id', 'user_id', 'invoice_number', 'amount',
        'tax', 'total', 'currency', 'status', 'issue_date', 'due_date',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date'   => 'date',
            'amount'     => 'float',
            'tax'        => 'float',
            'total'      => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $invoice) {
            $invoice->invoice_number ??= 'INV-' . date('Y') . '-' . str_pad(static::whereYear('created_at', date('Y'))->count() + 1, 5, '0', STR_PAD_LEFT);
        });
    }

    public function payment() { return $this->belongsTo(Payment::class); }
    public function user()    { return $this->belongsTo(User::class); }
}
