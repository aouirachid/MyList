<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CollaboratorsController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
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
        Route::resource('/user',UserController::class);
        Route::resource('/task',TaskController::class);
        Route::resource('/tag',TagController::class);
        Route::resource('/document',DocumentController::class);
        Route::resource('/collaborators',CollaboratorsController::class);
    });
});
