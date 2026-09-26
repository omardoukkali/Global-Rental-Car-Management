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
        $year = $validated['year'] ?? now()->year;

        // One entry per month (1 to 12)
        $monthly = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthly[$month] = [
                'month' => $month,
                'revenue' => 0,
                'reservations' => 0,
            ];
        }

        // 1. Revenue: the agency's share of each payment, minus what was refunded
        $payments = Payment::with('refund')
            ->whereIn('status', ['paid', 'refunded'])
            ->whereYear('paid_at', $year)
            ->whereHas('reservation', function ($reservationQuery) use ($agency) {
                $reservationQuery->where('agency_id', $agency->id);
            })
            ->get();

        $totalRevenue = 0;

        foreach ($payments as $payment) {
            $refundPercentage = 0;

            if ($payment->refund) {
                $refundPercentage = $payment->refund->percentage;
            }

            $revenue = $payment->agency_amount * (100 - $refundPercentage) / 100;

            $monthly[$payment->paid_at->month]['revenue'] += $revenue;
            $totalRevenue += $revenue;
        }

        // 2. Reservations: count per month and per status
        $reservationsByStatus = [];

        foreach ($agency->reservations as $reservation) {
            if ($reservation->start_at->year == $year) {
                $monthly[$reservation->start_at->month]['reservations']++;
            }

            if (!isset($reservationsByStatus[$reservation->status])) {
                $reservationsByStatus[$reservation->status] = 0;
            }

            $reservationsByStatus[$reservation->status]++;
        }

        foreach ($monthly as $month => $data) {
            $monthly[$month]['revenue'] = round($data['revenue'], 2);
        }

        return response()->json([
            'year' => (int) $year,
            'total_revenue' => round($totalRevenue, 2),
            'monthly' => array_values($monthly),
            'reservations_by_status' => $reservationsByStatus,
            'occupancy_rate' => $this->currentMonthOccupancy($agency),
            'avg_rating' => $agency->avg_rating,
            'total_reviews' => $agency->total_reviews,
        ]);
    }

    /**
     * Occupancy this month = booked car-days / (number of cars x days in month), in %.
     */
    private function currentMonthOccupancy(Agency $agency): float
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        // Disabled cars cannot be rented, so they don't count
        $numberOfCars = $agency->cars()->where('status', '!=', 'unavailable')->count();

        if ($numberOfCars === 0) {
            return 0;
        }

        $reservations = $agency->reservations()
            ->whereIn('status', ['confirmed', 'picked_up', 'completed'])
            ->where('start_at', '<', $monthEnd)
            ->where('end_at', '>', $monthStart)
            ->get();

        $bookedDays = 0;

        foreach ($reservations as $reservation) {
            // Only count the part of the reservation inside this month
            $start = $reservation->start_at;
            $end = $reservation->end_at;

            if ($start < $monthStart) {
                $start = $monthStart;
            }

            if ($end > $monthEnd) {
                $end = $monthEnd;
            }

            $bookedDays += $start->diffInHours($end) / 24;
        }

        $availableDays = $numberOfCars * $monthStart->daysInMonth;

        return round($bookedDays / $availableDays * 100, 2);
    }
}
