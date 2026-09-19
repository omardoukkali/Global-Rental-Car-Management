<?php

namespace App\Http\Controllers\Agency;

use App\Http\Requests\Agency\UpdateAgencyRequest;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Payment;

use Illuminate\Http\Request;

class AgencyController extends Controller
{
    public function show(Request $request)
    {
        $agency = $request->user()->agency;

        if (!$agency) {
            return response()->json([
                'message' => 'Agency not found.'
            ], 404);
        }

        return response()->json([
            'agency' => $agency
        ]);
    }

    public function update(UpdateAgencyRequest $request)
    {
        $agency = $request->user()->agency;

        if (!$agency) {
            return response()->json([
                'message' => 'Agency not found.',
            ], 404);
        }



        $agency->update($request->validated());

        return response()->json([
            'message' => 'Agency profile updated successfully.',
            'agency' => $agency->fresh(),
        ]);
    }

    public function stats(Request $request)
    {
        $validated = $request->validate([
            'year' => ['sometimes', 'integer', 'min:2000', 'max:2100'],
        ]);

        $agency = $request->user()->agency;
        $year = (int) ($validated['year'] ?? now()->year);

        // Net revenue = agency share minus the refunded part
        $payments = Payment::with('refund')
            ->whereIn('status', ['paid', 'refunded'])
            ->whereYear('paid_at', $year)
            ->whereHas('reservation', fn ($query) => $query->where('agency_id', $agency->id))
            ->get();

        $netRevenue = fn (Payment $payment) => (float) $payment->agency_amount
            * (1 - ((float) ($payment->refund?->percentage ?? 0)) / 100);

        $reservationsThisYear = $agency->reservations()
            ->whereYear('start_at', $year)
            ->get(['id', 'status', 'start_at']);

        $monthly = collect(range(1, 12))->map(fn (int $month) => [
            'month' => $month,
            'revenue' => round(
                $payments->filter(fn ($payment) => $payment->paid_at->month === $month)->sum($netRevenue),
                2
            ),
            'reservations' => $reservationsThisYear
                ->filter(fn ($reservation) => $reservation->start_at->month === $month)
                ->count(),
        ]);

        return response()->json([
            'year' => $year,
            'total_revenue' => round($payments->sum($netRevenue), 2),
            'monthly' => $monthly,
            'reservations_by_status' => $agency->reservations()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'occupancy_rate' => $this->currentMonthOccupancy($agency),
            'avg_rating' => $agency->avg_rating,
            'total_reviews' => $agency->total_reviews,
        ]);
    }

    /**
     * Percentage of car-days booked this month over the agency's rentable fleet.
     */
    private function currentMonthOccupancy(Agency $agency): float
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $fleetSize = $agency->cars()->where('status', '!=', 'unavailable')->count();

        if ($fleetSize === 0) {
            return 0.0;
        }

        $bookedDays = $agency->reservations()
            ->whereIn('status', ['confirmed', 'picked_up', 'completed'])
            ->where('start_at', '<', $monthEnd)
            ->where('end_at', '>', $monthStart)
            ->get(['start_at', 'end_at'])
            ->sum(fn ($reservation) => $reservation->start_at->max($monthStart)
                ->diffInHours($reservation->end_at->min($monthEnd)) / 24);

        return round($bookedDays / ($fleetSize * $monthStart->daysInMonth) * 100, 2);
    }
}
