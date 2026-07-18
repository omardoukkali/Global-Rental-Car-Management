<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\CarImage;
use App\Models\City;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class WebOwnerController extends Controller
{
    // ── GET /owner/dashboard
    public function index(Request $request)
    {
        $agency = $request->user()->agency;
        if (!$agency) {
            return redirect('/')->with('error', 'You do not have an agency registered.');
        }

        $cars = Car::where('agency_id', $agency->id)
            ->with(['city', 'images' => fn($q) => $q->orderBy('is_primary', 'desc')])
            ->latest()
            ->paginate(10);

        return Inertia::render('Owner/Dashboard', [
            'agency' => $agency,
            'cars' => $cars,
            'cities' => City::all(),
        ]);
    }

    // ── POST /owner/cars
    public function storeCar(Request $request)
    {
        $agency = $request->user()->agency;
        if (!$agency) {
            return back()->with('error', 'Unauthorized.');
        }

        $validated = $request->validate([
            'brand'         => 'required|string|max:255',
            'model'         => 'required|string|max:255',
            'year'          => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'color'         => 'required|string|max:255',
            'plate_number'  => 'required|string|unique:cars,plate_number',
            'type'          => 'required|string',
            'transmission'  => 'required|in:automatic,manual',
            'seats'         => 'required|integer|min:2|max:9',
            'price_per_day' => 'required|numeric|min:0',
            'description'   => 'nullable|string',
            'city_id'       => 'required|exists:cities,id',
            'images'        => 'required|array|min:1',
            'images.*'      => 'image|mimes:jpg,jpeg,png,webp|max:2048', // 1. Strict mime + size validation
        ]);

        DB::transaction(function () use ($validated, $agency, $request) {
            $car = Car::create([
                'agency_id'     => $agency->id,
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

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('cars', 'public');
                    $car->images()->create([
                        'url'        => $path,
                        'is_primary' => $index === 0,
                    ]);
                }
            }
        });

        return back()->with('success', 'Car added successfully.');
    }

    // ── PUT /owner/cars/{car} (Note: form POST with _method=PUT to support multipart/form-data)
    public function updateCar(Request $request, Car $car)
    {
        $agency = $request->user()->agency;
        
        // 3. Ownership validation on update
        if (!$agency || $car->agency_id !== $agency->id) {
            return back()->with('error', 'Unauthorized.');
        }

        $validated = $request->validate([
            'brand'         => 'required|string|max:255',
            'model'         => 'required|string|max:255',
            'year'          => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'color'         => 'required|string|max:255',
            'plate_number'  => 'required|string|unique:cars,plate_number,' . $car->id,
            'type'          => 'required|string',
            'transmission'  => 'required|in:automatic,manual',
            'seats'         => 'required|integer|min:2|max:9',
            'price_per_day' => 'required|numeric|min:0',
            'description'   => 'nullable|string',
            'city_id'       => 'required|exists:cities,id',
            'status'        => 'required|in:available,rented,maintenance,inactive',
            'images.*'      => 'image|mimes:jpg,jpeg,png,webp|max:2048', // 1. Strict mime + size validation
        ]);

        DB::transaction(function () use ($car, $validated, $request) {
            $car->update($validated);

            if ($request->hasFile('images')) {
                $hasPrimary = $car->images()->where('is_primary', true)->exists();
                foreach ($request->file('images') as $image) {
                    $path = $image->store('cars', 'public');
                    $car->images()->create([
                        'url'        => $path,
                        'is_primary' => !$hasPrimary,
                    ]);
                    $hasPrimary = true;
                }
            }
        });

        return back()->with('success', 'Car updated successfully.');
    }

    // ── DELETE /owner/cars/{car}
    public function destroyCar(Request $request, Car $car)
    {
        $agency = $request->user()->agency;

        // 3. Ownership validation on delete
        if (!$agency || $car->agency_id !== $agency->id) {
            return back()->with('error', 'Unauthorized.');
        }

        $activeReservations = $car->reservations()->whereIn('status', ['confirmed', 'pending'])->exists();
        if ($activeReservations) {
            return back()->with('error', 'Cannot delete car with active reservations.');
        }

        // Optional image cleanup (disabled for audit trail/soft deletes depending on requirements)
        // foreach ($car->images as $image) {
        //     Storage::disk('public')->delete($image->url);
        // }
        // $car->images()->delete();

        $car->delete();

        return back()->with('success', 'Car deleted successfully.');
    }

    // ── DELETE /owner/cars/{car}/images/{image}
    public function destroyImage(Request $request, Car $car, CarImage $image)
    {
        $agency = $request->user()->agency;

        if (!$agency || $car->agency_id !== $agency->id) {
            return back()->with('error', 'Unauthorized.');
        }

        if ($image->car_id !== $car->id) {
            return back()->with('error', 'Image does not belong to this car.');
        }

        if ($car->images()->count() <= 1) {
            return back()->with('error', 'Cannot delete the only image of the car.');
        }

        $wasPrimary = $image->is_primary;
        $url = $image->url;

        DB::transaction(function () use ($car, $image, $wasPrimary) {
            $image->delete();

            if ($wasPrimary) {
                $firstImage = $car->images()->oldest()->first();
                if ($firstImage) {
                    $firstImage->update(['is_primary' => true]);
                }
            }
        });

        Storage::disk('public')->delete($url);

        return back()->with('success', 'Image removed successfully.');
    }
}
