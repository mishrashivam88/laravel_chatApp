<?php

use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('/send-message', [MessageController::class, 'sendMessage']);
    Route::get('/messages/{user}', [MessageController::class, 'getMessages']);
    Route::post('/messages/{user}/seen', [MessageController::class, 'markAsSeen']);
    Route::post('/messages/delivered', [MessageController::class, 'markDelivered']);
    Route::delete('/delete-message/{id}', [MessageController::class, 'deleteMessage']);
    });