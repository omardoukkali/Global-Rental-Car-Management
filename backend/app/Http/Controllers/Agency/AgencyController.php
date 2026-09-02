<?php

namespace App\Http\Controllers\Agency;

use App\Http\Requests\Agency\UpdateAgencyRequest;
use App\Http\Controllers\Controller;

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
}
