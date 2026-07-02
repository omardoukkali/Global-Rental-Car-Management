<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Carbon\Carbon;


class Reservation extends Model
{
    use HasUuids;
    protected $fillable = [
        'id',
        'client_id',
        'car_id',
        'agency_id',
        'reference_number',
        'start_date',
        'end_date',
        'price_per_day_snapshot',
        'total_amount',
        'commission_amount',
        'agency_earning',
        'status',
        'cancellation_reason',
        'cancelled_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'price_per_day_snapshot' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'agency_earning' => 'decimal:2',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
    public function car(){
        return $this->belongsTo(Car::class);
    }
    public function user(){
        return $this->belongsTo(User::class, 'client_id');
    }
    public function client(){
        return $this->belongsTo(User::class, 'client_id');
    }
    public function agency(){
        return $this->belongsTo(Agency::class);
    }
    public function payment(){
        return $this->hasOne(Payment::class);
    }
    public function review(){
        return $this->hasOne(Review::class);
    }
    public function getTotalDays(): int
    {
        return Carbon::parse($this->start_date)->diffInDays(Carbon::parse($this->end_date));
    }
}
