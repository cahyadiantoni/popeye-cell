<?php

use App\Http\Controllers\KirimBarangController;
use App\Http\Controllers\TerimaBarangController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataUserController;
use App\Http\Controllers\DataGudangController;
use App\Http\Controllers\DataBarangController;
use App\Http\Controllers\StokGudangController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\TransaksiFakturController;
use App\Http\Controllers\TransaksiOnlineController;
use App\Http\Controllers\TransaksiFakturOnlineController;
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

// Route::post('/kirim-barang', [StokGudangController::class, 'kirimBarang'])->middleware('auth')->name('kirimBarang');
Route::delete('/kirim-barang/{id}', [KirimBarangController::class, 'destroy'])->name('kirim-barang.delete');
Route::delete('/kirim-barang/deletebarang/{id}', [KirimBarangController::class, 'destroybarang'])->name('kirim-barang.deletebarang');
// Route::get('/history-kirim', [StokGudangController::class, 'history_kirim'])->middleware('auth')->name('historyKirim');
Route::resource('/kirim-barang', KirimBarangController::class)->middleware('auth');
Route::post('/kirim-barang/addbarang', [KirimBarangController::class, 'addbarang'])->name('kirim-barang.addbarang');

Route::resource('/terima-barang', TerimaBarangController::class)->middleware('auth');
Route::post('/terima-barang/terima', [TerimaBarangController::class, 'terima'])->name('terima-barang.terima');
Route::post('/terima-barang/tolak', [TerimaBarangController::class, 'tolak'])->name('terima-barang.tolak');

Route::resource('/transaksi-return', TransaksiReturnController::class)->middleware('auth');
Route::post('/return-barang', [TransaksiReturnController::class, 'returnBarang'])->name('returnBarang');
Route::delete('/transaksi-return/{lok_spk}', [TransaksiReturnController::class, 'destroy'])->name('transaksi-return.delete');

Route::get('/export-barang', function () {
    return Excel::download(new BarangExport, 'stok_barang.xlsx');
})->name('export.barang');

Route::resource('/transaksi-faktur', TransaksiFakturController::class)->middleware('auth');
Route::get('/transaksi-faktur/{nomor_faktur}', [TransaksiFakturController::class, 'show'])->name('transaksi-faktur.show');
Route::get('/transaksi-faktur/{nomor_faktur}/print', [TransaksiFakturController::class, 'printPdf'])->name('transaksi-faktur.print');
Route::put('/transaksi-faktur/update/{nomor_faktur}', [TransaksiFakturController::class, 'update'])->name('transaksi-faktur.update');

Route::resource('/transaksi-jual', TransaksiController::class)->middleware('auth');
Route::delete('/transaksi-jual/{lok_spk}', [TransaksiController::class, 'destroy'])->name('transaksi-jual.delete');
Route::put('/transaksi-jual/update', [TransaksiController::class, 'update'])->name('transaksi-jual.update');
Route::post('/transaksi-jual/addbarang', [TransaksiFakturController::class, 'addbarang'])->name('transaksi-jual.addbarang');

Route::resource('/transaksi-jual-online', TransaksiOnlineController::class)->middleware('auth');
Route::delete('/transaksi-jual-online/{lok_spk}', [TransaksiOnlineController::class, 'destroy'])->name('transaksi-jual-online.delete');
Route::put('/transaksi-jual-online/update', [TransaksiOnlineController::class, 'update'])->name('transaksi-jual-online.update');
Route::post('/transaksi-jual-online/addbarang', [TransaksiFakturOnlineController::class, 'addbarang'])->name('transaksi-jual-online.addbarang');

Route::resource('/transaksi-faktur-online', TransaksiFakturOnlineController::class)->middleware('auth');
Route::get('/transaksi-faktur-online/{nomor_faktur}', [TransaksiFakturOnlineController::class, 'show'])->name('transaksi-faktur-online.show');
Route::get('/transaksi-faktur-online/{nomor_faktur}/print', [TransaksiFakturOnlineController::class, 'printPdf'])->name('transaksi-faktur-online.print');
Route::put('/transaksi-faktur-online/update/{nomor_faktur}', [TransaksiFakturOnlineController::class, 'update'])->name('transaksi-faktur-online.update');


