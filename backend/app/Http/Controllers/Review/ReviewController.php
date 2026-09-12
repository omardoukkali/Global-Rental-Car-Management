<?php

namespace App\Http\Controllers\Review;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewRequest;
use App\Models\Reservation;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(): JsonResponse
    {
        $reviews = Review::with([
            'user',
            'reservation.car',
            'reservation.agency',
        ])
            ->latest()
            ->get();

        return response()->json([
            'reviews' => $reviews,
        ]);
    }

    public function store(StoreReviewRequest $request): JsonResponse
    {
        $data = $request->validated();

        $reservation = Reservation::with('review')
            ->findOrFail($data['reservation_id']);

        if ($reservation->client_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to review this reservation.',
            ], 403);
        }

        if ($reservation->status !== 'completed') {
            return response()->json([
                'message' => 'You can only review a completed reservation.',
            ], 422);
        }

        if ($reservation->review) {
            return response()->json([
                'message' => 'This reservation already has a review.',
            ], 422);
        }

        $review = Review::create([
            'reservation_id' => $reservation->id,
            'user_id' => $request->user()->id,
            'car_rating' => $data['car_rating'],
            'agency_rating' => $data['agency_rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json([
            'message' => 'Review created successfully.',
            'review' => $review->load([
                'reservation',
                'user',
            ]),
        ], 201);
    }

    public function show(Review $review): JsonResponse
    {
        return response()->json([
            'review' => $review->load([
                'user',
                'reservation.car',
                'reservation.agency',
            ]),
        ]);
    }

    public function update(
        Request $request,
        Review $review
    ): JsonResponse {
        if ($review->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to update this review.',
            ], 403);
        }

        $validated = $request->validate([
            'car_rating' => [
                'sometimes',
                'required',
                'numeric',
                'min:1',
                'max:5',
            ],

            'agency_rating' => [
                'sometimes',
                'required',
                'numeric',
                'min:1',
                'max:5',
            ],

            'comment' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $review->update($validated);

        return response()->json([
            'message' => 'Review updated successfully.',
            'review' => $review->fresh()->load([
                'reservation',
                'user',
            ]),
        ]);
    }

    public function destroy(
        Request $request,
        Review $review
    ): JsonResponse {
        if ($review->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to delete this review.',
            ], 403);
        }

        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully.',
        ]);
    }
}