<?php

namespace App\Http\Controllers;

use App\Http\Resources\CarImageResource;
use App\Models\Car;
use App\Models\CarImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CarImageController extends Controller
{
    // ── GET /api/agency/cars/{car}/images
    public function index(Car $car)
    {
        $images = $car->images()->orderBy('sort_order')->get();

        return CarImageResource::collection($images);
    }

    // ── POST /api/agency/cars/{car}/images
    public function store(Request $request, Car $car)
    {
        // Check ownership
        if ($car->agency_id !== $request->user()->agency->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'image'      => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ]);

        // Store image
        $path = $request->file('image')->store('cars', 'public');
        $url  = Storage::url($path);

        // If primary → unset others
        if ($request->is_primary) {
            $car->images()->update(['is_primary' => false]);
        }

        // If first image → make it primary automatically
        $isPrimary = $request->is_primary
            ?? ($car->images()->count() === 0);

        $image = CarImage::create([
            'id'         => Str::uuid(),
            'car_id'     => $car->id,
            'url'        => $url,
            'is_primary' => $isPrimary,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return new CarImageResource($image);
    }

    // ── PUT /api/agency/cars/{car}/images/{image}/primary
    public function setPrimary(Request $request, Car $car, CarImage $image)
    {
        if ($car->agency_id !== $request->user()->agency->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        // Unset all primary
        $car->images()->update(['is_primary' => false]);

        // Set this one as primary
        $image->update(['is_primary' => true]);

        return new CarImageResource($image);
    }

    // ── DELETE /api/agency/cars/{car}/images/{image}
    public function destroy(Request $request, Car $car, CarImage $image)
    {
        if ($car->agency_id !== $request->user()->agency->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        // Delete file from storage
        $path = str_replace('/storage/', '', $image->url);
        Storage::disk('public')->delete($path);

        $image->delete();

        return response()->json([
            'message' => 'Image deleted successfully'
        ]);
    }
}