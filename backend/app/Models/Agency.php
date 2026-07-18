<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agency extends Model
{
    use SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'owner_id',
        'city_id',
        'name',
        'slug',
        'description',
        'logo_url',
        'address',
        'phone',
        'email',
        'status',
        'avg_rating',
        'total_reviews',
    ];
    public function owner(){
        return $this->belongsTo(User::class, 'owner_id');
    }
    public function city(){
        return $this->belongsTo(City::class);
    }
    public function cars(){
        return $this->hasMany(Car::class);
    }
    public function reservations(){
        return $this->hasMany(Reservation::class);
    }

}
