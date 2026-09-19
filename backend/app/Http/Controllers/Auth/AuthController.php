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
use Illuminate\Http\Request;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    /**
     * Register a new client.
     */
    public function registerClient(RegisterClientRequest $request): JsonResponse // Specify that this method returns a JSON response.
    {
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
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = User::where('email', $validated['email'])->first();
        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 401);
        }
        if ($user->status === 'suspended') {
            return response()->json([
                'message' => 'Your account has been suspended.',
            ], 403);
        }
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user,
        ]);
    }
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        // Same answer whether the email exists or not (no account enumeration)
        return response()->json([
            'message' => 'If an account exists for this email, a reset link has been sent.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                // Log out every device that used the old password
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'This password reset link is invalid or has expired.',
            ], 422);
        }

        return response()->json([
            'message' => 'Password reset successfully.',
        ]);
    }
}