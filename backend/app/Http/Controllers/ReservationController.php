<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReservationResource;
use App\Models\Car;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReservationController extends Controller
{
    // ── POST /api/client/reservations — client
    public function store(Request $request)
    {
        $validated = $request->validate([
            'car_id'     => 'required|uuid|exists:cars,id',
            'start_date' => 'required|date|after:now',
            'end_date'   => 'required|date|after:start_date',
        ]);

        $car = Car::findOrFail($validated['car_id']);

        // Check car is available
        if ($car->status !== 'available') {
            return response()->json([
                'message' => 'Car is not available'
            ], 422);
        }

        // Check no overlapping reservations
        $overlap = Reservation::where('car_id', $car->id)
            ->whereIn('status', ['confirmed', 'pending'])
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                      ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                      ->orWhere(function ($q) use ($validated) {
                          $q->where('start_date', '<=', $validated['start_date'])
                            ->where('end_date', '>=', $validated['end_date']);
                      });
            })->exists();

        if ($overlap) {
            return response()->json([
                'message' => 'Car is already reserved for these dates'
            ], 422);
        }

        // Calculate amounts
        $start          = \Carbon\Carbon::parse($validated['start_date']);
        $end            = \Carbon\Carbon::parse($validated['end_date']);
        $totalDays      = $start->diffInDays($end);
        $totalAmount    = $totalDays * $car->price_per_day;
        $commission     = $totalAmount * 0.15;
        $agencyEarning  = $totalAmount - $commission;

        // Create reservation
        $reservation = Reservation::create([
            'id'                     => Str::uuid(),
            'client_id'              => $request->user()->id,
            'car_id'                 => $car->id,
            'agency_id'              => $car->agency_id,
            'reference_number'       => 'RES-' . strtoupper(Str::random(8)),
            'start_date'             => $validated['start_date'],
            'end_date'               => $validated['end_date'],
            'price_per_day_snapshot' => $car->price_per_day,
            'total_amount'           => $totalAmount,
            'commission_amount'      => $commission,
            'agency_earning'         => $agencyEarning,
            'status'                 => 'confirmed',
        ]);

        return new ReservationResource(
            $reservation->load(['car.images', 'car.city', 'agency', 'client'])
        );
    }

    // ── GET /api/client/reservations — client
    public function clientIndex(Request $request)
    {
        $reservations = Reservation::where('client_id', $request->user()->id)
            ->with(['car.images', 'car.city', 'agency', 'payment'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return ReservationResource::collection($reservations);
    }

    // ── GET /api/client/reservations/{reservation} — client
    public function clientShow(Request $request, Reservation $reservation)
    {
        // Make sure reservation belongs to this client
        if ($reservation->client_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $reservation->load(['car.images', 'car.city', 'agency', 'payment.refund', 'review']);

        return new ReservationResource($reservation);
    }

    // ── DELETE /api/client/reservations/{reservation} — client cancel
    public function cancel(Request $request, Reservation $reservation)
    {
        // Check ownership
        if ($reservation->client_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        // Check if cancellable
        if (!in_array($reservation->status, ['confirmed', 'pending'])) {
            return response()->json([
                'message' => 'Reservation cannot be cancelled'
            ], 422);
        }

        // Check if trip already started
        if (now()->greaterThanOrEqualTo($reservation->start_date)) {
            return response()->json([
                'message' => 'Cannot cancel — trip has already started'
            ], 422);
        }

        $reservation->update([
            'status'              => 'cancelled',
            'cancellation_reason' => $request->reason ?? 'Cancelled by client',
            'cancelled_at'        => now(),
        ]);

        return response()->json([
            'message'     => 'Reservation cancelled successfully',
            'reservation' => new ReservationResource($reservation),
        ]);
    }

    // ── GET /api/agency/reservations — agency
    public function agencyIndex(Request $request)
    {
        $reservations = Reservation::where('agency_id', $request->user()->agency->id)
            ->with(['car', 'client', 'payment'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return ReservationResource::collection($reservations);
    }

    // ── GET /api/agency/reservations/{reservation} — agency
    public function agencyShow(Request $request, Reservation $reservation)
    {
        if ($reservation->agency_id !== $request->user()->agency->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $reservation->load(['car', 'client', 'payment.refund']);

        return new ReservationResource($reservation);
    }

    // ── GET /api/admin/reservations — admin
    public function adminIndex(Request $request)
    {
        $reservations = Reservation::with(['car', 'client', 'agency', 'payment'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return ReservationResource::collection($reservations);
    }
}