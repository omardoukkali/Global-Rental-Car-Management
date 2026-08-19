<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agency extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'city_id',
        'name',
        'slug',
        'logo_url',
        'address',
        'phone',
        'email',
        'status',
        'commission_rate',
        'avg_rating',
        'total_reviews',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'avg_rating' => 'decimal:2',
            'total_reviews' => 'integer',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function agencyPoints()
    {
        return $this->hasMany(AgencyPoint::class);
    }

    public function cars()
    {
        return $this->hasMany(Car::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function reviews()
    {
        return $this->hasManyThrough(
            Review::class,
            Reservation::class,
            'agency_id',
            'reservation_id'
        );
    }
}
