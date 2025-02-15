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
use App\Http\Controllers\CekSOController;
use App\Exports\BarangExport;
use Maatwebsite\Excel\Facades\Excel;


Auth::routes();
Auth::routes(['register' => false]);


Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->middleware('auth');
Route::resource('/data-user', DataUSerController::class)->middleware('auth');
Route::resource('/data-gudang', DataGudangController::class)->middleware('auth');
Route::resource('/data-barang', DataBarangController::class)->middleware('auth');
Route::get('/mass-edit-barang', [DataBarangController::class, 'massedit'])->middleware('auth');
Route::post('/mass-update-data-barang', [DataBarangController::class, 'massUpdateDataBarang'])->middleware('auth')->name('mass-update.dataBarang');
Route::put('/update-data-barang/{lok_spk}', [DataBarangController::class, 'updateDataBarang'])->middleware('auth');
Route::get('/request-masuk-gudang', [StokGudangController::class, 'request_masuk'])->middleware('auth');
Route::post('/handle-request', [StokGudangController::class, 'handleRequest'])->middleware('auth')->name('handleRequest');
Route::get('/stok-opname', [StokGudangController::class, 'stok_opname'])->middleware('auth')->name('stokOpname');
Route::get('/choice-gudang', [StokGudangController::class, 'choice_gudang'])->middleware('auth')->name('choiceGudang');

// Route::post('/kirim-barang', [StokGudangController::class, 'kirimBarang'])->middleware('auth')->name('kirimBarang');
Route::delete('/kirim-barang/{id}', [KirimBarangController::class, 'destroy'])->name('kirim-barang.delete');
Route::delete('/kirim-barang/deletebarang/{id}', [KirimBarangController::class, 'destroybarang'])->name('kirim-barang.deletebarang');
// Route::get('/history-kirim', [StokGudangController::class, 'history_kirim'])->middleware('auth')->name('historyKirim');
Route::resource('/kirim-barang', KirimBarangController::class)->middleware('auth');
Route::get('/kirim-barang/{id}/print', [KirimBarangController::class, 'printPdf'])->name('kirim-barang.print');
Route::post('/kirim-barang/addbarang', [KirimBarangController::class, 'addbarang'])->name('kirim-barang.addbarang');
Route::post('/kirim-barang/upload-bukti', [KirimBarangController::class, 'uploadBukti'])->name('kirim-barang.upload-bukti');
Route::get('/kirim-barang/{id}/print-bukti', [KirimBarangController::class, 'printBukti'])->name('kirim-barang.print-bukti');

Route::resource('/terima-barang', TerimaBarangController::class)->middleware('auth');
Route::post('/terima-barang/terima', [TerimaBarangController::class, 'terima'])->name('terima-barang.terima');
Route::post('/terima-barang/tolak', [TerimaBarangController::class, 'tolak'])->name('terima-barang.tolak');
Route::get('/terima-barang/export/{id}', [TerimaBarangController::class, 'export'])->name('terima-barang.export');

Route::resource('/transaksi-return', TransaksiReturnController::class)->middleware('auth');
Route::post('/return-barang', [TransaksiReturnController::class, 'returnBarang'])->name('returnBarang');
Route::delete('/transaksi-return/{lok_spk}', [TransaksiReturnController::class, 'destroy'])->name('transaksi-return.delete');

Route::get('/export-barang/{id}', function ($id) {
    return Excel::download(new BarangExport($id), 'stok_barang_' . $id . '.xlsx');
})->name('export.barang');

Route::resource('/transaksi-faktur', TransaksiFakturController::class)->middleware('auth');
Route::delete('/transaksi-faktur/{nomor_faktur}', [TransaksiFakturController::class, 'destroy'])->name('transaksi-faktur.delete');
Route::get('/transaksi-faktur/{nomor_faktur}', [TransaksiFakturController::class, 'show'])->name('transaksi-faktur.show');
Route::get('/transaksi-faktur/{nomor_faktur}/print', [TransaksiFakturController::class, 'printPdf'])->name('transaksi-faktur.print');
Route::put('/transaksi-faktur/update/{nomor_faktur}', [TransaksiFakturController::class, 'update'])->name('transaksi-faktur.update');
Route::post('/transaksi-faktur/upload-bukti', [TransaksiFakturController::class, 'uploadBukti'])->name('transaksi-faktur.upload-bukti');
Route::put('/transaksi-faktur/{id}/tandai-sudah-dicek', [TransaksiFakturController::class, 'tandaiSudahDicek'])->name('transaksi-faktur.tandai-sudah-dicek');

Route::resource('/transaksi-jual', TransaksiController::class)->middleware('auth');
Route::delete('/transaksi-jual/{lok_spk}', [TransaksiController::class, 'destroy'])->name('transaksi-jual.delete');
Route::put('/transaksi-jual/update', [TransaksiController::class, 'update'])->name('transaksi-jual.update');
Route::post('/transaksi-jual/addbarang', [TransaksiFakturController::class, 'addbarang'])->name('transaksi-jual.addbarang');
Route::get('/suggest-no-fak', [TransaksiController::class, 'getSuggestNoFak'])->name('suggest.no.fak');

Route::resource('/transaksi-jual-online', TransaksiOnlineController::class)->middleware('auth');
Route::delete('/transaksi-jual-online/{lok_spk}', [TransaksiOnlineController::class, 'destroy'])->name('transaksi-jual-online.delete');
Route::put('/transaksi-jual-online/update', [TransaksiOnlineController::class, 'update'])->name('transaksi-jual-online.update');
Route::post('/transaksi-jual-online/addbarang', [TransaksiFakturOnlineController::class, 'addbarang'])->name('transaksi-jual-online.addbarang');
Route::get('/suggest-no-fak-online', [TransaksiOnlineController::class, 'getSuggestNoFak'])->name('suggest.no.fak.online');

Route::resource('/transaksi-faktur-online', TransaksiFakturOnlineController::class)->middleware('auth');
Route::delete('/transaksi-faktur-online/{nomor_faktur}', [TransaksiFakturOnlineController::class, 'destroy'])->name('transaksi-faktur-online.delete');
Route::get('/transaksi-faktur-online/{nomor_faktur}', [TransaksiFakturOnlineController::class, 'show'])->name('transaksi-faktur-online.show');
Route::get('/transaksi-faktur-online/{nomor_faktur}/print', [TransaksiFakturOnlineController::class, 'printPdf'])->name('transaksi-faktur-online.print');
Route::put('/transaksi-faktur-online/update/{nomor_faktur}', [TransaksiFakturOnlineController::class, 'update'])->name('transaksi-faktur-online.update');
Route::post('/transaksi-faktur-online/upload-bukti', [TransaksiFakturOnlineController::class, 'uploadBukti'])->name('transaksi-faktur-online.upload-bukti');
Route::put('/transaksi-faktur-online/{id}/tandai-sudah-dicek', [TransaksiFakturOnlineController::class, 'tandaiSudahDicek'])->name('transaksi-faktur-online.tandai-sudah-dicek');

Route::resource('/cek-so', CekSOController::class)->middleware('auth');
Route::get('/get-last-kode/{gudang_id}', [CekSOController::class, 'getLastKode']);
Route::get('/get-cek-so/{id}/barangs', [CekSOController::class, 'getCekSOBarangs'])->name('get-cekso.barangs');
Route::post('/scan-cek-so', [CekSOController::class, 'scan'])->name('cekso.scan');
Route::post('/upload-cek-so', [CekSOController::class, 'upload'])->name('cekso.upload');
Route::post('/finish-cek-so', [CekSOController::class, 'finish'])->name('cekso.finish');
Route::get('/finish-cek-so/{id}', [CekSOController::class, 'showFinish'])->name('cekso.showFinish');
Route::get('/get-cek-so-finish/{id}', [CekSOController::class, 'getCekSOFinish'])->name('get-cekso.finish');