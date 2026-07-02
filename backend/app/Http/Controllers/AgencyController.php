<?php

namespace App\Http\Controllers;

use App\Http\Resources\AgencyResource;
use App\Models\Agency;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgencyController extends Controller
{
    // ── GET /api/agencies — public
    public function index(Request $request)
    {
        $agencies = Agency::where('status', 'approved')
            ->with(['city', 'owner'])
            ->when($request->city_id, fn($q) => $q->where('city_id', $request->city_id))
            ->when($request->search,  fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
            ->orderBy('avg_rating', 'desc')
            ->paginate(12);

        return AgencyResource::collection($agencies);
    }
    // ── GET /api/agencies/{agency} — public
    public function show(Agency $agency)
    {
        $agency->load(['city', 'owner', 'cars.images', 'cars.city']);

        return new AgencyResource($agency);
    }

    // ── PUT /api/agency/profile — agency owner
    public function update(Request $request)
    {
        $agency = $request->user()->agency;

        if (!$agency) {
            return response()->json([
                'message' => 'Agency not found'
            ], 404);
        }

        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'address'     => 'sometimes|string|max:255',
            'phone'       => 'sometimes|string|max:20',
            'email'       => 'sometimes|email|max:255',
        ]);

        // Update slug if name changed
        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $agency->update($validated);

        return new AgencyResource($agency->load(['city', 'owner']));
    }
    // ── GET /api/admin/agencies — admin
    public function adminIndex(Request $request)
    {
        $agencies = Agency::with(['city', 'owner'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return AgencyResource::collection($agencies);
    }
    // ── PUT /api/admin/agencies/{agency}/approve — admin
    public function approve(Agency $agency)
    {
        $agency->update(['status' => 'approved']);

        return response()->json([
            'message' => 'Agency approved successfully',
            'agency'  => new AgencyResource($agency),
        ]);
    }
    // ── PUT /api/admin/agencies/{agency}/reject — admin
    public function reject(Request $request, Agency $agency)
    {
        $agency->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Agency rejected',
            'agency'  => new AgencyResource($agency),
        ]);
    }

    // ── DELETE /api/admin/agencies/{agency} — admin
    public function destroy(Agency $agency)
    {
        $agency->delete();

        return response()->json([
            'message' => 'Agency deleted successfully'
        ]);
    }
}
