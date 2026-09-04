<?php

use App\Http\Controllers\Agency\AgencyPointController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Car\CarImageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\City\CityController;
use App\Http\Controllers\Agency\AgencyController;
use App\Http\Controllers\Admin\AgencyApprovalController;
use App\Http\Controllers\Car\CarController;

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

    Route::post('/agency/points', [
        AgencyPointController::class,
        'store',
    ]);
    Route::get('/agency/points', [
        AgencyPointController::class,
        'index',
    ]);
    Route::get('/agency/points/{agencyPoint}', [
        AgencyPointController::class,
        'show',
    ]);
    Route::put('/agency/points/{agencyPoint}', [
        AgencyPointController::class,
        'update',
    ]);
    Route::patch('/agency/points/{agencyPoint}/toggle-status', [
        AgencyPointController::class,
        'toggleStatus',
    ]);


    Route::post('/agency/cars', [CarController::class, 'store']);
    Route::get('/agency/cars', [CarController::class, 'index']);
    Route::get('/agency/cars/{car}', [CarController::class, 'show']);
    Route::put('/agency/cars/{car}', [CarController::class, 'update']);
    Route::patch('/agency/cars/{car}/disable', [CarController::class, 'disable']);

    Route::post('/agency/cars/{car}/images', [CarImageController::class, 'store']);
    Route::get('/agency/cars/{car}/images', [CarImageController::class, 'index']);
    Route::patch('/agency/cars/{car}/images/{image}/primary',[CarImageController::class, 'setPrimary']);
    Route::delete('/agency/cars/{car}/images/{image}',[CarImageController::class, 'destroy']);

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