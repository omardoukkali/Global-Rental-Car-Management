<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agency\StoreAgencyPointRequest;
use App\Http\Requests\Agency\UpdateAgencyPointRequest;
use App\Models\AgencyPoint;
use Illuminate\Http\JsonResponse;

class AgencyPointController extends Controller
{
    public function store(StoreAgencyPointRequest $request): JsonResponse
    {
        $agency = $request->user()->agency;

        if (!$agency) {
            return response()->json([
                'message' => 'Agency not found.',
            ], 404);
        }

        $point = $agency->agencyPoints()->create(
            $request->validated()
        );

        return response()->json([
            'message' => 'Agency point created successfully.',
            'point' => $point,
        ], 201);
    }
    public function index(): JsonResponse
    {
        $agency = request()->user()->agency;

        if (!$agency) {
            return response()->json([
                'message' => 'Agency not found.',
            ], 404);
        }

        $points = $agency->agencyPoints()->get();

        return response()->json([
            'points' => $points,
        ]);
    }
    public function show(AgencyPoint $agencyPoint): JsonResponse
    {
        $agency = request()->user()->agency;

        if (!$agency) {
            return response()->json([
                'message' => 'Agency not found.',
            ], 404);
        }

        if ($agencyPoint->agency_id !== $agency->id) {
            return response()->json([
                'message' => 'This point does not belong to your agency.',
            ], 403);
        }

        return response()->json([
            'point' => $agencyPoint,
        ]);
    }
    public function update(
        UpdateAgencyPointRequest $request,
        AgencyPoint $agencyPoint
    ): JsonResponse {
        $agency = $request->user()->agency;

        if (!$agency) {
            return response()->json([
                'message' => 'Agency not found.',
            ], 404);
        }

        if ($agencyPoint->agency_id !== $agency->id) {
            return response()->json([
                'message' => 'This point does not belong to your agency.',
            ], 403);
        }

        $agencyPoint->update($request->validated());

        return response()->json([
            'message' => 'Agency point updated successfully.',
            'point' => $agencyPoint->fresh(),
        ]);
    }
    public function toggleStatus(AgencyPoint $agencyPoint): JsonResponse
    {
        $agency = request()->user()->agency;

        if (!$agency) {
            return response()->json([
                'message' => 'Agency not found.',
            ], 404);
        }

        if ($agencyPoint->agency_id !== $agency->id) {
            return response()->json([
                'message' => 'This point does not belong to your agency.',
            ], 403);
        }

        $agencyPoint->update([
            'is_active' => !$agencyPoint->is_active,
        ]);

        return response()->json([
            'message' => $agencyPoint->is_active
                ? 'Agency point activated successfully.'
                : 'Agency point deactivated successfully.',
            'point' => $agencyPoint->fresh(),
        ]);
    }
}