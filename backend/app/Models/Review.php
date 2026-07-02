<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


class Review extends Model
{
    use SoftDeletes , HasUuids;

    protected $fillable = [
        'id',
        'reservation_id',
        'client_id',
        'car_rating',
        'agency_rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'car_rating' => 'decimal:1',
            'agency_rating' => 'decimal:1',
            'deleted_at' => 'datetime',
        ];
    }
    public function user(){
        return $this->belongsTo(User::class, 'client_id');
    }
    public function client(){
        return $this->belongsTo(User::class, 'client_id');
    }
    public function reservation(){
        return $this->belongsTo(Reservation::class);
    }
}
