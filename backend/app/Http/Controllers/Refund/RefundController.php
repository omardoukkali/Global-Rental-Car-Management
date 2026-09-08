<?php

namespace App\Http\Controllers\Refund;

use App\Http\Controllers\Controller;
use App\Http\Requests\Refund\StoreRefundRequest;
use App\Http\Requests\Refund\UpdateRefundDecisionRequest;
use App\Models\Payment;
use App\Models\Refund;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    public function store(StoreRefundRequest $request): JsonResponse
    {
        $data = $request->validated();

        $refund = DB::transaction(function () use ($request, $data) {
            $payment = Payment::with('reservation')
                ->where('id', $data['payment_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $reservation = $payment->reservation;

            if (!$reservation) {
                abort(response()->json([
                    'message' => 'Reservation not found for this payment.',
                ], 404));
            }

            if ($payment->status !== 'paid') {
                abort(response()->json([
                    'message' => 'Only paid payments can be refunded.',
                ], 422));
            }

            if ($payment->refund) {
                abort(response()->json([
                    'message' => 'This payment already has a refund.',
                ], 422));
            }

            if ($reservation->status === 'picked_up') {
                abort(response()->json([
                    'message' => 'This reservation cannot be refunded after pickup.',
                ], 422));
            }

            if ($request->user()->role === 'agency') {
                if ($reservation->agency_id !== $request->user()->agency?->id) {
                    abort(response()->json([
                        'message' => 'You are not authorized to refund this payment.',
                    ], 403));
                }
            }

            $now = Carbon::now();
            $pickupAt = Carbon::parse($reservation->start_at);

            $hoursUntilPickup = $now->diffInHours($pickupAt, false);

            /*
             * At least 24 hours before pickup:
             * automatic 100% refund.
             */
            if ($hoursUntilPickup >= 24) {
                $percentage = 100;
                $decisionSource = 'automatic';
            } else {
                /*
                 * Late cancellation:
                 * minimum 50%.
                 *
                 * Agency may increase it up to 100%.
                 */
                $percentage = $data['percentage'] ?? 50;

                $decisionSource = $request->user()->role === 'agency'
                    ? 'agency'
                    : 'automatic';
            }

            $refundedAmount = $payment->amount * ($percentage / 100);

            return Refund::create([
                'payment_id' => $payment->id,
                'agency_id' => $reservation->agency_id,
                'percentage' => $percentage,
                'refunded_amount' => $refundedAmount,
                'decision_source' => $decisionSource,
                'status' => 'processed',
                'reason' => $data['reason'] ?? null,
                'decided_at' => now(),
                'processed_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'Refund processed successfully.',
            'refund' => $refund->load([
                'payment.reservation',
                'agency',
            ]),
        ], 201);
    }

    public function decide(
        UpdateRefundDecisionRequest $request,
        Refund $refund
    ): JsonResponse {
        $agency = $request->user()->agency;

        if (!$agency || $refund->agency_id !== $agency->id) {
            return response()->json([
                'message' => 'You are not authorized to decide this refund.',
            ], 403);
        }

        if ($refund->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending refunds can receive an agency decision.',
            ], 422);
        }

        $refund->update([
            'percentage' => $request->validated()['percentage'],
            'refunded_amount' => $refund->payment->amount
                * ($request->validated()['percentage'] / 100),
            'decision_source' => 'agency',
            'status' => 'processed',
            'reason' => $request->validated()['reason'] ?? $refund->reason,
            'decided_at' => now(),
            'processed_at' => now(),
        ]);

        $refund->payment->update([
            'status' => 'refunded',
        ]);

        return response()->json([
            'message' => 'Refund decision processed successfully.',
            'refund' => $refund->fresh()->load([
                'payment.reservation',
                'agency',
            ]),
        ]);
    }
}