<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AvailabilityController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group(['prefix' => 'auth'], function() {
    Route::post('login', [AuthController::class, 'login']);
});

// Define a route group for availability-related endpoints
Route::group(['prefix' => 'availability'], function () {

    // POST /availability
    // Endpoint to set availability for a user
    Route::post('', [AvailabilityController::class, 'setAvailability'])->middleware('auth:api');

    // GET /availability/{user_id}
    // Endpoint to get availability for a specific user
    Route::get('/{user_id}', [AvailabilityController::class, 'getAvailability']);

});

