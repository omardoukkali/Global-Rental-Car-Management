<?php

namespace App\Http\Controllers\Admin;

use App\Models\Agency;
use App\Models\Car;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;

class AdminPresenter
{
    public const MONTH_LABELS = [
        1 => 'Jan',
        2 => 'Fev',
        3 => 'Mar',
        4 => 'Avr',
        5 => 'Mai',
        6 => 'Jui',
        7 => 'Jul',
        8 => 'Aou',
        9 => 'Sep',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Dec',
    ];

    public static function agency(Agency $agency): array
    {
        $agency->loadMissing(['city', 'owner']);

        $id = str_replace('-', '', (string) $agency->id);

        return [
            'id' => $agency->id,
            'name' => $agency->name,
            'slug' => $agency->slug,
            'city' => $agency->city?->name,
            'manager' => self::fullName($agency->owner),
            'owner_email' => $agency->owner?->email,
            'owner_phone' => $agency->owner?->phone,
            'reference' => 'AG-' . strtoupper(substr($id, -4)),
            'email' => $agency->email,
            'phone' => $agency->phone,
            'address' => $agency->address,
            'logo_url' => $agency->logo_url,
            'commission_rate' => round((float) $agency->commission_rate, 2),
            'cars_count' => (int) ($agency->cars_count ?? $agency->cars()->count()),
            'points_count' => (int) ($agency->agency_points_count ?? $agency->agencyPoints()->count()),
            'avg_rating' => $agency->avg_rating !== null ? round((float) $agency->avg_rating, 1) : null,
            'total_reviews' => (int) ($agency->total_reviews ?? 0),
            'status' => $agency->status,
            'checks' => [
                ['label' => 'Email', 'valid' => filled($agency->email)],
                ['label' => 'Téléphone', 'valid' => filled($agency->phone)],
                ['label' => 'Adresse', 'valid' => filled($agency->address)],
                ['label' => 'Ville', 'valid' => $agency->city_id !== null],
            ],
        ];
    }

    public static function reservation(Reservation $reservation): array
    {
        $reservation->loadMissing(['client', 'agency', 'payment', 'car']);

        $commission = (float) ($reservation->payment?->platform_commission ?? 0);
        $commissionRate = (float) ($reservation->payment?->commission_rate
            ?? $reservation->agency?->commission_rate
            ?? 0);

        if (in_array($reservation->status, ['cancelled', 'rejected'], true)) {
            $commission = 0.0;
        }

        $car = trim(($reservation->car?->brand ?? '') . ' ' . ($reservation->car?->model ?? ''));

        return [
            'id' => $reservation->id,
            'reference' => $reservation->reference,
            'client' => self::shortName($reservation->client),
            'agency' => $reservation->agency?->name,
            'car' => $car !== '' ? $car : null,
            'amount' => round((float) $reservation->total_amount, 2),
            'commission' => round($commission, 2),
            'commission_rate' => round($commissionRate, 2),
            'status' => $reservation->status,
            'start_at' => optional($reservation->start_at)?->toIso8601String(),
            'end_at' => optional($reservation->end_at)?->toIso8601String(),
        ];
    }

    public static function user(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => self::fullName($user),
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'status' => $user->status,
            'created_at' => optional($user->created_at)?->toIso8601String(),
        ];
    }

    public static function car(Car $car): array
    {
        $car->loadMissing(['agency', 'city']);

        return [
            'id' => $car->id,
            'brand' => $car->brand,
            'model' => $car->model,
            'year' => $car->year,
            'plate_number' => $car->plate_number,
            'type' => $car->type,
            'daily_price' => round((float) $car->daily_price, 2),
            'status' => $car->status,
            'agency' => $car->agency?->name,
            'city' => $car->city?->name,
        ];
    }

    public static function review(Review $review): array
    {
        $review->loadMissing(['user', 'reservation.car', 'reservation.agency']);

        $car = trim(($review->reservation?->car?->brand ?? '') . ' ' . ($review->reservation?->car?->model ?? ''));

        return [
            'id' => $review->id,
            'car_rating' => $review->car_rating === null ? null : round((float) $review->car_rating, 1),
            'agency_rating' => $review->agency_rating === null ? null : round((float) $review->agency_rating, 1),
            'comment' => $review->comment,
            'client' => self::fullName($review->user),
            'car' => $car !== '' ? $car : null,
            'agency' => $review->reservation?->agency?->name,
            'created_at' => optional($review->created_at)?->toIso8601String(),
        ];
    }

    public static function payment(Payment $payment): array
    {
        $payment->loadMissing(['refund', 'reservation.agency']);

        return [
            'id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'amount' => round((float) $payment->amount, 2),
            'platform_commission' => round((float) $payment->platform_commission, 2),
            'net_commission' => self::netCommission($payment),
            'status' => $payment->status,
            'paid_at' => optional($payment->paid_at)?->toIso8601String(),
            'reference' => $payment->reservation?->reference,
            'agency' => $payment->reservation?->agency?->name,
        ];
    }

    public static function monthlyRevenue(): array
    {
        $start = now()->startOfMonth()->subMonths(5);

        $payments = Payment::with('refund')
            ->whereIn('status', ['paid', 'refunded'])
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $start)
            ->get();

        $buckets = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->startOfMonth()->subMonths($i);
            $key = $date->format('Y-m');
            $buckets[$key] = [
                'year' => (int) $date->year,
                'month' => (int) $date->month,
                'label' => self::MONTH_LABELS[(int) $date->month],
                'revenue' => 0.0,
            ];
        }

        foreach ($payments as $payment) {
            $key = $payment->paid_at->format('Y-m');

            if (!isset($buckets[$key])) {
                continue;
            }

            $buckets[$key]['revenue'] += self::netCommission($payment);
        }

        return array_values(array_map(function (array $row) {
            $row['revenue'] = round($row['revenue'], 2);

            return $row;
        }, $buckets));
    }

    public static function report(Reservation $reservation): array
    {
        $reservation->loadMissing(['client', 'car']);

        $car = trim(($reservation->car?->brand ?? '') . ' ' . ($reservation->car?->model ?? ''));

        return [
            'id' => $reservation->id,
            'reference' => $reservation->reference,
            'title' => 'Litige sur la réservation',
            'car' => $car !== '' ? $car : null,
            'client' => self::fullName($reservation->client),
            'status' => $reservation->status,
            'created_at' => optional($reservation->updated_at ?? $reservation->created_at)?->toIso8601String(),
        ];
    }

    public static function netCommission(Payment $payment): float
    {
        $percentage = (float) ($payment->refund?->percentage ?? 0);

        return round((float) $payment->platform_commission * (100 - $percentage) / 100, 2);
    }

    public static function fullName(?User $user): string
    {
        if (!$user) {
            return '—';
        }

        return trim($user->first_name . ' ' . $user->last_name) ?: '—';
    }

    public static function shortName(?User $user): string
    {
        if (!$user) {
            return '—';
        }

        $last = $user->last_name
            ? mb_strtoupper(mb_substr($user->last_name, 0, 1)) . '.'
            : '';

        return trim($user->first_name . ' ' . $last) ?: '—';
    }
}
