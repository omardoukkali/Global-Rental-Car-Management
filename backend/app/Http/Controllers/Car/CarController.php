<?php

namespace App\Http\Controllers\Car;

use App\Http\Controllers\Controller;
use App\Http\Requests\Car\StoreCarRequest;
use App\Http\Requests\Car\UpdateCarRequest;
use App\Models\Car;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CarController extends Controller
{
    public function publicIndex(): JsonResponse
    {
        $cars = Car::query()
            ->where('status', 'available')
            ->with([
                'agency',
                'city',
                'images',
                'agency.agencyPoints' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->where(function ($query) {
                            $query
                                ->where('allows_pickup', true)
                                ->orWhere('allows_return', true);
                        });
                },
            ])
            ->get();

        return response()->json([
            'cars' => $cars,
        ]);
    }

    public function publicShow(Car $car): JsonResponse
    {
        if ($car->status !== 'available') {
            return response()->json([
                'message' => 'Car is not available.',
            ], 404);
        }

        return response()->json([
            'car' => $car->load([
                'agency',
                'city',
                'images',
                'agency.agencyPoints' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->where(function ($query) {
                            $query
                                ->where('allows_pickup', true)
                                ->orWhere('allows_return', true);
                        });
                },
            ]),
        ]);
    }
    public function store(StoreCarRequest $request): JsonResponse
    {
        $agency = $request->user()->agency;

        if (!$agency) {
            return response()->json([
                'message' => 'Agency profile not found.',
            ], 404);
        }

        $data = $request->validated();

        $images = $data['images'] ?? [];

        unset($data['images']);

        $car = DB::transaction(function () use ($agency, $data, $images) {

            $car = $agency->cars()->create($data);

            if (!empty($images)) {
                $car->images()->createMany($images);
            }

            return $car;
        });

        return response()->json([
            'message' => 'Car created successfully.',
            'car' => $car->load('images'),
        ], 201);
    }

    public function index(): JsonResponse
    {
        $agency = request()->user()->agency;

        if (!$agency) {
            return response()->json([
                'message' => 'Agency profile not found.',
            ], 404);
        }

        $cars = $agency->cars()
            ->with('images')
            ->get();

        return response()->json([
            'cars' => $cars,
        ]);
    }

    public function show(Car $car): JsonResponse
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

        return response()->json([
            'car' => $car->load('images'),
        ]);
    }

    public function update(
        UpdateCarRequest $request,
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

        $car->update($request->validated());

        return response()->json([
            'message' => 'Car updated successfully.',
            'car' => $car->fresh(),
        ]);
    }

    public function disable(Car $car): JsonResponse
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

        $car->update([
            'status' => 'unavailable',
        ]);

        return response()->json([
            'message' => 'Car disabled successfully.',
            'car' => $car->fresh(),
        ]);
    }
}