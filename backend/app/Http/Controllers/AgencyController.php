<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AgencyController extends Controller
{
    public function show(Request $request)
    {
        $agency = $request->user()->agency;

        if (!$agency) {
            return response()->json([
                'message' => 'Agence introuvable.'
            ], 404);
        }

        return response()->json([
            'agency' => $agency
        ]);
    }
}