<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Publik\Auth\LoginController;
use App\Http\Controllers\Publik\Auth\ResetPasswordController;
use App\Http\Controllers\Publik\Auth\ForgotPasswordController;

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

Route::post('/login', [LoginController::class, 'login']);
Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/logout', [LoginController::class, 'logout'])->middleware('web');

    Route::prefix('pemantau-suara/dashboard')->group(function () {
        Route::get('/account-info', [LoginController::class, 'getInfoUserLogin']);
    });
});
