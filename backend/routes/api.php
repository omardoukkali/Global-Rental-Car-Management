<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CityController;
use App\Http\Controllers\AgencyController;

// Public routes

Route::prefix('register')->group(function () {

    Route::post('/client', [
        AuthController::class,
        'registerClient'
    ]);

    Route::post('/agency', [
        AuthController::class,
        'registerAgency'
    ]);
});

Route::post('/login', [
    AuthController::class,
    'login'
]);


// Authenticated routes

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', function (Request $request) {
        return response()->json([
            'user' => $request->user(),
        ]);
    });

    Route::post('/logout', [
        AuthController::class,
        'logout'
    ]);
});


// Client routes

Route::middleware([
    'auth:sanctum',
    'role:client'
])->group(function () {

    Route::get('/client/test', function () {
        return response()->json([
            'message' => 'Client access granted.',
        ]);
    });

});


// Agency routes

Route::middleware([
    'auth:sanctum',
    'role:agency',
    'agency.approved',
])->group(function () {

    Route::get('/agency/test', function () {
        return response()->json([
            'message' => 'Approved agency access granted.',
        ]);
    });

    Route::get('/agency/profile', [AgencyController::class, 'show']);

});


// Admin routes

Route::middleware([
    'auth:sanctum',
    'role:admin'
])->group(function () {

    Route::get('/admin/test', function () {
        return response()->json([
            'message' => 'Admin access granted.',
        ]);
    });

});


Route::get('/cities', [CityController::class, 'index']);