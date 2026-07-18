<?php

use App\Http\Controllers\Web\WebAuthController;
use App\Http\Controllers\Web\WebCarController;
use App\Http\Controllers\Web\WebOwnerController;
use App\Http\Controllers\Web\WebReservationController;
use App\Http\Controllers\Web\WebAdminController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public Views
Route::get('/', [WebCarController::class, 'index'])->name('landing');
Route::get('/cars/{car}', [WebCarController::class, 'show'])->name('cars.show');

Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login']);

Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
Route::post('/register/client', [WebAuthController::class, 'registerClient']);
Route::post('/register/agency', [WebAuthController::class, 'registerAgency']);

// Authenticated Views
Route::middleware('auth')->group(function () {
    // Logout Action
    Route::post('/auth/logout', [WebAuthController::class, 'logout'])->name('logout');

    // ── Client Dashboard & Actions
    Route::middleware('role:client')->group(function () {
        Route::get('/client/reservations', [WebReservationController::class, 'index'])
            ->name('client.reservations');
        Route::post('/client/reservations', [WebReservationController::class, 'store']);
        Route::post('/client/reservations/{reservation}/pay', [WebReservationController::class, 'pay'])
            ->name('client.reservations.pay');
        Route::delete('/client/reservations/{reservation}', [WebReservationController::class, 'cancel'])
            ->name('client.reservations.cancel');
        Route::post('/client/reservations/{reservation}/refund', [WebReservationController::class, 'refund'])
            ->name('client.reservations.refund');
        Route::post('/client/reservations/{reservation}/review', [WebReservationController::class, 'review'])
            ->name('client.reservations.review');
    });

    // ── Agency Owner Dashboard & Actions
    Route::middleware('role:agency_owner')->group(function () {
        Route::get('/owner/dashboard', [WebOwnerController::class, 'index'])->name('agency.dashboard');
        Route::post('/owner/cars', [WebOwnerController::class, 'storeCar'])->name('agency.cars.store');
        Route::put('/owner/cars/{car}', [WebOwnerController::class, 'updateCar'])->name('agency.cars.update');
        Route::delete('/owner/cars/{car}', [WebOwnerController::class, 'destroyCar'])->name('agency.cars.destroy');
        Route::delete('/owner/cars/{car}/images/{image}', [WebOwnerController::class, 'destroyImage'])->name('agency.cars.images.destroy');
    });

    // Admin control panel route binding
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/dashboard', [WebAdminController::class, 'index'])->name('admin.dashboard');
        Route::post('/admin/agencies/{agency}/approve', [WebAdminController::class, 'approveAgency'])->name('admin.agencies.approve');
        Route::post('/admin/agencies/{agency}/reject', [WebAdminController::class, 'rejectAgency'])->name('admin.agencies.reject');
        Route::post('/admin/users/{user}/suspend', [WebAdminController::class, 'suspendUser'])->name('admin.users.suspend');
        Route::post('/admin/users/{user}/activate', [WebAdminController::class, 'activateUser'])->name('admin.users.activate');
    });
});

