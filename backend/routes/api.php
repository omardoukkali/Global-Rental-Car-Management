<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

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