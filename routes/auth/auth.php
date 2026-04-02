<?php

use App\Http\Controllers\AuthController\AuthController;
use Illuminate\Support\Facades\Route;

Route::view('register' , 'auth.register')->name('register');
Route::view('login' , 'auth.login')->name('login');

Route::post('registerSave' , [AuthController::class , 'register' ])->name('register.now');
Route::post('loginSave' , [AuthController::class , 'login' ])->name('login.now');

Route::middleware('auth')->group(function(){
    Route::view('/' , 'layouts.master');
    Route::post('logout' , [AuthController::class , 'logout'])->name('logout');
});