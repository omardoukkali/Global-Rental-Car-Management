<?php

namespace Database\Seeders;

use App\Models\Refund;
use App\Models\Reservation;
use Illuminate\Database\Seeder;

class RefundSeeder extends Seeder
{
    /**
     * Refunds follow the same rules as the app:
     * - rejected by the agency          -> 100% refund
     * - cancelled 24h+ before pickup    -> 100% automatic refund
     * - late cancellation (past pickup) -> 50% (agency did not decide in time)
     */
    public function run(): void
    {
        $reservations = Reservation::with('payment')
            ->whereIn('status', ['rejected', 'cancelled'])
            ->get();

        foreach ($reservations as $reservation) {
            $payment = $reservation->payment;

            if (!$payment) {
                continue;
            }

            if ($reservation->status === 'rejected') {
                $percentage = 100;
                $decisionSource = 'agency';
                $reason = 'Véhicule indisponible à ces dates.';
            } elseif ($reservation->start_at->isFuture()) {
                $percentage = 100;
                $decisionSource = 'automatic';
                $reason = 'Cancellation at least 24 hours before pickup.';
            } else {
                $percentage = 50;
                $decisionSource = 'automatic';
                $reason = 'Agency did not make a decision within 2 hours.';
            }

            Refund::create([
                'payment_id' => $payment->id,
                'agency_id' => $reservation->agency_id,
                'percentage' => $percentage,
                'refunded_amount' => round($payment->amount * $percentage / 100, 2),
                'decision_source' => $decisionSource,
                'status' => 'processed',
                'reason' => $reason,
                'decided_at' => $payment->paid_at,
                'processed_at' => $payment->paid_at,
            ]);

            $payment->update([
                'status' => 'refunded',
            ]);
        }
    }
}
