<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AddressController;
 

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Cebu address suggestions
Route::get('/addresses/cebu/suggest', [AddressController::class, 'suggest']);

// Video call API routes removed - now using Firebase Realtime Database
// All video call signaling is handled client-side with Firebase

// Get current active trade session for video call initialization
Route::middleware('auth')->get('/trades/get-current-session', [\App\Http\Controllers\TradeController::class, 'getCurrentSession']);

 
