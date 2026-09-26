<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PaymentSeeder extends Seeder
{
    /**
     * Every reservation except "pending" was paid.
     * Refunds (and the "refunded" status) are added later in RefundSeeder.
     */
    public function run(): void
    {
        $reservations = Reservation::with('agency')
            ->where('status', '!=', 'pending')
            ->get();

        foreach ($reservations as $reservation) {
            $amount = $reservation->total_amount;

            // Same calculation as PaymentController
            $commissionRate = $reservation->agency->commission_rate;
            $platformCommission = round($amount * $commissionRate / 100, 2);
            $agencyAmount = round($amount - $platformCommission, 2);

            // Paid 5 days before pickup, but never in the future
            $paidAt = $reservation->start_at->copy()->subDays(5);

            if ($paidAt->isFuture()) {
                $paidAt = now();
            }

            Payment::create([
                'reservation_id' => $reservation->id,
                'amount' => $amount,
                'commission_rate' => $commissionRate,
                'platform_commission' => $platformCommission,
                'agency_amount' => $agencyAmount,
                'transaction_id' => 'TXN-' . strtoupper(Str::random(12)),
                'status' => 'paid',
                'paid_at' => $paidAt,
            ]);
        }
    }
}
