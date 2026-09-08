<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $data = $request->validated();

        $payment = DB::transaction(function () use ($request, $data) {
            $reservation = Reservation::with('payment')
                ->where('id', $data['reservation_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($reservation->client_id !== $request->user()->id) {
                abort(response()->json([
                    'message' => 'You are not authorized to pay for this reservation.',
                ], 403));
            }

            if ($reservation->status === 'cancelled') {
                abort(response()->json([
                    'message' => 'Cancelled reservations cannot be paid.',
                ], 422));
            }

            if ($reservation->payment) {
                abort(response()->json([
                    'message' => 'This reservation already has a payment.',
                ], 422));
            }

            $amount = $reservation->total_amount;

            $commissionRate = 15;

            $platformCommission = $amount * ($commissionRate / 100);

            $agencyAmount = $amount - $platformCommission;

            return Payment::create([
                'reservation_id' => $reservation->id,
                'amount' => $amount,
                'commission_rate' => $commissionRate,
                'platform_commission' => $platformCommission,
                'agency_amount' => $agencyAmount,
                'transaction_id' => 'TXN-' . strtoupper(Str::random(12)),
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        });

        return response()->json([
            'message' => 'Payment processed successfully.',
            'payment' => $payment->load('reservation'),
        ], 201);
    }
}