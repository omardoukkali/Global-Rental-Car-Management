<?php

namespace Database\Factories;

use App\Models\Reservation;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        $comments = [
            'Voiture propre et en très bon état. Je recommande !',
            'Service rapide, agence très professionnelle.',
            'Bonne expérience, la prise en charge était simple.',
            'Petit retard à la remise des clés, sinon tout était parfait.',
            'Très bon rapport qualité-prix.',
            'Voiture confortable pour un long trajet.',
            null,
        ];

        return [
            'reservation_id' => Reservation::factory(),

            // The reviewer is the client of the reservation
            'user_id' => function (array $attributes) {
                return Reservation::find($attributes['reservation_id'])->client_id;
            },

            'car_rating' => fake()->randomElement([3, 3.5, 4, 4.5, 5, 5]),
            'agency_rating' => fake()->randomElement([3, 3.5, 4, 4.5, 5, 5]),
            'comment' => fake()->randomElement($comments),
        ];
    }
}
