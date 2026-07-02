<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


class City extends Model
{
    use HasUuids;
    protected $fillable = [
        'name',
        'region',
        'country',
        'is_active',
    ];
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean'
        ];
    }
    public function cars(){
        return $this->hasMany(Car::class);
    }
    public function agencies(){
        return $this->hasMany(Agency::class);
    }
}
