<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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


Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail']);
    Route::post('auth/password/reset', [ResetPasswordController::class, 'reset']);

    Route::middleware(['jwtAuth'])->group(function () {
        //Protected routes
        Route::post('auth/refresh', [AuthController::class, 'refreshToken']);
    });
});
