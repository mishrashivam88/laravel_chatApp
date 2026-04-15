<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\MessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function(){
    Route::post('/register' , [AuthController::class , 'register']);
    Route::post('/login' , [AuthController::class , 'login']);
    Route::post('/logout', [AuthController::class , 'logout'])->middleware('auth:sanctum');
    Route::post('/change-pass', [AuthController::class , 'changePassword'])->middleware('auth:sanctum');
    Route::post('/forgot-pass', [AuthController::class , 'forgotPassword']);
    Route::post('/verify-otp', [AuthController::class , 'verifyOtp']);
    Route::post('/get-all-users' , [AuthController::class , 'getAllUsers'])->middleware('auth:sanctum');
    Route::post('/get-messages', [AuthController::class, 'getMessagesApi'])->middleware('auth:sanctum');
});