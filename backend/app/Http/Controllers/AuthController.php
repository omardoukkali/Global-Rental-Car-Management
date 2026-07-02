<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    //Register Client
    public function registerClient(Request $request){
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8'
        ]);
        $user = User::create([
            'id' => Str::uuid(),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
            'role' => 'client',
            'status' => 'active'
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Client registered successfully',
            'token' => $token,
            'user' => $user
        ],201);
    }
    // ── Register Agency Owner
    public function registerAgency(Request $request)
    {
        $validated = $request->validate([
            'first_name'  => 'required|string|max:255',
            'last_name'   => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:8|confirmed',
            'phone'       => 'required|string|max:20',
            'agency_name' => 'required|string|max:255',
            'agency_city' => 'required|uuid|exists:cities,id',
            'address'     => 'required|string|max:255',
            'agency_phone'=> 'required|string|max:20',
        ]);

        // Create user
        $user = User::create([
            'id'         => Str::uuid(),
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => $validated['email'],
            'password'   => $validated['password'],
            'phone'      => $validated['phone'],
            'role'       => 'agency_owner',
            'status'     => 'active',
        ]);

        // Create agency (pending approval)
        Agency::create([
            'id'       => Str::uuid(),
            'owner_id' => $user->id,
            'city_id'  => $validated['agency_city'],
            'name'     => $validated['agency_name'],
            'slug'     => Str::slug($validated['agency_name']),
            'address'  => $validated['address'],
            'phone'    => $validated['agency_phone'],
            'status'   => 'pending',
        ]);

        return response()->json([
            'message' => 'Agency registered successfully, waiting for approval',
            'user'    => new UserResource($user), 
        ], 201);
 
    }

    public function login(Request $request){
        $validated = $request->validate([
            'email'       => 'required|email',
            'password'    => 'required|string'
        ]);
        if(!Auth::attempt($validated)){
            return response()->json([
                'message' => 'invalide credentials'
            ],401);
        }
        $user  = Auth::user()->load('agency');
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'loged in succesfully',
            'user'    => new UserResource($user), 
            'token' => $token
        ]);
    }

    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
    public function me(Request $request)
    {
        $user = $request->user()->load('agency');
        return response()->json([
            'user'    => new UserResource($user),
        ]);
    }
}