<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDirectoryController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AgencyApprovalController;
use App\Http\Controllers\Agency\AgencyController;
use App\Http\Controllers\Agency\AgencyPointController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Car\CarAvailabilityController;
use App\Http\Controllers\Car\CarController;
use App\Http\Controllers\Car\CarImageController;
use App\Http\Controllers\City\CityController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Refund\RefundController;
use App\Http\Controllers\Reservation\ReservationController;
use App\Http\Controllers\Review\ReviewController;
use App\Http\Controllers\SmartDrive\SmartDriveController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes (all prefixed with /api)
|--------------------------------------------------------------------------
|
| 1. Public: authentication
| 2. Public: catalogue (cities, cars, reviews, SmartDrive)
| 3. Any logged-in user
| 4. Client
| 5. Agency (any status, even pending)
| 6. Approved agency
| 7. Admin
|
*/


/*
|--------------------------------------------------------------------------
| 1. Public: authentication
|--------------------------------------------------------------------------
*/

// Registration: 3 requests per minute (a real user registers once)
Route::middleware('throttle:register')->group(function () {
    Route::post('/register/client', [AuthController::class, 'registerClient']);
    Route::post('/register/agency', [AuthController::class, 'registerAgency']);
});

// Login: 5 requests per minute, against brute force
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// E-mail verification: the link in the e-mail is signed by Laravel
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/resend', [AuthController::class, 'resendVerification'])
    ->middleware('throttle:3,1');


// Password reset (max 5 requests per minute)
Route::middleware('throttle:password-reset')->group(function () {
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});


/*
|--------------------------------------------------------------------------
| 2. Public: catalogue
|--------------------------------------------------------------------------
*/

Route::get('/cities', [CityController::class, 'index']);

// Cars (search and filters: see CarController::publicIndex)
Route::get('/cars', [CarController::class, 'publicIndex']);
Route::get('/cars/{car}', [CarController::class, 'publicShow']);
Route::get('/cars/{car}/availability', [CarAvailabilityController::class, 'check']);

// Reviews
Route::get('/cars/{car}/reviews', [ReviewController::class, 'carReviews']);
Route::get('/agencies/{agency}/reviews', [ReviewController::class, 'agencyReviews']);

// SmartDrive AI: vehicles eligible for a trip (max 30 requests per minute)
Route::post('/smartdrive/eligible-vehicles', [SmartDriveController::class, 'eligibleVehicles'])
    ->middleware('throttle:30,1');


/*
|--------------------------------------------------------------------------
| 3. Any logged-in user
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'user.active'])->group(function () {

    Route::get('/me', function (Request $request) {
        return response()->json([
            'user' => $request->user(),
        ]);
    });

    Route::post('/logout', [AuthController::class, 'logout']);
});


/*
|--------------------------------------------------------------------------
| 4. Client
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'user.active', 'role:client'])->group(function () {

    Route::get('/client/test', function () {
        return response()->json([
            'message' => 'Client access granted.',
        ]);
    });

    // Reservations
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
    Route::put('/reservations/{reservation}', [ReservationController::class, 'update']);
    Route::patch('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);
    Route::patch('/reservations/{reservation}/dispute', [ReservationController::class, 'dispute']);

    // Pickup / return confirmation (client side)
    Route::patch('/reservations/{reservation}/pickup/confirm-client', [ReservationController::class, 'confirmPickup']);
    Route::patch('/reservations/{reservation}/return/confirm-client', [ReservationController::class, 'confirmReturn']);

    // Payments
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);

    // Reviews
    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/reviews/{review}', [ReviewController::class, 'show']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
});


/*
|--------------------------------------------------------------------------
| 5. Agency (any status, even pending)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'user.active', 'role:agency'])->group(function () {

    // Profile
    Route::get('/agency/profile', [AgencyController::class, 'show']);
    Route::put('/agency/profile', [AgencyController::class, 'update']);

    // Reject a paid reservation (refunds 100%)
    Route::patch('/reservations/{reservation}/reject', [ReservationController::class, 'reject']);

    // Refunds
    Route::post('/refunds', [RefundController::class, 'store']);
    Route::patch('/refunds/{refund}/decision', [RefundController::class, 'decide']);
});


/*
|--------------------------------------------------------------------------
| 6. Approved agency
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'user.active', 'role:agency', 'agency.approved'])->group(function () {

    // Dashboard
    Route::get('/agency/reservations', [ReservationController::class, 'agencyIndex']);
    Route::get('/agency/refunds', [RefundController::class, 'index']);
    Route::get('/agency/stats', [AgencyController::class, 'stats']);

    // Pickup / return points
    Route::get('/agency/points', [AgencyPointController::class, 'index']);
    Route::post('/agency/points', [AgencyPointController::class, 'store']);
    Route::get('/agency/points/{agencyPoint}', [AgencyPointController::class, 'show']);
    Route::put('/agency/points/{agencyPoint}', [AgencyPointController::class, 'update']);
    Route::patch('/agency/points/{agencyPoint}/toggle-status', [AgencyPointController::class, 'toggleStatus']);

    // Cars
    Route::get('/agency/cars', [CarController::class, 'index']);
    Route::post('/agency/cars', [CarController::class, 'store']);
    Route::get('/agency/cars/{car}', [CarController::class, 'show']);
    Route::put('/agency/cars/{car}', [CarController::class, 'update']);
    Route::patch('/agency/cars/{car}/disable', [CarController::class, 'disable']);

    // Car images
    Route::get('/agency/cars/{car}/images', [CarImageController::class, 'index']);
    Route::post('/agency/cars/{car}/images', [CarImageController::class, 'store']);
    Route::patch('/agency/cars/{car}/images/{image}/primary', [CarImageController::class, 'setPrimary']);
    Route::delete('/agency/cars/{car}/images/{image}', [CarImageController::class, 'destroy']);

    // Pickup / return confirmation (agency side)
    Route::patch('/reservations/{reservation}/pickup/confirm-agency', [ReservationController::class, 'confirmAgencyPickup']);
    Route::patch('/reservations/{reservation}/return/confirm-agency', [ReservationController::class, 'confirmAgencyReturn']);
});


/*
|--------------------------------------------------------------------------
| 7. Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'user.active', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'show']);
    Route::get('/admin/agencies', [AgencyApprovalController::class, 'index']);
    Route::get('/admin/agencies/{agency}', [AgencyApprovalController::class, 'show']);
    Route::patch('/admin/agencies/{agency}', [AgencyApprovalController::class, 'update']);
    Route::delete('/admin/agencies/{agency}', [AgencyApprovalController::class, 'destroy']);
    Route::patch('/admin/agencies/{agency}/approve', [AgencyApprovalController::class, 'approve']);
    Route::patch('/admin/agencies/{agency}/reject', [AgencyApprovalController::class, 'reject']);
    Route::get('/admin/users', [AdminDirectoryController::class, 'users']);
    Route::get('/admin/cars', [AdminDirectoryController::class, 'cars']);
    Route::get('/admin/reservations', [AdminDirectoryController::class, 'reservations']);
    Route::get('/admin/revenue', [AdminDirectoryController::class, 'revenue']);
    Route::get('/admin/reviews', [AdminDirectoryController::class, 'reviews']);
    Route::get('/admin/profile', [AdminProfileController::class, 'show']);
    Route::patch('/admin/profile', [AdminProfileController::class, 'update']);
});
