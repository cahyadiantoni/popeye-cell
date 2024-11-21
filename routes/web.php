<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataUserController;
use App\Http\Controllers\DataGudangController;
use App\Http\Controllers\DataBarangController;
use App\Http\Controllers\StokGudangController;
use App\Http\Controllers\TransaksiController;


Auth::routes();
Auth::routes(['register' => false]);


Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->middleware('auth');
Route::resource('/data-user', DataUSerController::class)->middleware('auth');
Route::resource('/data-gudang', DataGudangController::class)->middleware('auth');
Route::resource('/data-barang', DataBarangController::class)->middleware('auth');
Route::get('/request-masuk-gudang', [StokGudangController::class, 'request_masuk'])->middleware('auth');
Route::post('/handle-request', [StokGudangController::class, 'handleRequest'])->middleware('auth')->name('handleRequest');
Route::get('/stok-opname', [StokGudangController::class, 'stok_opname'])->middleware('auth');
Route::post('/kirim-barang', [StokGudangController::class, 'kirimBarang'])->middleware('auth')->name('kirimBarang');
Route::get('/history-kirim', [StokGudangController::class, 'history_kirim'])->middleware('auth')->name('historyKirim');
Route::resource('/transaksi-jual', TransaksiController::class)->middleware('auth');

