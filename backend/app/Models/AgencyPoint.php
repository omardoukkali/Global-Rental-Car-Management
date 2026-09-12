<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyPoint extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'agency_id',
        'city_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'allows_pickup',
        'allows_return',
        'opening_hours',
        'instructions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'allows_pickup' => 'boolean',
            'allows_return' => 'boolean',
            'opening_hours' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function pickupReservations()
    {
        return $this->hasMany(
            Reservation::class,
            'pickup_point_id'
        );
    }

    public function returnReservations()
    {
        return $this->hasMany(
            Reservation::class,
            'return_point_id'
        );
    }
}