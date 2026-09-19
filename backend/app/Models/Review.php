<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'reservation_id',
        'user_id',
        'car_rating',
        'agency_rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'car_rating' => 'decimal:1',
            'agency_rating' => 'decimal:1',
        ];
    }

    protected static function booted(): void
    {
        // Keep the agency's avg_rating / total_reviews in sync
        static::saved(fn (Review $review) => $review->reservation?->agency?->refreshRating());
        static::deleted(fn (Review $review) => $review->reservation?->agency?->refreshRating());
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}