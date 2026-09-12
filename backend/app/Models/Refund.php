<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'payment_id',
        'agency_id',
        'percentage',
        'refunded_amount',
        'decision_source',
        'status',
        'reason',
        'decided_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'decided_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}