<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReviewResource;
use App\Models\Car;
use App\Models\Agency;
use App\Models\Review;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReviewController extends Controller
{
    // ── GET /api/cars/{car}/reviews — public
    public function carReviews(Car $car)
    {
        $reviews = Review::whereHas('reservation', fn($q) => $q->where('car_id', $car->id))
            ->with(['client', 'reservation'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return ReviewResource::collection($reviews);
    }

    // ── GET /api/agencies/{agency}/reviews — public
    public function agencyReviews(Agency $agency)
    {
        $reviews = Review::whereHas('reservation', fn($q) => $q->where('agency_id', $agency->id))
            ->with(['client', 'reservation'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return ReviewResource::collection($reviews);
    }

    // ── POST /api/client/reservations/{reservation}/review — client
    public function store(Request $request, Reservation $reservation)
    {
        // Check ownership
        if ($reservation->client_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        // Check reservation is completed
        if ($reservation->status !== 'completed') {
            return response()->json([
                'message' => 'You can only review completed reservations'
            ], 422);
        }

        // Check not already reviewed
        if ($reservation->review) {
            return response()->json([
                'message' => 'You already reviewed this reservation'
            ], 422);
        }

        $validated = $request->validate([
            'car_rating'    => 'required|numeric|min:1|max:5',
            'agency_rating' => 'required|numeric|min:1|max:5',
            'comment'       => 'nullable|string|max:1000',
        ]);

        $review = Review::create([
            'id'             => Str::uuid(),
            'reservation_id' => $reservation->id,
            'client_id'      => $request->user()->id,
            'car_rating'     => $validated['car_rating'],
            'agency_rating'  => $validated['agency_rating'],
            'comment'        => $validated['comment'],
        ]);

        // Update car avg_rating
        $car = $reservation->car;
        $car->update([
            'avg_rating'    => Review::whereHas('reservation', fn($q) => $q->where('car_id', $car->id))->avg('car_rating'),
            'total_reviews' => Review::whereHas('reservation', fn($q) => $q->where('car_id', $car->id))->count(),
        ]);

        // Update agency avg_rating
        $agency = $reservation->agency;
        $agency->update([
            'avg_rating'    => Review::whereHas('reservation', fn($q) => $q->where('agency_id', $agency->id))->avg('agency_rating'),
            'total_reviews' => Review::whereHas('reservation', fn($q) => $q->where('agency_id', $agency->id))->count(),
        ]);

        return new ReviewResource($review->load(['client', 'reservation']));
    }

    // ── GET /api/client/reviews — client
    public function clientIndex(Request $request)
    {
        $reviews = Review::where('client_id', $request->user()->id)
            ->with(['reservation.car', 'reservation.agency'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return ReviewResource::collection($reviews);
    }

    // ── GET /api/admin/reviews — admin
    public function adminIndex(Request $request)
    {
        $reviews = Review::withTrashed()
            ->with(['client', 'reservation.car', 'reservation.agency'])
            ->when($request->deleted, fn($q) => $q->onlyTrashed())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return ReviewResource::collection($reviews);
    }

    // ── DELETE /api/admin/reviews/{review} — admin soft delete
    public function destroy(Review $review)
    {
        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully'
        ]);
    }

    // ── PUT /api/admin/reviews/{review}/restore — admin restore
    public function restore($id)
    {
        $review = Review::withTrashed()->findOrFail($id);
        $review->restore();

        return response()->json([
            'message' => 'Review restored successfully'
        ]);
    }
}