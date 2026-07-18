<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentResource;
use App\Http\Resources\RefundResource;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    // ── POST /api/client/reservations/{reservation}/pay — client
    public function pay(Request $request, Reservation $reservation)
    {
        // Check ownership
        if ($reservation->client_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        // Check reservation status
        if ($reservation->status !== 'confirmed') {
            return response()->json([
                'message' => 'Reservation is not confirmed'
            ], 422);
        }

        // Check if already paid
        if ($reservation->payment) {
            return response()->json([
                'message' => 'Reservation already paid'
            ], 422);
        }

        // Create payment
        $payment = Payment::create([
            'id'                => Str::uuid(),
            'reservation_id'    => $reservation->id,
            'amount'            => $reservation->total_amount,
            'commission_amount' => $reservation->commission_amount,
            'agency_amount'     => $reservation->agency_earning,
            'payment_method'    => 'card',
            'status'            => 'paid',
            'paid_at'           => now(),
        ]);

        return new PaymentResource($payment->load('reservation'));
    }

    // ── POST /api/client/reservations/{reservation}/refund — client
    public function refund(Request $request, Reservation $reservation)
    {
        // Check ownership
        if ($reservation->client_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $payment = $reservation->payment;

        // Check payment exists
        if (!$payment) {
            return response()->json([
                'message' => 'No payment found for this reservation'
            ], 422);
        }

        // Check already refunded
        if ($payment->refund) {
            return response()->json([
                'message' => 'Payment already refunded'
            ], 422);
        }

        // Check trip not started
        if (now()->greaterThanOrEqualTo($reservation->start_date)) {
            return response()->json([
                'message' => 'Cannot refund — trip has already started'
            ], 422);
        }

        // Calculate refund based on cancellation policy
        $hoursUntilTrip = now()->diffInHours($reservation->start_date, false);

        if ($hoursUntilTrip >= 48) {
            // Full refund
            $refundAmount      = $payment->amount;
            $cancellationFee   = 0;
            $reason            = 'before_48h';
        } else {
            // 70% refund
            $refundAmount      = $payment->amount * 0.70;
            $cancellationFee   = $payment->amount * 0.30;
            $reason            = 'within_48h';
        }

        // Create refund
        $refund = Refund::create([
            'id'               => Str::uuid(),
            'payment_id'       => $payment->id,
            'amount'           => $refundAmount,
            'cancellation_fee' => $cancellationFee,
            'platform_fee'     => $cancellationFee * 0.15,
            'agency_fee'       => $cancellationFee * 0.85,
            'reason'           => $reason,
            'status'           => 'processed',
            'processed_at'     => now(),
        ]);

        // Update reservation status
        $reservation->update([
            'status'              => 'cancelled',
            'cancellation_reason' => 'Cancelled by client — refund processed',
            'cancelled_at'        => now(),
        ]);

        // Update payment status
        $payment->update([
            'status' => $reason === 'before_48h' ? 'refunded' : 'partially_refunded',
        ]);

        return new RefundResource($refund->load('payment'));
    }

    // ── GET /api/client/reservations/{reservation}/payment — client
    public function clientShow(Request $request, Reservation $reservation)
    {
        if ($reservation->client_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $payment = $reservation->payment;

        if (!$payment) {
            return response()->json([
                'message' => 'No payment found'
            ], 404);
        }

        return new PaymentResource($payment->load('refund'));
    }

    // ── GET /api/agency/payments — agency
    public function agencyIndex(Request $request)
    {
        $payments = Payment::whereHas('reservation', function ($q) use ($request) {
            $q->where('agency_id', $request->user()->agency->id);
        })
        ->with(['reservation.car', 'reservation.client', 'refund'])
        ->when($request->status, fn($q) => $q->where('status', $request->status))
        ->orderBy('created_at', 'desc')
        ->paginate(10);

        return PaymentResource::collection($payments);
    }

    // ── GET /api/admin/payments — admin
    public function adminIndex(Request $request)
    {
        $payments = Payment::with(['reservation.car', 'reservation.client', 'reservation.agency', 'refund'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return PaymentResource::collection($payments);
    }

    // ── PUT /api/admin/payments/{payment}/release — admin
    public function release(Payment $payment)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($payment) {
            $locked = Payment::lockForUpdate()->findOrFail($payment->id);
            
            if ($locked->status !== 'paid') {
                abort(422, 'Payment cannot be released');
            }

            $locked->update([
                'status'      => 'released',
                'released_at' => now(),
            ]);

            // Update reservation status
            $locked->reservation->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            return new PaymentResource($locked);
        });
    }
}