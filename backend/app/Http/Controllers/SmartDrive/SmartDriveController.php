<?php

namespace App\Http\Controllers\SmartDrive;

use App\Http\Controllers\Controller;
use App\Http\Requests\SmartDrive\SmartDriveRequest;
use App\Models\Car;
use App\Services\SmartDriveAiService;
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
        return response()->json(
            $this->eligiblePayload($request->validated())
        );
    }

    /**
     * SmartDrive AI (SCRUM-180)
     *
     * Same rules as above, but the eligible vehicles are sent to the AI service,
     * which scores them and returns the recommendation.
     */
    public function recommend(SmartDriveRequest $request, SmartDriveAiService $ai): JsonResponse
    {
        $payload = $this->eligiblePayload($request->validated());

        // No car can be rented for this trip: the AI has nothing to score
        if ($payload['total'] === 0) {
            return response()->json([
                'message' => 'Aucun véhicule disponible pour ce trajet.',
                'trip' => $payload['trip'],
                'preferences' => $payload['preferences'],
                'total' => 0,
                'results' => [],
            ]);
        }

        // The AI service expects the trip and the preferences as flat fields
        $recommendation = $ai->recommend([
            'budget_per_day' => $payload['preferences']['budget_per_day'],
            'start_at' => $payload['trip']['start_at'],
            'end_at' => $payload['trip']['end_at'],
            'city_id' => $payload['trip']['city_id'],
            'passengers' => $payload['trip']['passengers'],
            'vehicle_type' => $payload['preferences']['vehicle_type'],
            'transmission' => $payload['preferences']['transmission'],
            'energy_type' => $payload['preferences']['energy_type'],
            'trip' => $payload['trip'],
            'vehicles' => $payload['vehicles'],
        ]);

        // AI service down or too slow
        if ($recommendation === null) {
            return response()->json([
                'message' => "Le service de recommandation est momentanément indisponible. Réessayez dans quelques instants.",
            ], 503);
        }

        return response()->json($recommendation);
    }

    /**
     * Builds the trip, the preferences and the list of vehicles that can
     * really be rented. Used by both endpoints.
     */
    private function eligiblePayload(array $data): array
    {

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

        return [
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
        ];
    }
}
