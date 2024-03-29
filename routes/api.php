<?php

use App\Http\Controllers\API\CheckinRPC;
use App\Http\Controllers\API\MessageRPC;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/checkin', [CheckinRPC::class, 'handleRpc']);
Route::post('/message', [MessageRPC::class, 'handleRpc']);
