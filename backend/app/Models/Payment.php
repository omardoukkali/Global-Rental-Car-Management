<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


class Payment extends Model
{
    use HasUuids;
    protected $fillable = [
        'id',
        'reservation_id',
        'amount',
        'commission_amount',
        'agency_amount',
        'payment_method',
        'status',
        'paid_at',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'agency_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }
    public function reservation(){
        return $this->belongsTo(Reservation::class);
    }
    public function refund(){
        return $this->hasOne(Refund::class);
    }
    
}
