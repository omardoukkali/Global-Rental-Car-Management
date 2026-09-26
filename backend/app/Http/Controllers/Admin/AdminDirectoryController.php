<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDirectoryController extends Controller
{
    public function users(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'role' => ['sometimes', 'nullable', 'in:admin,client,agency'],
            'status' => ['sometimes', 'nullable', 'in:active,pending,suspended'],
        ]);

        $query = User::query()->latest();

        if (filled($validated['role'] ?? null)) {
            $query->where('role', $validated['role']);
        }

        if (filled($validated['status'] ?? null)) {
            $query->where('status', $validated['status']);
        }

        if (filled($validated['q'] ?? null)) {
            $term = '%' . $validated['q'] . '%';
            $query->where(function ($inner) use ($term) {
                $inner->where('first_name', 'ilike', $term)
                    ->orWhere('last_name', 'ilike', $term)
                    ->orWhere('email', 'ilike', $term)
                    ->orWhere('phone', 'ilike', $term);
            });
        }

        return $this->page($query->paginate(15), 'users', fn (User $user) => AdminPresenter::user($user));
    }

    public function cars(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'nullable', 'in:available,unavailable,maintenance'],
        ]);

        $query = Car::query()->with(['agency', 'city'])->latest();

        if (filled($validated['status'] ?? null)) {
            $query->where('status', $validated['status']);
        }

        if (filled($validated['q'] ?? null)) {
            $term = '%' . $validated['q'] . '%';
            $query->where(function ($inner) use ($term) {
                $inner->where('brand', 'ilike', $term)
                    ->orWhere('model', 'ilike', $term)
                    ->orWhere('plate_number', 'ilike', $term);
            });
        }

        return $this->page($query->paginate(15), 'cars', fn (Car $car) => AdminPresenter::car($car));
    }

    public function reservations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'nullable', 'in:pending,confirmed,rejected,picked_up,completed,cancelled,disputed'],
        ]);

        $query = Reservation::query()
            ->with(['client', 'agency', 'payment', 'car'])
            ->latest();

        if (filled($validated['status'] ?? null)) {
            $query->where('status', $validated['status']);
        }

        if (filled($validated['q'] ?? null)) {
            $term = '%' . $validated['q'] . '%';
            $query->where(function ($inner) use ($term) {
                $inner->where('reference', 'ilike', $term)
                    ->orWhereHas('client', function ($client) use ($term) {
                        $client->where('first_name', 'ilike', $term)
                            ->orWhere('last_name', 'ilike', $term)
                            ->orWhere('email', 'ilike', $term);
                    })
                    ->orWhereHas('agency', fn ($agency) => $agency->where('name', 'ilike', $term));
            });
        }

        return $this->page(
            $query->paginate(15),
            'reservations',
            fn (Reservation $reservation) => AdminPresenter::reservation($reservation)
        );
    }

    public function revenue(Request $request): JsonResponse
    {
        $now = now();

        $paymentsThisMonth = Payment::with('refund')
            ->whereIn('status', ['paid', 'refunded'])
            ->whereNotNull('paid_at')
            ->whereYear('paid_at', $now->year)
            ->whereMonth('paid_at', $now->month)
            ->get();

        $paymentsThisYear = Payment::with('refund')
            ->whereIn('status', ['paid', 'refunded'])
            ->whereNotNull('paid_at')
            ->whereYear('paid_at', $now->year)
            ->get();

        $page = Payment::query()
            ->with(['refund', 'reservation.agency'])
            ->latest('paid_at')
            ->paginate(15);

        return response()->json([
            'stats' => [
                'month' => round($paymentsThisMonth->sum(
                    fn (Payment $payment) => AdminPresenter::netCommission($payment)
                ), 2),
                'year' => round($paymentsThisYear->sum(
                    fn (Payment $payment) => AdminPresenter::netCommission($payment)
                ), 2),
                'payments_this_month' => $paymentsThisMonth->count(),
            ],
            'monthly_revenue' => AdminPresenter::monthlyRevenue(),
            'payments' => $page->getCollection()
                ->map(fn (Payment $payment) => AdminPresenter::payment($payment))
                ->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
                'per_page' => $page->perPage(),
            ],
        ]);
    }

    public function reviews(): JsonResponse
    {
        $reviews = Review::query()
            ->with(['user', 'reservation.car', 'reservation.agency'])
            ->latest()
            ->paginate(15);

        $reports = Reservation::with(['client', 'car'])
            ->where('status', 'disputed')
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->map(fn (Reservation $reservation) => AdminPresenter::report($reservation))
            ->values();

        return response()->json([
            'reviews' => $reviews->getCollection()
                ->map(fn (Review $review) => AdminPresenter::review($review))
                ->values(),
            'reports' => $reports,
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
                'per_page' => $reviews->perPage(),
            ],
        ]);
    }

    private function page(LengthAwarePaginator $paginator, string $key, callable $mapper): JsonResponse
    {
        return response()->json([
            $key => $paginator->getCollection()->map($mapper)->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }
}
