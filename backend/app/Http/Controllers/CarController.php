<?php

namespace App\Http\Controllers;
use App\Http\Resources\CarResource;
use App\Http\Resources\CarImageResource;
use App\Models\Car;
use App\Models\CarImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CarController extends Controller
{
    // ── GET /api/cars — public
    public function index(Request $request)
    {
        $cars = Car::where('status', 'available')
            ->whereHas('agency', fn($q) => $q->where('status', 'approved'))
            ->with(['agency', 'city', 'images'])
            ->when($request->city_id,     fn($q) => $q->where('city_id', $request->city_id))
            ->when($request->brand,       fn($q) => $q->where('brand', $request->brand))
            ->when($request->type,        fn($q) => $q->where('type', $request->type))
            ->when($request->transmission,fn($q) => $q->where('transmission', $request->transmission))
            ->when($request->seats,       fn($q) => $q->where('seats', $request->seats))
            ->when($request->min_price,   fn($q) => $q->where('price_per_day', '>=', $request->min_price))
            ->when($request->max_price,   fn($q) => $q->where('price_per_day', '<=', $request->max_price))
            ->orderBy('price_per_day', 'asc')
            ->paginate(12);

        return CarResource::collection($cars);
    }

    // ── GET /api/cars/{car} — public
    public function show(Car $car)
    {
        $car->load('agency');

        if ($car->status !== 'available' || $car->agency?->status !== 'approved') {
            abort(404);
        }

        $car->load(['agency.city', 'city', 'images']);

        return new CarResource($car);
    }

    // ── GET /api/agency/cars — agency owner
    public function agencyIndex(Request $request)
    {
        $cars = Car::where('agency_id', $request->user()->agency->id)
            ->with(['city', 'images'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return CarResource::collection($cars);
    }

    // ── POST /api/agency/cars — agency owner
    public function store(Request $request)
    {
        $validated = $request->validate([
            'city_id'       => 'required|uuid|exists:cities,id',
            'brand'         => 'required|string|max:255',
            'model'         => 'required|string|max:255',
            'year'          => 'required|integer|min:2000|max:2026',
            'color'         => 'required|string|max:255',
            'plate_number'  => 'required|string|unique:cars,plate_number',
            'type'          => 'required|in:sedan,suv,hatchback,coupe,van,truck',
            'transmission'  => 'required|in:automatic,manual',
            'seats'         => 'required|integer|min:2|max:12',
            'price_per_day' => 'required|numeric|min:0',
            'description'   => 'nullable|string',
        ]);

        $car = Car::create([
            'id'            => Str::uuid(),
            'agency_id'     => $request->user()->agency->id,
            'city_id'       => $validated['city_id'],
            'brand'         => $validated['brand'],
            'model'         => $validated['model'],
            'year'          => $validated['year'],
            'color'         => $validated['color'],
            'plate_number'  => $validated['plate_number'],
            'type'          => $validated['type'],
            'transmission'  => $validated['transmission'],
            'seats'         => $validated['seats'],
            'price_per_day' => $validated['price_per_day'],
            'description'   => $validated['description'],
            'status'        => 'available',
        ]);

        return new CarResource($car->load(['city', 'images']));
    }

    // ── PUT /api/agency/cars/{car} — agency owner
    public function update(Request $request, Car $car)
    {
        // Make sure agency owns this car
        if ($car->agency_id !== $request->user()->agency->id) {
            return response()->json([
                'message' => 'Unauthorized — this car does not belong to your agency'
            ], 403);
        }

        $validated = $request->validate([
            'city_id'       => 'sometimes|uuid|exists:cities,id',
            'brand'         => 'sometimes|string|max:255',
            'model'         => 'sometimes|string|max:255',
            'year'          => 'sometimes|integer|min:2000|max:2026',
            'color'         => 'sometimes|string|max:255',
            'plate_number'  => 'sometimes|string|unique:cars,plate_number,' . $car->id,
            'type'          => 'sometimes|in:sedan,suv,hatchback,coupe,van,truck',
            'transmission'  => 'sometimes|in:automatic,manual',
            'seats'         => 'sometimes|integer|min:2|max:12',
            'price_per_day' => 'sometimes|numeric|min:0',
            'description'   => 'nullable|string',
            'status'        => 'sometimes|in:available,maintenance,inactive',
        ]);

        $car->update($validated);

        return new CarResource($car->load(['city', 'images']));
    }

    // ── DELETE /api/agency/cars/{car} — agency owner
    public function destroy(Request $request, Car $car)
    {
        if ($car->agency_id !== $request->user()->agency->id) {
            return response()->json([
                'message' => 'Unauthorized — this car does not belong to your agency'
            ], 403);
        }

        $car->delete();

        return response()->json([
            'message' => 'Car deleted successfully'
        ]);
    }

    // ── POST /api/agency/cars/{car}/images — agency owner
    public function uploadImage(Request $request, Car $car)
    {
        if ($car->agency_id !== $request->user()->agency->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'url'        => 'required|string|url',
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ]);

        // If new image is primary, unset others
        if ($request->is_primary) {
            $car->images()->update(['is_primary' => false]);
        }

        $image = CarImage::create([
            'id'         => Str::uuid(),
            'car_id'     => $car->id,
            'url'        => $request->url,
            'is_primary' => $request->is_primary ?? false,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return new CarImageResource($image);
    }

    // ── DELETE /api/agency/cars/{car}/images/{image} — agency owner
    public function deleteImage(Request $request, Car $car, CarImage $image)
    {
        if ($car->agency_id !== $request->user()->agency->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $image->delete();

        return response()->json([
            'message' => 'Image deleted successfully'
        ]);
    }

    // ── GET /api/admin/cars — admin
    public function adminIndex(Request $request)
    {
        $cars = Car::with(['agency', 'city', 'images'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return CarResource::collection($cars);
    }
}
