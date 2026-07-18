<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Reservation;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WebReservationController extends Controller
{
    // ── GET /client/reservations — client dashboard
    public function index(Request $request)
    {
        $reservations = Reservation::where('client_id', $request->user()->id)
            ->with(['car.images', 'car.city', 'agency', 'payment.refund', 'review'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Client/Dashboard', [
            'reservations' => $reservations,
            'filters' => $request->only(['status']),
        ]);
    }

    // ── POST /client/reservations — create booking
    public function store(Request $request)
    {
        $validated = $request->validate([
            'car_id'     => 'required|uuid|exists:cars,id',
            'start_date' => 'required|date|after:now',
            'end_date'   => 'required|date|after:start_date',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $car = Car::lockForUpdate()->findOrFail($validated['car_id']);

            // Check car is available
            if ($car->status !== 'available') {
                return back()->with('error', 'Car is not available.');
            }

            // Check no overlapping reservations (inside transaction with row lock)
            $overlap = Reservation::where('car_id', $car->id)
                ->whereIn('status', ['confirmed', 'pending'])
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                          ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                          ->orWhere(function ($q) use ($validated) {
                              $q->where('start_date', '<=', $validated['start_date'])
                                ->where('end_date', '>=', $validated['end_date']);
                          });
                })->lockForUpdate()->exists();

            if ($overlap) {
                return back()->with('error', 'Car is already reserved for these dates.');
            }

            // Server-side price calculation — never trust client-submitted totals
            $start          = Carbon::parse($validated['start_date']);
            $end            = Carbon::parse($validated['end_date']);
            $totalDays      = max(1, $start->diffInDays($end));
            $totalAmount    = $totalDays * $car->price_per_day;
            $commission     = $totalAmount * 0.15;
            $agencyEarning  = $totalAmount - $commission;

            Reservation::create([
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

            return redirect()->route('client.reservations')
                ->with('success', 'Reservation created successfully!');
        });
    }

    // ── POST /client/reservations/{reservation}/pay — pay booking
    public function pay(Request $request, Reservation $reservation)
    {
        if ($reservation->client_id !== $request->user()->id) {
            return redirect()->route('client.reservations')
                ->with('error', 'Unauthorized.');
        }

        if ($reservation->status !== 'confirmed') {
            return back()->with('error', 'Reservation is not confirmed.');
        }

        if ($reservation->payment) {
            return back()->with('error', 'Reservation already paid.');
        }

        Payment::create([
            'reservation_id'    => $reservation->id,
            'amount'            => $reservation->total_amount,
            'commission_amount' => $reservation->commission_amount,
            'agency_amount'     => $reservation->agency_earning,
            'payment_method'    => 'card',
            'status'            => 'paid',
            'paid_at'           => now(),
        ]);

        return back()->with('success', 'Payment processed successfully!');
    }

    // ── DELETE /client/reservations/{reservation} — cancel booking
    public function cancel(Request $request, Reservation $reservation)
    {
        if ($reservation->client_id !== $request->user()->id) {
            return redirect()->route('client.reservations')
                ->with('error', 'Unauthorized.');
        }

        if (!in_array($reservation->status, ['confirmed', 'pending'])) {
            return back()->with('error', 'Reservation cannot be cancelled.');
        }

        if ($reservation->payment) {
            return back()->with('error', 'This reservation is paid — please request a refund instead.');
        }

        if (now()->greaterThanOrEqualTo($reservation->start_date)) {
            return back()->with('error', 'Cannot cancel — trip has already started.');
        }

        $reservation->update([
            'status'              => 'cancelled',
            'cancellation_reason' => 'Cancelled by client',
            'cancelled_at'        => now(),
        ]);

        return back()->with('success', 'Reservation cancelled.');
    }

    // ── POST /client/reservations/{reservation}/refund — request refund
    public function refund(Request $request, Reservation $reservation)
    {
        if ($reservation->client_id !== $request->user()->id) {
            return redirect()->route('client.reservations')
                ->with('error', 'Unauthorized.');
        }

        $payment = $reservation->payment;

        if (!$payment) {
            return back()->with('error', 'No payment found for this reservation.');
        }

        if ($payment->refund) {
            return back()->with('error', 'Payment already refunded.');
        }

        if (now()->greaterThanOrEqualTo($reservation->start_date)) {
            return back()->with('error', 'Cannot refund — trip has already started.');
        }

        // Cancellation policy: >= 48h = full refund, < 48h = 70% refund
        $hoursUntilTrip = now()->diffInHours($reservation->start_date, false);

        if ($hoursUntilTrip >= 48) {
            $refundAmount    = $payment->amount;
            $cancellationFee = 0;
            $reason          = 'before_48h';
        } else {
            $refundAmount    = $payment->amount * 0.70;
            $cancellationFee = $payment->amount * 0.30;
            $reason          = 'within_48h';
        }

        Refund::create([
            'payment_id'       => $payment->id,
            'amount'           => $refundAmount,
            'cancellation_fee' => $cancellationFee,
            'platform_fee'     => $cancellationFee * 0.15,
            'agency_fee'       => $cancellationFee * 0.85,
            'reason'           => $reason,
            'status'           => 'processed',
        ]);

        $reservation->update([
            'status'              => 'cancelled',
            'cancellation_reason' => 'Cancelled by client — refund processed',
            'cancelled_at'        => now(),
        ]);

        $payment->update([
            'status' => $reason === 'before_48h' ? 'refunded' : 'partially_refunded',
        ]);

        return back()->with('success', 'Refund processed successfully.');
    }

    // ── POST /client/reservations/{reservation}/review — submit review
    public function review(Request $request, Reservation $reservation)
    {
        if ($reservation->client_id !== $request->user()->id) {
            return redirect()->route('client.reservations')
                ->with('error', 'Unauthorized.');
        }

        if ($reservation->status !== 'completed') {
            return back()->with('error', 'You can only review completed reservations.');
        }

        if ($reservation->review) {
            return back()->with('error', 'You already reviewed this reservation.');
        }

        $validated = $request->validate([
            'car_rating'    => 'required|numeric|min:1|max:5',
            'agency_rating' => 'required|numeric|min:1|max:5',
            'comment'       => 'nullable|string|max:1000',
        ]);

        Review::create([
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

        return back()->with('success', 'Review submitted — thank you!');
    }
}
