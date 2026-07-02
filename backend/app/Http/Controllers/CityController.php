<?php

namespace App\Http\Controllers;

use App\Http\Resources\CityResource;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CityController extends Controller
{
    // Public — for dropdowns
    public function index()
    {
        $cities = City::where('is_active', true)
                      ->orderBy('name')
                      ->get();

        return CityResource::collection($cities);
    }
    // Admin — add new city
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255|unique:cities,name',
            'region'  => 'required|string|max:255',
            'country' => 'required|string|max:255',
        ]);

        $city = City::create([
            'id'        => Str::uuid(),
            'name'      => $validated['name'],
            'region'    => $validated['region'],
            'country'   => $validated['country'],
            'is_active' => true,
        ]);

        return new CityResource($city);
    }
}
