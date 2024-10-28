<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataUserController;
use App\Http\Controllers\DataBarangController;


Auth::routes();
Auth::routes(['register' => false]);


Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->middleware('auth');
Route::resource('/data-user', DataUSerController::class)->middleware('auth');
Route::resource('/data-barang', DataBarangController::class)->middleware('auth');

