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

        // Only available cars from approved agencies
        $query = Car::query()
            ->where('status', 'available')
            ->whereHas('agency', function ($agencyQuery) {
                $agencyQuery->where('status', 'approved');
            });

        // Search by brand or model (ilike = case-insensitive in PostgreSQL)
        if (isset($filters['q'])) {
            $search = '%' . $filters['q'] . '%';

            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('brand', 'ilike', $search)
                    ->orWhere('model', 'ilike', $search);
            });
        }

        if (isset($filters['city_id'])) {
            $query->where('city_id', $filters['city_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['transmission'])) {
            $query->where('transmission', $filters['transmission']);
        }

        if (isset($filters['energy_type'])) {
            $query->where('energy_type', $filters['energy_type']);
        }

        if (isset($filters['min_seats'])) {
            $query->where('seats', '>=', $filters['min_seats']);
        }

        if (isset($filters['min_price'])) {
            $query->where('daily_price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('daily_price', '<=', $filters['max_price']);
        }

        // Only cars with no active reservation during the requested dates
        if (isset($filters['start_at'])) {
            $startAt = $filters['start_at'];
            $endAt = $filters['end_at'];

            $query->whereDoesntHave('reservations', function ($reservationQuery) use ($startAt, $endAt) {
                $reservationQuery->whereIn('status', ['pending', 'confirmed', 'picked_up'])
                    ->where('start_at', '<', $endAt)
                    ->where('end_at', '>', $startAt);
            });
        }

        $sort = $filters['sort'] ?? null;

        if ($sort === 'price_asc') {
            $query->orderBy('daily_price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('daily_price', 'desc');
        } elseif ($sort === 'newest') {
            $query->orderBy('created_at', 'desc');
        }

        $cars = $query
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