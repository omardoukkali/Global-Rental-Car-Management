<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


class CarImage extends Model
{
    use HasUuids;
    protected $fillable = [
        'id',
        'car_id',
        'url',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
    public function car(){
        return $this->belongsTo(Car::class);
    }
}
