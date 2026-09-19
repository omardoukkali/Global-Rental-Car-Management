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
            if ($car->status !== 'available' || $car->agency?->status !== 'approved') {
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

        $data = $request->validated();

        DB::transaction(function () use ($reservation, $data) {
            // Lock the car first (same order as store) so overlap checks are serialized
            Car::where('id', $reservation->car_id)->lockForUpdate()->first();

            $reservation = Reservation::with('payment')
                ->where('id', $reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($reservation->status, ['pending', 'confirmed'])) {
                abort(response()->json([
                    'message' => 'This reservation cannot be updated.',
                ], 422));
            }

            $pickupPointId = $data['pickup_point_id']
                ?? $reservation->pickup_point_id;

            $returnPointId = $data['return_point_id']
                ?? $reservation->return_point_id;

            $startAt = Carbon::parse($data['start_at'] ?? $reservation->start_at);
            $endAt = Carbon::parse($data['end_at'] ?? $reservation->end_at);

            $datesChanged = !$startAt->equalTo($reservation->start_at)
                || !$endAt->equalTo($reservation->end_at);

            // The payment amount is fixed once paid, so the dates are too
            if ($datesChanged && $reservation->payment?->status === 'paid') {
                abort(response()->json([
                    'message' => 'Dates of a paid reservation cannot be changed.',
                ], 422));
            }

            $pickupPoint = AgencyPoint::findOrFail($pickupPointId);
            $returnPoint = AgencyPoint::findOrFail($returnPointId);

            if ($pickupPoint->agency_id !== $reservation->agency_id) {
                abort(response()->json([
                    'message' => 'Pickup point does not belong to the reservation agency.',
                ], 422));
            }

            if ($returnPoint->agency_id !== $reservation->agency_id) {
                abort(response()->json([
                    'message' => 'Return point does not belong to the reservation agency.',
                ], 422));
            }

            if (!$pickupPoint->is_active || !$pickupPoint->allows_pickup) {
                abort(response()->json([
                    'message' => 'Pickup point is not available for pickup.',
                ], 422));
            }

            if (!$returnPoint->is_active || !$returnPoint->allows_return) {
                abort(response()->json([
                    'message' => 'Return point is not available for return.',
                ], 422));
            }

            $hasOverlap = Reservation::where('car_id', $reservation->car_id)
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
                abort(response()->json([
                    'message' => 'Car is already reserved for the selected period.',
                ], 422));
            }

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
        });

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

        $payment = DB::transaction(function () use ($reservation) {
            $reservation = Reservation::with('payment')
                ->where('id', $reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($reservation->status, ['pending', 'confirmed'])) {
                abort(response()->json([
                    'message' => 'This reservation cannot be cancelled.',
                ], 422));
            }

            $payment = $reservation->payment;

            $reservation->update([
                'status' => 'cancelled',
            ]);

            if (!$payment) {
                return null;
            }

            $hoursUntilPickup = now()->diffInHours(
                Carbon::parse($reservation->start_at),
                false
            );

            if ($hoursUntilPickup >= 24) {
                Refund::create([
                    'payment_id' => $payment->id,
                    'agency_id' => $reservation->agency_id,
                    'percentage' => 100,
                    'refunded_amount' => $payment->amount,
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

            return $payment;
        });

        $relations = ['car', 'agency', 'pickupPoint', 'returnPoint'];

        if ($payment) {
            $relations[] = 'payment.refund';
        }

        return response()->json([
            'message' => 'Reservation cancelled successfully.',
            'reservation' => $reservation->fresh()->load($relations),
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

        return $this->confirmStep(
            $reservation,
            'client_pickup_confirmed_at',
            'agency_pickup_confirmed_at',
            'confirmed',
            ['status' => 'picked_up', 'picked_up_at' => now()],
            'Pickup has already been confirmed by the client.',
            'Only confirmed reservations can confirm pickup.',
            'Pickup confirmed successfully.'
        );
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

        return $this->confirmStep(
            $reservation,
            'agency_pickup_confirmed_at',
            'client_pickup_confirmed_at',
            'confirmed',
            ['status' => 'picked_up', 'picked_up_at' => now()],
            'Pickup has already been confirmed by the agency.',
            'Only confirmed reservations can confirm pickup.',
            'Pickup confirmed successfully.'
        );
    }

    public function confirmReturn(
        Request $request,
        Reservation $reservation
    ): JsonResponse {
        if ($reservation->client_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You are not authorized to confirm return for this reservation.',
            ], 403);
        }

        return $this->confirmStep(
            $reservation,
            'client_return_confirmed_at',
            'agency_return_confirmed_at',
            'picked_up',
            ['status' => 'completed', 'returned_at' => now()],
            'Return has already been confirmed by the client.',
            'Only picked up reservations can confirm return.',
            'Return confirmed successfully.'
        );
    }

    public function confirmAgencyReturn(
        Request $request,
        Reservation $reservation
    ): JsonResponse {
        $agency = $request->user()->agency;

        if (!$agency || $reservation->agency_id !== $agency->id) {
            return response()->json([
                'message' => 'You are not authorized to confirm return for this reservation.',
            ], 403);
        }

        return $this->confirmStep(
            $reservation,
            'agency_return_confirmed_at',
            'client_return_confirmed_at',
            'picked_up',
            ['status' => 'completed', 'returned_at' => now()],
            'Return has already been confirmed by the agency.',
            'Only picked up reservations can confirm return.',
            'Return confirmed successfully.'
        );
    }

    /**
     * Record one side of a two-party confirmation. The row is locked so that
     * when client and agency confirm at the same time, the second one always
     * sees the first and moves the reservation to its next status.
     */
    private function confirmStep(
        Reservation $reservation,
        string $ownField,
        string $otherField,
        string $requiredStatus,
        array $completedChanges,
        string $alreadyConfirmedMessage,
        string $wrongStatusMessage,
        string $successMessage
    ): JsonResponse {
        DB::transaction(function () use (
            $reservation,
            $ownField,
            $otherField,
            $requiredStatus,
            $completedChanges,
            $alreadyConfirmedMessage,
            $wrongStatusMessage
        ) {
            $reservation = Reservation::where('id', $reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($reservation->{$ownField}) {
                abort(response()->json([
                    'message' => $alreadyConfirmedMessage,
                ], 422));
            }

            if ($reservation->status !== $requiredStatus) {
                abort(response()->json([
                    'message' => $wrongStatusMessage,
                ], 422));
            }

            $changes = [$ownField => now()];

            if ($reservation->{$otherField}) {
                $changes = array_merge($changes, $completedChanges);
            }

            $reservation->update($changes);
        });

        return response()->json([
            'message' => $successMessage,
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
