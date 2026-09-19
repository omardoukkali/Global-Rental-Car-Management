<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    /**
     * Time slots used for every car, relative to today.
     * The slots never overlap, so a car is never booked twice at the same time.
     * 'start' = days from today (negative = in the past), 'days' = rental length.
     */
    private array $slots = [
        ['status' => 'completed', 'start' => -120, 'days' => 4],
        ['status' => 'completed', 'start' => -90, 'days' => 3],
        ['status' => 'completed', 'start' => -60, 'days' => 5],
        ['status' => 'completed', 'start' => -35, 'days' => 3],
        ['status' => 'completed', 'start' => -20, 'days' => 2],
        ['status' => 'disputed', 'start' => -14, 'days' => 3],
        ['status' => 'cancelled', 'start' => -8, 'days' => 2],
        ['status' => 'picked_up', 'start' => -1, 'days' => 4],
        ['status' => 'confirmed', 'start' => 6, 'days' => 3],
        ['status' => 'pending', 'start' => 14, 'days' => 2],
        ['status' => 'rejected', 'start' => 22, 'days' => 3],
        ['status' => 'cancelled', 'start' => 30, 'days' => 2],
    ];

    public function run(): void
    {
        $clients = User::where('role', 'client')->get();

        // Only rentable cars of approved agencies
        $cars = Car::with('agency.agencyPoints')
            ->where('status', 'available')
            ->whereHas('agency', function ($query) {
                $query->where('status', 'approved');
            })
            ->get();

        foreach ($cars as $car) {
            $points = $car->agency->agencyPoints;

            foreach ($this->slots as $slot) {
                // Use each slot only half of the time, so cars look different
                if (!fake()->boolean()) {
                    continue;
                }

                $startAt = now()->startOfDay()->addDays($slot['start'])->setTime(10, 0);
                $endAt = $startAt->copy()->addDays($slot['days']);

                $reservation = Reservation::factory()->make([
                    'client_id' => $clients->random()->id,
                    'car_id' => $car->id,
                    'agency_id' => $car->agency_id,
                    'pickup_point_id' => $points->random()->id,
                    'return_point_id' => $points->random()->id,
                    'start_at' => $startAt,
                    'end_at' => $endAt,
                    'daily_price_snapshot' => $car->daily_price,
                    'total_amount' => $car->daily_price * $slot['days'],
                    'status' => $slot['status'],
                ]);

                $this->addConfirmationDates($reservation);

                $reservation->save();
            }
        }
    }

    /**
     * Fill the pickup/return confirmation dates that match the status.
     */
    private function addConfirmationDates(Reservation $reservation): void
    {
        $status = $reservation->status;

        // The car was picked up (both sides confirmed the pickup)
        if ($status === 'picked_up' || $status === 'disputed' || $status === 'completed') {
            $reservation->client_pickup_confirmed_at = $reservation->start_at;
            $reservation->agency_pickup_confirmed_at = $reservation->start_at;
            $reservation->picked_up_at = $reservation->start_at;
        }

        // The car was returned (both sides confirmed the return)
        if ($status === 'completed') {
            $reservation->client_return_confirmed_at = $reservation->end_at;
            $reservation->agency_return_confirmed_at = $reservation->end_at;
            $reservation->returned_at = $reservation->end_at;
        }
    }
}
