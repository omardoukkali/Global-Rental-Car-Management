<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'region',
        'country',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function agencies()
    {
        return $this->hasMany(Agency::class);
    }

    public function agencyPoints()
    {
        return $this->hasMany(AgencyPoint::class);
    }

    public function cars()
    {
        return $this->hasMany(Car::class);
    }
}