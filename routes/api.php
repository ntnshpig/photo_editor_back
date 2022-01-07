<?php

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
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/password-reset', [AuthController::class, 'reset_password']);
    Route::post('/password-reset/{token}', [AuthController::class, 'confirm_token']);
    Route::post('/email-confirmation/{token}', [AuthController::class, 'confirm_email']);
});
    
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('user')->group(function () {
        Route::get('/user_info', [UserController::class, 'sent_user_info']);
        Route::post('/{user_id}', [UserController::class, 'update']);
        Route::post('/update/password', [UserController::class, 'update_password']);
    });
});
