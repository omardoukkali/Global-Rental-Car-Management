<?php

namespace App\Http\Controllers\Car;

use App\Http\Controllers\Controller;
use App\Http\Requests\Car\StoreCarImageRequest;
use App\Models\Car;
use App\Models\CarImage;
use Illuminate\Http\JsonResponse;

class CarImageController extends Controller
{
    /**
     * Add a new image to a car.
     */
    public function store(
        StoreCarImageRequest $request,
        Car $car
    ): JsonResponse {
        $agency = $request->user()->agency;

        if (!$agency) {
            return response()->json([
                'message' => 'Agency profile not found.',
            ], 404);
        }

        if ($car->agency_id !== $agency->id) {
            return response()->json([
                'message' => 'This car does not belong to your agency.',
            ], 403);
        }

        $data = $request->validated();

        if (($data['is_primary'] ?? false) === true) {
            $car->images()->update([
                'is_primary' => false,
            ]);
        }

        $image = $car->images()->create($data);

        return response()->json([
            'message' => 'Car image added successfully.',
            'image' => $image,
        ], 201);
    }

    /**
     * List all images of a car.
     */
    public function index(Car $car): JsonResponse
    {
        $agency = request()->user()->agency;

        if (!$agency) {
            return response()->json([
                'message' => 'Agency profile not found.',
            ], 404);
        }

        if ($car->agency_id !== $agency->id) {
            return response()->json([
                'message' => 'This car does not belong to your agency.',
            ], 403);
        }

        $images = $car->images()
            ->orderBy('display_order')
            ->get();

        return response()->json([
            'images' => $images,
        ]);
    }

    /**
     * Set an image as the primary image.
     */
    public function setPrimary(
        Car $car,
        CarImage $image
    ): JsonResponse {
        $agency = request()->user()->agency;

        if (!$agency) {
            return response()->json([
                'message' => 'Agency profile not found.',
            ], 404);
        }

        if ($car->agency_id !== $agency->id) {
            return response()->json([
                'message' => 'This car does not belong to your agency.',
            ], 403);
        }

        if ($image->car_id !== $car->id) {
            return response()->json([
                'message' => 'This image does not belong to this car.',
            ], 403);
        }

        $car->images()->update([
            'is_primary' => false,
        ]);

        $image->update([
            'is_primary' => true,
        ]);

        return response()->json([
            'message' => 'Primary image updated successfully.',
            'image' => $image->fresh(),
        ]);
    }

    /**
     * Delete a car image.
     */
    public function destroy(
        Car $car,
        CarImage $image
    ): JsonResponse {
        $agency = request()->user()->agency;

        if (!$agency) {
            return response()->json([
                'message' => 'Agency profile not found.',
            ], 404);
        }

        if ($car->agency_id !== $agency->id) {
            return response()->json([
                'message' => 'This car does not belong to your agency.',
            ], 403);
        }

        if ($image->car_id !== $car->id) {
            return response()->json([
                'message' => 'This image does not belong to this car.',
            ], 403);
        }

        $image->delete();

        return response()->json([
            'message' => 'Car image deleted successfully.',
        ]);
    }
}