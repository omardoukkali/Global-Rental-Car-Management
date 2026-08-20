<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

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

    Route::post('/logout', [
        AuthController::class,
        'logout'
    ]);

});