<?php

namespace App\Console\Commands;

use App\Models\Refund;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessExpiredRefunds extends Command
{
    protected $signature = 'refunds:process-expired';

    protected $description = 'Process pending refunds after the 2-hour agency decision window';

    public function handle(): int
    {
        $refunds = Refund::with('payment.reservation')
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subHours(2))
            ->get();

        foreach ($refunds as $refund) {
            DB::transaction(function () use ($refund) {
                $refund->update([
                    'percentage' => 50,
                    'refunded_amount' => $refund->payment->amount * 0.50,
                    'decision_source' => 'automatic',
                    'status' => 'processed',
                    'reason' => 'Agency did not make a decision within 2 hours.',
                    'processed_at' => now(),
                ]);

                $refund->payment->update([
                    'status' => 'refunded',
                ]);
            });
        }

        $this->info("Processed {$refunds->count()} expired refunds.");

        return self::SUCCESS;
    }
}