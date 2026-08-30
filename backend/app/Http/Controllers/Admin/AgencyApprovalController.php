<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Agency;

class AgencyApprovalController extends Controller
{
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
