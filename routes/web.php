<?php

use App\Events\TestMessage;
use App\Http\Controllers\MessageController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Route::view('/' , 'layouts.master');

Route::get('/search-users', function (Request $request) {
    $search = $request->input('search');  

    $users = User::where('id', '!=', Auth::id())
        ->where('name', 'like', "%{$search}%")
        ->get();

    return response()->json($users);  
})->middleware('auth');


// Route::get('/test-broadcast', function () {
//     broadcast(new TestMessage());
//     return "Event Fired";
// });

Route::middleware('auth')->group(function () {
    Route::post('/send-message', [MessageController::class, 'sendMessage']);
    Route::get('/messages/{user}', [MessageController::class, 'getMessages']);
});


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/auth/auth.php';
require __DIR__.'/profile.php';