<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\City\CityController;
use App\Http\Controllers\Agency\AgencyController;
use App\Http\Controllers\Admin\AgencyApprovalController;

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
])->group(function () {

    Route::get('/agency/profile', [AgencyController::class, 'show']);
    Route::put('/agency/profile', [AgencyController::class, 'update']);

});

// Approved agencies routes

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

});

// Admin routes

Route::middleware([
    'auth:sanctum',
    'role:admin'
])->group(function () {

    Route::patch(
        '/admin/agencies/{agency}/approve',
        [AgencyApprovalController::class, 'approve']
    );

    Route::patch(
        '/admin/agencies/{agency}/reject',
        [AgencyApprovalController::class, 'reject']
    );
});


Route::get('/cities', [CityController::class, 'index']);