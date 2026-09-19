<?php

namespace Database\Seeders;

use App\Models\Reservation;
use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * About 70% of completed reservations get a review.
     * Creating a review also updates the agency avg_rating / total_reviews
     * (see Review::booted()).
     */
    public function run(): void
    {
        $reservations = Reservation::where('status', 'completed')->get();

        foreach ($reservations as $reservation) {
            if (!fake()->boolean(70)) {
                continue;
            }

            Review::factory()->create([
                'reservation_id' => $reservation->id,
                'user_id' => $reservation->client_id,
            ]);
        }
    }
}
