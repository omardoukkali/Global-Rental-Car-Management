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

    public function update(Request $request)
    {
        $agency = $request->user()->agency;

        if (!$agency) {
            return response()->json([
                'message' => 'Agence introuvable.'
            ], 404);
        }

        $validated = $request->validate([
            'name'    => 'sometimes|string|max:255',
            'email'   => 'sometimes|email|max:255',
            'phone'   => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:255',
        ]);

        $agency->update($validated);

        return response()->json([
            'message' => 'Profil mis à jour avec succès.',
            'agency'  => $agency->fresh()
        ]);
    }
}