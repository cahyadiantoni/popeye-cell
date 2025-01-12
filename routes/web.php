<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataUserController;
use App\Http\Controllers\DataGudangController;
use App\Http\Controllers\DataBarangController;
use App\Http\Controllers\StokGudangController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\TransaksiFakturController;
use App\Http\Controllers\TransaksiReturnController;
use App\Exports\BarangExport;
use Maatwebsite\Excel\Facades\Excel;


Auth::routes();
Auth::routes(['register' => false]);


Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->middleware('auth');
Route::resource('/data-user', DataUSerController::class)->middleware('auth');
Route::resource('/data-gudang', DataGudangController::class)->middleware('auth');
Route::resource('/data-barang', DataBarangController::class)->middleware('auth');
Route::get('/request-masuk-gudang', [StokGudangController::class, 'request_masuk'])->middleware('auth');
Route::post('/handle-request', [StokGudangController::class, 'handleRequest'])->middleware('auth')->name('handleRequest');
Route::get('/stok-opname', [StokGudangController::class, 'stok_opname'])->middleware('auth')->name('stokOpname');
Route::get('/choice-gudang', [StokGudangController::class, 'choice_gudang'])->middleware('auth')->name('choiceGudang');
Route::post('/kirim-barang', [StokGudangController::class, 'kirimBarang'])->middleware('auth')->name('kirimBarang');
Route::get('/history-kirim', [StokGudangController::class, 'history_kirim'])->middleware('auth')->name('historyKirim');
Route::resource('/transaksi-jual', TransaksiController::class)->middleware('auth');
Route::resource('/transaksi-faktur', TransaksiFakturController::class)->middleware('auth');
Route::get('/transaksi-faktur/{nomor_faktur}', [TransaksiFakturController::class, 'show'])->name('transaksi-faktur.show');
Route::resource('/transaksi-return', TransaksiReturnController::class)->middleware('auth');
Route::post('/return-barang', [TransaksiReturnController::class, 'returnBarang'])->name('returnBarang');
Route::delete('/transaksi-return/{lok_spk}', [TransaksiReturnController::class, 'destroy'])->name('transaksi-return.delete');

Route::get('/export-barang', function () {
    return Excel::download(new BarangExport, 'stok_barang.xlsx');
})->name('export.barang');

Route::get('/transaksi-faktur/{nomor_faktur}/print', [TransaksiFakturController::class, 'printPdf'])->name('transaksi-faktur.print');

Route::delete('/transaksi-jual/{lok_spk}', [TransaksiController::class, 'destroy'])->name('transaksi-jual.delete');
Route::put('/transaksi-jual/update', [TransaksiController::class, 'update'])->name('transaksi-jual.update');
Route::post('/transaksi-jual/addbarang', [TransaksiFakturController::class, 'addbarang'])->name('transaksi-jual.addbarang');

Route::put('/transaksi-faktur/update/{nomor_faktur}', [TransaksiFakturController::class, 'update'])->name('transaksi-faktur.update');




