<?php

use App\Http\Controllers\Admin\AgencyApprovalController;
use App\Http\Controllers\Agency\AgencyController;
use App\Http\Controllers\Agency\AgencyPointController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Car\CarAvailabilityController;
use App\Http\Controllers\Car\CarController;
use App\Http\Controllers\Car\CarImageController;
use App\Http\Controllers\City\CityController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Reservation\ReservationController;
use App\Http\Controllers\Review\ReviewController;
use App\Http\Controllers\Refund\RefundController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


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

    Route::get('/reviews', [
        ReviewController::class,
        'index',
    ]);

    Route::post('/reviews', [
        ReviewController::class,
        'store',
    ]);

    Route::get('/reviews/{review}', [
        ReviewController::class,
        'show',
    ]);

    Route::put('/reviews/{review}', [
        ReviewController::class,
        'update',
    ]);

    Route::delete('/reviews/{review}', [
        ReviewController::class,
        'destroy',
    ]);

    Route::patch('/reservations/{reservation}/pickup/confirm', [
        ReservationController::class,
        'confirmPickup',
    ]);

    Route::patch('/reservations/{reservation}/return/confirm', [
        ReservationController::class,
        'confirmReturn',
    ]);

});


// Agency routes

Route::middleware([
    'auth:sanctum',
    'role:agency',
])->group(function () {

    Route::get('/agency/profile', [
        AgencyController::class,
        'show'
    ]);

    Route::put('/agency/profile', [
        AgencyController::class,
        'update'
    ]);

    Route::post('/refunds', [
        RefundController::class,
        'store',
    ]);

    Route::patch('/refunds/{refund}/decision', [
        RefundController::class,
        'decide',
    ]);

    Route::patch('/reservations/{reservation}/reject', [
        ReservationController::class,
        'reject',
    ]);

});


// Approved agencies routes

Route::middleware([
    'auth:sanctum',
    'role:agency',
    'agency.approved',
])->group(function () {

    // Agency Points

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


    // Cars

    Route::post('/agency/cars', [
        CarController::class,
        'store'
    ]);

    Route::get('/agency/cars', [
        CarController::class,
        'index'
    ]);

    Route::get('/agency/cars/{car}', [
        CarController::class,
        'show'
    ]);

    Route::put('/agency/cars/{car}', [
        CarController::class,
        'update'
    ]);

    Route::patch('/agency/cars/{car}/disable', [
        CarController::class,
        'disable'
    ]);


    // Car Images

    Route::post('/agency/cars/{car}/images', [
        CarImageController::class,
        'store'
    ]);

    Route::get('/agency/cars/{car}/images', [
        CarImageController::class,
        'index'
    ]);

    Route::patch(
        '/agency/cars/{car}/images/{image}/primary',
        [
            CarImageController::class,
            'setPrimary'
        ]
    );

    Route::delete(
        '/agency/cars/{car}/images/{image}',
        [
            CarImageController::class,
            'destroy'
        ]
    );


    // Reservation pickup / return

    Route::patch('/reservations/{reservation}/pickup/confirm', [
        ReservationController::class,
        'confirmAgencyPickup',
    ]);

    Route::patch('/reservations/{reservation}/return/confirm', [
        ReservationController::class,
        'confirmAgencyReturn',
    ]);

});


// Admin routes

Route::middleware([
    'auth:sanctum',
    'role:admin'
])->group(function () {

    Route::patch(
        '/admin/agencies/{agency}/approve',
        [
            AgencyApprovalController::class,
            'approve'
        ]
    );

    Route::patch(
        '/admin/agencies/{agency}/reject',
        [
            AgencyApprovalController::class,
            'reject'
        ]
    );

});


// Public city routes

Route::get('/cities', [
    CityController::class,
    'index'
]);


// Public car availability

Route::get('/cars/{car}/availability', [
    CarAvailabilityController::class,
    'check',
]);


// Client reservation + payment routes

Route::middleware([
    'auth:sanctum',
    'role:client'
])->group(function () {

    // Reservations

    Route::get('/reservations', [
        ReservationController::class,
        'index',
    ]);

    Route::post('/reservations', [
        ReservationController::class,
        'store',
    ]);

    Route::get('/reservations/{reservation}', [
        ReservationController::class,
        'show',
    ]);

    Route::put('/reservations/{reservation}', [
        ReservationController::class,
        'update',
    ]);

    Route::patch('/reservations/{reservation}/cancel', [
        ReservationController::class,
        'cancel',
    ]);

    Route::patch('/reservations/{reservation}/dispute', [
        ReservationController::class,
        'dispute',
    ]);


    // Payments

    Route::post('/payments', [
        PaymentController::class,
        'store',
    ]);

});