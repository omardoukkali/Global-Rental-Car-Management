<?php

namespace App\Http\Controllers\Reservation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reservation\StoreReservationRequest;
use App\Http\Requests\Reservation\UpdateReservationRequest;
use App\Models\AgencyPoint;
use App\Models\Car;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Refund;
use App\Http\Requests\Reservation\RejectReservationRequest;

class ReservationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reservations = $request->user()
            ->reservations()
            ->with([
                'car',
                'agency',
                'pickupPoint',
                'returnPoint',
            ])
            ->latest()
            ->get();

        return response()->json([
            'reservations' => $reservations,
        ]);
    }

    public function store(StoreReservationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $reservation = DB::transaction(function () use ($request, $data) {
            // Lock the car row to prevent concurrent reservations
            $car = Car::with('agency')
                ->where('id', $data['car_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Check if the car is available
            if ($car->status !== 'available') {
                abort(response()->json([
                    'message' => 'Car is not available.',
                ], 422));
            }

            // Get pickup and return points
            $pickupPoint = AgencyPoint::findOrFail(
                $data['pickup_point_id']
            );

            $returnPoint = AgencyPoint::findOrFail(
                $data['return_point_id']
            );

            // Check pickup point belongs to the same agency
            if ($pickupPoint->agency_id !== $car->agency_id) {
                abort(response()->json([
                    'message' => 'Pickup point does not belong to the car agency.',
                ], 422));
            }

            // Check return point belongs to the same agency
            if ($returnPoint->agency_id !== $car->agency_id) {
                abort(response()->json([
                    'message' => 'Return point does not belong to the car agency.',
                ], 422));
            }

            // Check pickup point is active and allows pickup
            if (!$pickupPoint->is_active || !$pickupPoint->allows_pickup) {
                abort(response()->json([
                    'message' => 'Pickup point is not available for pickup.',
                ], 422));
            }

            // Check return point is active and allows return
            if (!$returnPoint->is_active || !$returnPoint->allows_return) {
                abort(response()->json([
                    'message' => 'Return point is not available for return.',
                ], 422));
            }

            // Check for overlapping reservations
            $hasOverlap = $car->reservations()
                ->whereIn('status', [
                    'pending',
                    'confirmed',
                    'picked_up',
                ])
                ->where('start_at', '<', $data['end_at'])
                ->where('end_at', '>', $data['start_at'])
                ->exists();

            if ($hasOverlap) {
                abort(response()->json([
                    'message' => 'Car is already reserved for the selected period.',
                ], 422));
            }

            // Calculate reservation duration
            $startAt = Carbon::parse($data['start_at']);
            $endAt = Carbon::parse($data['end_at']);

            $days = max(1, (int) ceil($startAt->diffInHours($endAt) / 24));

            // Calculate price
            $dailyPrice = $car->daily_price;
            $totalAmount = $days * $dailyPrice;

            // Create reservation
            return Reservation::create([
                'client_id' => $request->user()->id,
                'car_id' => $car->id,
                'agency_id' => $car->agency_id,
                'pickup_point_id' => $data['pickup_point_id'],
                'return_point_id' => $data['return_point_id'],
                'reference' => 'RES-' . strtoupper(Str::random(10)),
                'start_at' => $data['start_at'],
                'end_at' => $data['end_at'],
                'daily_price_snapshot' => $dailyPrice,
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);
        });

        return response()->json([
            'message' => 'Reservation created successfully.',
            'reservation' => $reservation->load([
                'car',
                'agency',
                'pickupPoint',
                'returnPoint',
            ]),
        ], 201);
    }

    public function show(
        Request $request,
        Reservation $reservation
    ): JsonResponse {
        if ($reservation->client_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to access this reservation.',
            ], 403);
        }

        return response()->json([
            'reservation' => $reservation->load([
                'car',
                'agency',
                'pickupPoint',
                'returnPoint',
            ]),
        ]);
    }

    public function update(
        UpdateReservationRequest $request,
        Reservation $reservation
    ): JsonResponse {
        if ($reservation->client_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to update this reservation.',
            ], 403);
        }

        if (!in_array($reservation->status, ['pending', 'confirmed'])) {
            return response()->json([
                'message' => 'This reservation cannot be updated.',
            ], 422);
        }

        $data = $request->validated();

        $pickupPointId = $data['pickup_point_id']
            ?? $reservation->pickup_point_id;

        $returnPointId = $data['return_point_id']
            ?? $reservation->return_point_id;

        $startAt = $data['start_at']
            ?? $reservation->start_at;

        $endAt = $data['end_at']
            ?? $reservation->end_at;

        $pickupPoint = AgencyPoint::findOrFail($pickupPointId);
        $returnPoint = AgencyPoint::findOrFail($returnPointId);

        if ($pickupPoint->agency_id !== $reservation->agency_id) {
            return response()->json([
                'message' => 'Pickup point does not belong to the reservation agency.',
            ], 422);
        }

        if ($returnPoint->agency_id !== $reservation->agency_id) {
            return response()->json([
                'message' => 'Return point does not belong to the reservation agency.',
            ], 422);
        }

        if (!$pickupPoint->is_active || !$pickupPoint->allows_pickup) {
            return response()->json([
                'message' => 'Pickup point is not available for pickup.',
            ], 422);
        }

        if (!$returnPoint->is_active || !$returnPoint->allows_return) {
            return response()->json([
                'message' => 'Return point is not available for return.',
            ], 422);
        }

        $hasOverlap = $reservation->car
            ->reservations()
            ->where('id', '!=', $reservation->id)
            ->whereIn('status', [
                'pending',
                'confirmed',
                'picked_up',
            ])
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->exists();

        if ($hasOverlap) {
            return response()->json([
                'message' => 'Car is already reserved for the selected period.',
            ], 422);
        }

        $startAt = Carbon::parse($startAt);
        $endAt = Carbon::parse($endAt);

        $days = max(1, (int) ceil($startAt->diffInHours($endAt) / 24));

        $dailyPrice = $reservation->daily_price_snapshot;
        $totalAmount = $days * $dailyPrice;

        $reservation->update([
            'pickup_point_id' => $pickupPointId,
            'return_point_id' => $returnPointId,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'total_amount' => $totalAmount,
        ]);

        return response()->json([
            'message' => 'Reservation updated successfully.',
            'reservation' => $reservation->fresh()->load([
                'car',
                'agency',
                'pickupPoint',
                'returnPoint',
            ]),
        ]);
    }
    public function reject(
        RejectReservationRequest $request,
        Reservation $reservation
    ): JsonResponse {
        $agency = $request->user()->agency;

        if (!$agency || $reservation->agency_id !== $agency->id) {
            return response()->json([
                'message' => 'You are not authorized to reject this reservation.',
            ], 403);
        }

        if ($reservation->status !== 'confirmed') {
            return response()->json([
                'message' => 'Only confirmed reservations can be rejected.',
            ], 422);
        }

        $payment = $reservation->payment;

        if (!$payment || $payment->status !== 'paid') {
            return response()->json([
                'message' => 'Only paid reservations can be rejected.',
            ], 422);
        }

        DB::transaction(function () use ($reservation, $payment, $request) {
            $reservation->update([
                'status' => 'rejected',
            ]);

            Refund::create([
                'payment_id' => $payment->id,
                'agency_id' => $reservation->agency_id,
                'percentage' => 100,
                'refunded_amount' => $payment->amount,
                'decision_source' => 'agency',
                'status' => 'processed',
                'reason' => $request->validated()['reason'],
                'decided_at' => now(),
                'processed_at' => now(),
            ]);

            $payment->update([
                'status' => 'refunded',
            ]);
        });

        return response()->json([
            'message' => 'Reservation rejected successfully.',
            'reservation' => $reservation->fresh()->load([
                'car',
                'agency',
                'pickupPoint',
                'returnPoint',
                'payment.refund',
            ]),
        ]);
    }
    public function cancel(
        Request $request,
        Reservation $reservation
    ): JsonResponse {
        if ($reservation->client_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to cancel this reservation.',
            ], 403);
        }

        if (!in_array($reservation->status, ['pending', 'confirmed'])) {
            return response()->json([
                'message' => 'This reservation cannot be cancelled.',
            ], 422);
        }

        $payment = $reservation->payment;

        if (!$payment) {
            $reservation->update([
                'status' => 'cancelled',
            ]);

            return response()->json([
                'message' => 'Reservation cancelled successfully.',
                'reservation' => $reservation->fresh()->load([
                    'car',
                    'agency',
                    'pickupPoint',
                    'returnPoint',
                ]),
            ]);
        }

        $hoursUntilPickup = now()->diffInHours(
            Carbon::parse($reservation->start_at),
            false
        );

        $reservation->update([
            'status' => 'cancelled',
        ]);

        if ($hoursUntilPickup >= 24) {
            $refundedAmount = $payment->amount;

            Refund::create([
                'payment_id' => $payment->id,
                'agency_id' => $reservation->agency_id,
                'percentage' => 100,
                'refunded_amount' => $refundedAmount,
                'decision_source' => 'automatic',
                'status' => 'processed',
                'reason' => 'Cancellation at least 24 hours before pickup.',
                'decided_at' => now(),
                'processed_at' => now(),
            ]);

            $payment->update([
                'status' => 'refunded',
            ]);
        } else {
            Refund::create([
                'payment_id' => $payment->id,
                'agency_id' => $reservation->agency_id,
                'percentage' => 50,
                'refunded_amount' => $payment->amount * 0.50,
                'decision_source' => 'automatic',
                'status' => 'pending',
                'reason' => 'Late cancellation. Waiting for agency decision.',
                'decided_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Reservation cancelled successfully.',
            'reservation' => $reservation->fresh()->load([
                'car',
                'agency',
                'pickupPoint',
                'returnPoint',
                'payment.refund',
            ]),
        ]);
    }

    public function confirmPickup(
        Request $request,
        Reservation $reservation
    ): JsonResponse {
        if ($reservation->client_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to confirm pickup for this reservation.',
            ], 403);
        }

        if ($reservation->status !== 'confirmed') {
            return response()->json([
                'message' => 'Only confirmed reservations can confirm pickup.',
            ], 422);
        }

        if (!$reservation->client_pickup_confirmed_at) {
            $reservation->update([
                'client_pickup_confirmed_at' => now(),
            ]);
        }

        if ($reservation->agency_pickup_confirmed_at) {
            $reservation->update([
                'status' => 'picked_up',
                'picked_up_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Pickup confirmed successfully.',
            'reservation' => $reservation->fresh()->load([
                'car',
                'agency',
                'pickupPoint',
                'returnPoint',
            ]),
        ]);
    }

    public function confirmAgencyPickup(
        Request $request,
        Reservation $reservation
    ): JsonResponse {
        $agency = $request->user()->agency;

        if (!$agency || $reservation->agency_id !== $agency->id) {
            return response()->json([
                'message' => 'You are not authorized to confirm pickup for this reservation.',
            ], 403);
        }

        if ($reservation->status !== 'confirmed') {
            return response()->json([
                'message' => 'Only confirmed reservations can confirm pickup.',
            ], 422);
        }

        if (!$reservation->agency_pickup_confirmed_at) {
            $reservation->update([
                'agency_pickup_confirmed_at' => now(),
            ]);
        }

        if ($reservation->client_pickup_confirmed_at) {
            $reservation->update([
                'status' => 'picked_up',
                'picked_up_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Pickup confirmed successfully.',
            'reservation' => $reservation->fresh()->load([
                'car',
                'agency',
                'pickupPoint',
                'returnPoint',
            ]),
        ]);
    }
    

    public function return(
        Request $request,
        Reservation $reservation
    ): JsonResponse {
        $agency = $request->user()->agency;

        if (!$agency || $reservation->agency_id !== $agency->id) {
            return response()->json([
                'message' => 'You are not authorized to return this reservation.',
            ], 403);
        }

        if ($reservation->status !== 'picked_up') {
            return response()->json([
                'message' => 'Only picked up reservations can be returned.',
            ], 422);
        }

        $reservation->update([
            'status' => 'completed',
            'returned_at' => now(),
        ]);

        return response()->json([
            'message' => 'Reservation returned successfully.',
            'reservation' => $reservation->fresh()->load([
                'car',
                'agency',
                'pickupPoint',
                'returnPoint',
            ]),
        ]);
    }

    public function dispute(
        Request $request,
        Reservation $reservation
    ): JsonResponse {
        if ($reservation->client_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to dispute this reservation.',
            ], 403);
        }

        if ($reservation->status !== 'picked_up') {
            return response()->json([
                'message' => 'Only picked up reservations can be disputed.',
            ], 422);
        }

        $reservation->update([
            'status' => 'disputed',
        ]);

        return response()->json([
            'message' => 'Reservation disputed successfully.',
            'reservation' => $reservation->fresh()->load([
                'car',
                'agency',
                'pickupPoint',
                'returnPoint',
            ]),
        ]);
    }
}