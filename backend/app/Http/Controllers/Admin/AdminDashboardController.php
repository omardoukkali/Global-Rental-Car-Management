<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Car;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function show(): JsonResponse
    {
        $now = now();

        $paymentsThisMonth = Payment::with('refund')
            ->whereIn('status', ['paid', 'refunded'])
            ->whereNotNull('paid_at')
            ->whereYear('paid_at', $now->year)
            ->whereMonth('paid_at', $now->month)
            ->get();

        $avgRating = Agency::query()
            ->whereNotNull('avg_rating')
            ->avg('avg_rating');

        $pendingAgencies = Agency::with(['city', 'owner'])
            ->withCount(['cars', 'agencyPoints'])
            ->where('status', 'pending')
            ->latest()
            ->get()
            ->map(fn (Agency $agency) => AdminPresenter::agency($agency))
            ->values();

        $recentReservations = Reservation::with(['client', 'agency', 'payment'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Reservation $reservation) => AdminPresenter::reservation($reservation))
            ->values();

        $reports = Reservation::with(['client', 'car'])
            ->where('status', 'disputed')
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (Reservation $reservation) => AdminPresenter::report($reservation))
            ->values();

        return response()->json([
            'stats' => [
                'users' => User::count(),
                'active_agencies' => Agency::where('status', 'approved')->count(),
                'cars' => Car::count(),
                'reservations_this_month' => Reservation::query()
                    ->whereYear('created_at', $now->year)
                    ->whereMonth('created_at', $now->month)
                    ->count(),
                'platform_revenue' => round($paymentsThisMonth->sum(
                    fn (Payment $payment) => AdminPresenter::netCommission($payment)
                ), 2),
                'avg_rating' => $avgRating === null ? null : round((float) $avgRating, 1),
            ],
            'pending_agencies' => $pendingAgencies,
            'recent_reservations' => $recentReservations,
            'monthly_revenue' => AdminPresenter::monthlyRevenue(),
            'reports' => $reports,
        ]);
    }
}
