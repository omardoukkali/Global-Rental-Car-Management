<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterAgencyRequest;
use App\Http\Requests\Auth\RegisterClientRequest;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register a new client.
     */
    public function registerClient(
        RegisterClientRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'phone' => $validated['phone'] ?? null,

            'role' => 'client',
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Client registered successfully.',
            'user' => $user,
        ], 201);
    }


    /**
     * Register a new agency and its owner.
     */
    public function registerAgency(
        RegisterAgencyRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        $result = DB::transaction(function () use ($validated) {

            // Create agency owner account
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'phone' => $validated['phone'],

                'role' => 'agency',
                'status' => 'active',
            ]);

            // Create agency profile
            $agency = Agency::create([
                'owner_id' => $user->id,
                'city_id' => $validated['agency_city'],

                'name' => $validated['agency_name'],

                'slug' => Str::slug($validated['agency_name'])
                    . '-'
                    . substr($user->id, 0, 8),

                'address' => $validated['address'],
                'phone' => $validated['agency_phone'],

                'status' => 'pending',
            ]);

            return [
                'user' => $user,
                'agency' => $agency,
            ];
        });

        return response()->json([
            'message' => 'Agency registered successfully and is awaiting approval.',

            'user' => $result['user'],
            'agency' => $result['agency'],
        ], 201);
    }
}