<?php

namespace App\Http\Controllers\Car;

use App\Http\Controllers\Controller;
use App\Http\Requests\Car\CheckCarAvailabilityRequest;
use App\Models\Car;
use Illuminate\Http\JsonResponse;

class CarAvailabilityController extends Controller
{
    public function check(CheckCarAvailabilityRequest $request, Car $car): JsonResponse
    {
        $data = $request->validated();

        if ($car->status !== 'available') {
            return response()->json([
                'car_id' => $car->id,
                'available' => false,
            ]);
        }

        $hasOverlap = $car->reservations()
            ->whereIn('status', [
                'pending',
                'confirmed',
                'picked_up',
            ])
            ->where('start_at', '<', $data['end_at'])
            ->where('end_at', '>', $data['start_at'])
            ->exists();

        return response()->json([
            'car_id' => $car->id,
            'available' => ! $hasOverlap,
        ]);
    }
}