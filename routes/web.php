<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataUserController;


Auth::routes();
Auth::routes(['register' => false]);


Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->middleware('auth');
Route::resource('/data-user', DataUSerController::class)->middleware('auth');

