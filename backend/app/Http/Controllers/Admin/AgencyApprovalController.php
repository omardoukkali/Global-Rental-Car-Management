<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Car;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgencyApprovalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'nullable', 'in:pending,approved,rejected'],
        ]);

        $agencies = Agency::query()
            ->with(['city', 'owner'])
            ->withCount(['cars', 'agencyPoints'])
            ->when(
                filled($validated['status'] ?? null),
                fn ($query) => $query->where('status', $validated['status'])
            )
            ->latest()
            ->get()
            ->map(fn (Agency $agency) => AdminPresenter::agency($agency))
            ->values();

        return response()->json([
            'agencies' => $agencies,
        ]);
    }

    public function show(Agency $agency): JsonResponse
    {
        $agency->loadCount(['cars', 'agencyPoints']);

        return response()->json([
            'agency' => AdminPresenter::agency($agency),
        ]);
    }

    public function update(Request $request, Agency $agency): JsonResponse
    {
        $validated = $request->validate([
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $agency->update([
            'commission_rate' => round((float) $validated['commission_rate'], 2),
        ]);

        $agency->refresh()->loadCount(['cars', 'agencyPoints']);

        return response()->json([
            'message' => 'Agency updated successfully.',
            'agency' => AdminPresenter::agency($agency),
        ]);
    }

    public function destroy(Agency $agency): JsonResponse
    {
        $hasActiveReservations = $agency->reservations()
            ->whereIn('status', ['pending', 'confirmed', 'picked_up'])
            ->exists();

        if ($hasActiveReservations) {
            return response()->json([
                'message' => 'Impossible de supprimer une agence avec des réservations en cours.',
            ], 422);
        }

        DB::transaction(function () use ($agency) {
            $agency->cars()->each(fn (Car $car) => $car->delete());
            $agency->agencyPoints()->update(['is_active' => false]);
            $owner = $agency->owner;

            if ($owner) {
                // status is not mass assignable (F-03)
                $owner->status = 'suspended';
                $owner->save();

                // Suspension takes effect right away (F-02)
                $owner->tokens()->delete();
            }
            $agency->delete();
        });

        return response()->json([
            'message' => 'Agency deleted successfully.',
        ]);
    }

    public function approve(Agency $agency): JsonResponse
    {
        if ($agency->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending agencies can be approved.',
            ], 422);
        }

        $agency->update([
            'status' => 'approved',
        ]);

        return response()->json([
            'message' => 'Agency approved successfully.',
            'agency' => $agency->fresh(),
        ]);
    }

    public function reject(Agency $agency): JsonResponse
    {
        if ($agency->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending agencies can be rejected.',
            ], 422);
        }

        $agency->update([
            'status' => 'rejected',
        ]);

        return response()->json([
            'message' => 'Agency rejected successfully.',
            'agency' => $agency->fresh(),
        ]);
    }
}
