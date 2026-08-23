<?php

namespace App\Http\Controllers;
use Illuminate\Http\JsonResponse;
use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index(): JsonResponse
    {
        $cities = City::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($cities);
    }
}
