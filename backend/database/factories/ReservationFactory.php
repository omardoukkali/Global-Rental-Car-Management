<?php

namespace Database\Factories;

use App\Models\AgencyPoint;
use App\Models\Car;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        $startAt = now()->addDays(fake()->numberBetween(2, 30))->setTime(10, 0);
        $days = fake()->numberBetween(1, 7);

        return [
            'client_id' => User::factory(),
            'car_id' => Car::factory(),

            // The agency and points must match the car, so they are
            // computed from the attributes above
            'agency_id' => function (array $attributes) {
                return Car::find($attributes['car_id'])->agency_id;
            },
            'pickup_point_id' => function (array $attributes) {
                return AgencyPoint::factory()->create([
                    'agency_id' => $attributes['agency_id'],
                ])->id;
            },
            'return_point_id' => function (array $attributes) {
                return $attributes['pickup_point_id'];
            },

            'reference' => 'RES-' . strtoupper(Str::random(10)),
            'start_at' => $startAt,
            'end_at' => $startAt->copy()->addDays($days),
            'daily_price_snapshot' => 300,
            'total_amount' => 300 * $days,
            'status' => 'pending',
        ];
    }
}
