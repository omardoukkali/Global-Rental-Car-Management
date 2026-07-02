<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


class Refund extends Model
{
    use HasUuids;
    protected $fillable = [
        'id',
        'payment_id',
        'amount',
        'cancellation_fee',
        'platform_fee',
        'agency_fee',
        'reason',
        'status',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'cancellation_fee' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'agency_fee' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }
    public function payment(){
        return $this->belongsTo(Payment::class);
    }
}
