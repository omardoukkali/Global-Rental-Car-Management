<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


class Car extends Model
{

    use SoftDeletes,HasUuids;
    protected $fillable = [
        'id',
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
        'price_per_day',
        'description',
        'status',
        'avg_rating',
        'total_reviews',
    ];
    protected function casts(): array
    {
        return [
            'price_per_day' => 'decimal:2',
            'avg_rating' => 'float',
            'total_reviews' => 'integer',
            'year' => 'integer',
            'seats' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }
    public function agency(){
        return $this->belongsTo(Agency::class);
    }
    public function city(){
        return $this->belongsTo(City::class);
    }
    public function images(){
        return $this->hasMany(CarImage::class);
    }
    public function reservations(){
        return $this->hasMany(Reservation::class);
    }
}
