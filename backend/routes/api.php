<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\CarImageController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// ── Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register/client', [AuthController::class, 'registerClient']);
    Route::post('/register/agency', [AuthController::class, 'registerAgency']);
    Route::post('/login',           [AuthController::class, 'login']);
});

Route::get('/cities',                       [CityController::class, 'index']);
Route::get('/agencies',                     [AgencyController::class, 'index']);
Route::get('/agencies/{agency}',            [AgencyController::class, 'show']);
Route::get('/cars',                         [CarController::class, 'index']);
Route::get('/cars/{car}',                   [CarController::class, 'show']);
Route::get('/cars/{car}/reviews',           [ReviewController::class, 'carReviews']);
Route::get('/agencies/{agency}/reviews',    [ReviewController::class, 'agencyReviews']);

// ── Authenticated routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);
    });

    // ── Client only
    Route::middleware('role:client')->prefix('client')->group(function () {
        // Reservations
        Route::post('/reservations',                              [ReservationController::class, 'store']);
        Route::get('/reservations',                               [ReservationController::class, 'clientIndex']);
        Route::get('/reservations/{reservation}',                 [ReservationController::class, 'clientShow']);
        Route::delete('/reservations/{reservation}',              [ReservationController::class, 'cancel']);
        // Payments
        Route::post('/reservations/{reservation}/pay',            [PaymentController::class, 'pay']);
        Route::post('/reservations/{reservation}/refund',         [PaymentController::class, 'refund']);
        Route::get('/reservations/{reservation}/payment',         [PaymentController::class, 'clientShow']);
        // Reviews
        Route::post('/reservations/{reservation}/review',         [ReviewController::class, 'store']);
        Route::get('/reviews',                                    [ReviewController::class, 'clientIndex']);
    });

    // ── Agency only
    Route::middleware('role:agency_owner')->prefix('agency')->group(function () {
        // Profile
        Route::put('/profile', [AgencyController::class, 'update']);
        // Cars
        Route::get('/cars',              [CarController::class, 'agencyIndex']);
        Route::post('/cars',             [CarController::class, 'store']);
        Route::put('/cars/{car}',        [CarController::class, 'update']);
        Route::delete('/cars/{car}',     [CarController::class, 'destroy']);
        // Car Images
        Route::get('/cars/{car}/images',                    [CarImageController::class, 'index']);
        Route::post('/cars/{car}/images',                   [CarImageController::class, 'store']);
        Route::put('/cars/{car}/images/{image}/primary',    [CarImageController::class, 'setPrimary']);
        Route::delete('/cars/{car}/images/{image}',         [CarImageController::class, 'destroy']);
        // Reservations
        Route::get('/reservations',                         [ReservationController::class, 'agencyIndex']);
        Route::get('/reservations/{reservation}',           [ReservationController::class, 'agencyShow']);
        // Payments
        Route::get('/payments',                             [PaymentController::class, 'agencyIndex']);
    });

    // ── Admin only
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Dashboard
        Route::get('/dashboard',                            [AdminController::class, 'dashboard']);
        // Users
        Route::get('/users',                                [AdminController::class, 'users']);
        Route::put('/users/{user}/suspend',                 [AdminController::class, 'suspendUser']);
        Route::put('/users/{user}/activate',                [AdminController::class, 'activateUser']);
        Route::delete('/users/{user}',                      [AdminController::class, 'deleteUser']);
        // Cities
        Route::post('/cities',                              [CityController::class, 'store']);
        // Agencies
        Route::get('/agencies',                             [AgencyController::class, 'adminIndex']);
        Route::put('/agencies/{agency}/approve',            [AgencyController::class, 'approve']);
        Route::put('/agencies/{agency}/reject',             [AgencyController::class, 'reject']);
        Route::delete('/agencies/{agency}',                 [AgencyController::class, 'destroy']);
        // Cars
        Route::get('/cars',                                 [CarController::class, 'adminIndex']);
        Route::delete('/cars/{car}',                        [CarController::class, 'destroy']);
        // Reservations
        Route::get('/reservations',                         [ReservationController::class, 'adminIndex']);
        // Payments
        Route::get('/payments',                             [PaymentController::class, 'adminIndex']);
        Route::put('/payments/{payment}/release',           [PaymentController::class, 'release']);
        // Reviews
        Route::get('/reviews',                              [ReviewController::class, 'adminIndex']);
        Route::delete('/reviews/{review}',                  [ReviewController::class, 'destroy']);
        Route::put('/reviews/{id}/restore',                 [ReviewController::class, 'restore']);
    });

    // ── Admin OR Agency
    Route::middleware('role:admin,agency_owner')->group(function () {
        // shared routes...
    });
});