<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\City;
use App\Models\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WebCarController extends Controller
{
    // ── GET / — public landing page
    public function index(Request $request)
    {
        $cars = Car::where('status', 'available')
            ->whereHas('agency', fn($q) => $q->where('status', 'approved'))
            ->with(['agency', 'city', 'images'])
            ->when($request->city_id,      fn($q) => $q->where('city_id', $request->city_id))
            ->when($request->search,       fn($q) => $q->where(fn($sq) => $sq->where('brand', 'like', "%{$request->search}%")->orWhere('model', 'like', "%{$request->search}%")))
            ->when($request->type,         fn($q) => $q->where('type', $request->type))
            ->when($request->transmission, fn($q) => $q->where('transmission', $request->transmission))
            ->when($request->max_price,    fn($q) => $q->where('price_per_day', '<=', $request->max_price))
            ->orderBy('price_per_day', 'asc')
            ->paginate(9)
            ->withQueryString();

        $cities = City::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Landing', [
            'cars' => $cars,
            'cities' => $cities,
            'filters' => $request->only(['search', 'city_id', 'type', 'transmission', 'max_price']),
        ]);
    }

    // ── GET /cars/{car} — public vehicle detail page
    public function show(Car $car)
    {
        $car->load(['agency.city', 'city', 'images']);

        if ($car->status !== 'available' || $car->agency?->status !== 'approved') {
            abort(404);
        }

        // Fetch verified reviews for this car via reservations
        $reviews = Review::whereHas('reservation', fn($q) => $q->where('car_id', $car->id))
            ->with(['client'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('CarDetail', [
            'car' => $car,
            'reviews' => $reviews
        ]);
    }
}
