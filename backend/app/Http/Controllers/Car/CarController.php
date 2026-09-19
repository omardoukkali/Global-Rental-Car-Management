<?php

namespace App\Http\Controllers\Car;

use App\Http\Controllers\Controller;
use App\Http\Requests\Car\PublicCarIndexRequest;
use App\Http\Requests\Car\StoreCarRequest;
use App\Http\Requests\Car\UpdateCarRequest;
use App\Models\Car;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CarController extends Controller
{
    public function publicIndex(PublicCarIndexRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $cars = Car::query()
            ->where('status', 'available')
            ->whereHas('agency', fn ($query) => $query->where('status', 'approved'))
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('brand', 'ilike', "%{$search}%")
                        ->orWhere('model', 'ilike', "%{$search}%");
                });
            })
            ->when($filters['city_id'] ?? null, fn ($query, $cityId) => $query->where('city_id', $cityId))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['transmission'] ?? null, fn ($query, $transmission) => $query->where('transmission', $transmission))
            ->when($filters['energy_type'] ?? null, fn ($query, $energy) => $query->where('energy_type', $energy))
            ->when($filters['min_seats'] ?? null, fn ($query, $seats) => $query->where('seats', '>=', $seats))
            ->when(isset($filters['min_price']), fn ($query) => $query->where('daily_price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn ($query) => $query->where('daily_price', '<=', $filters['max_price']))
            // Only cars free for the whole requested period (same rule as availability check)
            ->when(isset($filters['start_at'], $filters['end_at']), function ($query) use ($filters) {
                $query->whereDoesntHave('reservations', function ($query) use ($filters) {
                    $query
                        ->whereIn('status', ['pending', 'confirmed', 'picked_up'])
                        ->where('start_at', '<', $filters['end_at'])
                        ->where('end_at', '>', $filters['start_at']);
                });
            })
            ->when(
                $filters['sort'] ?? null,
                fn ($query, $sort) => match ($sort) {
                    'price_asc' => $query->orderBy('daily_price'),
                    'price_desc' => $query->orderByDesc('daily_price'),
                    'newest' => $query->latest(),
                }
            )
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
        if ($car->status !== 'available' || $car->agency?->status !== 'approved') {
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