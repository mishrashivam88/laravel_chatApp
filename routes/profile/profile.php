<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;


Route::get('profile/{id}' , [ProfileController::class , 'index'])->name('profile');
Route::patch('profile/update/user/{id}' , [ProfileController::class , 'storee'])->name('profile.update.now');
