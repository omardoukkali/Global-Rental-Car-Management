<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Car extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'agency_id',
        'city_id',
        'brand',
        'model',
        'year',
        'color',
        'plate_number',
        'type',
        'transmission',
        'seats',
        'daily_price',
        'energy_type',
        'fuel_consumption',
        'electric_range',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'seats' => 'integer',
            'daily_price' => 'decimal:2',
            'fuel_consumption' => 'decimal:2',
            'electric_range' => 'integer',
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

    public function images()
    {
        return $this->hasMany(CarImage::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function reviews()
    {
        return $this->hasManyThrough(
            Review::class,
            Reservation::class,
            'car_id',
            'reservation_id'
        );
    }
}