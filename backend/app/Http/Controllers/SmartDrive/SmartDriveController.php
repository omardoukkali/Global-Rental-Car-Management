<?php

namespace App\Http\Controllers\SmartDrive;

use App\Http\Controllers\Controller;
use App\Http\Requests\SmartDrive\SmartDriveRequest;
use App\Models\Car;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class SmartDriveController extends Controller
{
    /**
     * SmartDrive AI (SCRUM-173)
     *
     * Validates the SmartDrive form and returns the vehicles that CAN be rented
     * (hard business rules). The client preferences (budget, type, transmission,
     * energy) are NOT used to filter here: they are sent back so the AI service
     * can score the vehicles and suggest alternatives.
     */
    public function eligibleVehicles(SmartDriveRequest $request): JsonResponse
    {
        $data = $request->validated();

        $cityId = $data['city_id'];
        $startAt = Carbon::parse($data['start_at']);
        $endAt = Carbon::parse($data['end_at']);

        // Same day calculation as a reservation
        $days = max(1, (int) ceil($startAt->diffInHours($endAt) / 24));

        $cars = Car::with(['agency.agencyPoints', 'images'])
            // 1. The car can be rented and is in the pickup city
            ->where('status', 'available')
            ->where('city_id', $cityId)

            // 2. Enough seats for every passenger
            ->where('seats', '>=', $data['passengers'])

            // 3. The agency is approved
            ->whereHas('agency', function ($agencyQuery) {
                $agencyQuery->where('status', 'approved');
            })

            // 4. The agency has an open pickup point in the city
            ->whereHas('agency.agencyPoints', function ($pointQuery) use ($cityId) {
                $pointQuery->where('city_id', $cityId)
                    ->where('is_active', true)
                    ->where('allows_pickup', true);
            })

            // 5. The car is not already booked during these dates
            ->whereDoesntHave('reservations', function ($reservationQuery) use ($startAt, $endAt) {
                $reservationQuery->whereIn('status', ['pending', 'confirmed', 'picked_up'])
                    ->where('start_at', '<', $endAt)
                    ->where('end_at', '>', $startAt);
            })
            ->get();

        $vehicles = [];

        foreach ($cars as $car) {
            $pickupPoints = [];
            $returnPoints = [];

            foreach ($car->agency->agencyPoints as $point) {
                if (!$point->is_active) {
                    continue;
                }

                if ($point->allows_pickup && $point->city_id === $cityId) {
                    $pickupPoints[] = [
                        'id' => $point->id,
                        'name' => $point->name,
                        'address' => $point->address,
                    ];
                }

                if ($point->allows_return) {
                    $returnPoints[] = [
                        'id' => $point->id,
                        'name' => $point->name,
                        'address' => $point->address,
                    ];
                }
            }

            // 6. The car must also be returnable somewhere
            if (count($returnPoints) === 0) {
                continue;
            }

            $primaryImage = $car->images->firstWhere('is_primary', true);

            $vehicles[] = [
                'id' => $car->id,
                'brand' => $car->brand,
                'model' => $car->model,
                'year' => $car->year,
                'color' => $car->color,
                'type' => $car->type,
                'transmission' => $car->transmission,
                'seats' => $car->seats,
                'energy_type' => $car->energy_type,
                'fuel_consumption' => $car->fuel_consumption,
                'electric_range' => $car->electric_range,
                'daily_price' => $car->daily_price,
                'total_price' => round($car->daily_price * $days, 2),
                'image_url' => $primaryImage ? $primaryImage->url : null,
                'agency' => [
                    'id' => $car->agency->id,
                    'name' => $car->agency->name,
                    'avg_rating' => $car->agency->avg_rating,
                    'total_reviews' => $car->agency->total_reviews,
                ],
                'pickup_points' => $pickupPoints,
                'return_points' => $returnPoints,
            ];
        }

        return response()->json([
            'trip' => [
                'city_id' => $cityId,
                'start_at' => $startAt->toDateTimeString(),
                'end_at' => $endAt->toDateTimeString(),
                'days' => $days,
                'passengers' => (int) $data['passengers'],
            ],
            'preferences' => [
                'budget_per_day' => (float) $data['budget_per_day'],
                'vehicle_type' => $data['vehicle_type'] ?? null,
                'transmission' => $data['transmission'] ?? null,
                'energy_type' => $data['energy_type'] ?? null,
            ],
            'total' => count($vehicles),
            'vehicles' => $vehicles,
        ]);
    }
}
