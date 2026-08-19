<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'client_id',
        'car_id',
        'agency_id',
        'pickup_point_id',
        'return_point_id',
        'reference',
        'start_at',
        'end_at',
        'daily_price_snapshot',
        'total_amount',
        'status',
        'picked_up_at',
        'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'daily_price_snapshot' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'picked_up_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function pickupPoint()
    {
        return $this->belongsTo(
            AgencyPoint::class,
            'pickup_point_id'
        );
    }

    public function returnPoint()
    {
        return $this->belongsTo(
            AgencyPoint::class,
            'return_point_id'
        );
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
}