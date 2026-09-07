<?php

namespace App\Http\Controllers\Reservation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reservation\StoreReservationRequest;
use App\Models\AgencyPoint;
use App\Models\Car;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ReservationController extends Controller
{
    public function store(StoreReservationRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Get the car with its agency
        $car = Car::with('agency')->findOrFail($data['car_id']);

        // Check if the car is available for reservation
        if ($car->status !== 'available') {
            return response()->json([
                'message' => 'Car is not available.',
            ], 422);
        }

        // Get pickup and return points
        $pickupPoint = AgencyPoint::findOrFail(
            $data['pickup_point_id']
        );

        $returnPoint = AgencyPoint::findOrFail(
            $data['return_point_id']
        );

        // Check pickup point belongs to the same agency as the car
        if ($pickupPoint->agency_id !== $car->agency_id) {
            return response()->json([
                'message' => 'Pickup point does not belong to the car agency.',
            ], 422);
        }

        // Check return point belongs to the same agency as the car
        if ($returnPoint->agency_id !== $car->agency_id) {
            return response()->json([
                'message' => 'Return point does not belong to the car agency.',
            ], 422);
        }

        // Check pickup point is active and allows pickup
        if (!$pickupPoint->is_active || !$pickupPoint->allows_pickup) {
            return response()->json([
                'message' => 'Pickup point is not available for pickup.',
            ], 422);
        }

        // Check return point is active and allows return
        if (!$returnPoint->is_active || !$returnPoint->allows_return) {
            return response()->json([
                'message' => 'Return point is not available for return.',
            ], 422);
        }

        // Check if the car has an overlapping reservation
        $hasOverlap = $car->reservations()
            ->whereIn('status', [
                'pending',
                'confirmed',
                'picked_up',
            ])
            ->where('start_at', '<', $data['end_at'])
            ->where('end_at', '>', $data['start_at'])
            ->exists();

        if ($hasOverlap) {
            return response()->json([
                'message' => 'Car is already reserved for the selected period.',
            ], 422);
        }

        // Calculate reservation duration
        $startAt = Carbon::parse($data['start_at']);
        $endAt = Carbon::parse($data['end_at']);

        $days = $startAt->diffInDays($endAt);

        // Calculate price using the current car price
        $dailyPrice = $car->daily_price;
        $totalAmount = $days * $dailyPrice;

        // Create reservation
        $reservation = Reservation::create([
            'client_id' => $request->user()->id,
            'car_id' => $car->id,
            'agency_id' => $car->agency_id,
            'pickup_point_id' => $data['pickup_point_id'],
            'return_point_id' => $data['return_point_id'],
            'reference' => 'RES-' . strtoupper(Str::random(10)),
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'],
            'daily_price_snapshot' => $dailyPrice,
            'total_amount' => $totalAmount,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Reservation created successfully.',
            'reservation' => $reservation->load([
                'car',
                'agency',
                'pickupPoint',
                'returnPoint',
            ]),
        ], 201);
    }
}