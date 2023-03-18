<?php

use App\Http\Controllers\CommandHandler;
use App\Http\Controllers\EventHandler;
use App\Http\Controllers\UserSlackController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test',[AuthController::class, 'nextAuth']);

Route::get('/finish_auth', [AuthController::class, 'finishAuth']);

Route::post('/events', [EventHandler::class, 'dispatch']);
Route::post('/commands', [CommandHandler::class, 'dispatch']);

Route::resource('user-slacks', UserSlackController::class);

