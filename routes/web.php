<?php

use App\Http\Controllers\KirimBarangController;
use App\Http\Controllers\NegoanController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PulsaMasterController;
use App\Http\Controllers\PulsaReportController;
use App\Http\Controllers\TerimaBarangController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\DataUserController;
use App\Http\Controllers\DataGudangController;
use App\Http\Controllers\DataBarangController;
use App\Http\Controllers\StokGudangController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\TransaksiFakturController;
use App\Http\Controllers\TransaksiOutletController;
use App\Http\Controllers\TransaksiFakturOutletController;
use App\Http\Controllers\TransaksiBawahController;
use App\Http\Controllers\TransaksiFakturBawahController;
use App\Http\Controllers\TransaksiKesimpulanController;
use App\Http\Controllers\TransaksiOnlineController;
use App\Http\Controllers\TransaksiFakturOnlineController;
use App\Http\Controllers\TransaksiReturnController;
use App\Http\Controllers\CekSOController;
use App\Http\Controllers\AdmTodoTransferController;
use App\Http\Controllers\AdmSettingController;
use App\Http\Controllers\AdmItemTokpedController;
use App\Http\Controllers\AdmReqTokpedController;
use App\Http\Controllers\MacCheckController;
use App\Http\Controllers\FakturPaymentController;
use App\Http\Controllers\TokpedDepositController;
use App\Http\Controllers\TokpedOrderController;
use App\Http\Controllers\RiwayatBarangController;
use App\Http\Controllers\MasterHargaController;
use App\Http\Controllers\HistoryEditFakturAtasController;
use App\Http\Controllers\HistoryEditFakturBawahController;
use App\Http\Controllers\HistoryEditFakturOnlineController;
use App\Http\Controllers\HistoryEditFakturOutletController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\CheckMacAccess;
use App\Exports\BarangExport;
use App\Exports\FakturOnlineExport;
use Maatwebsite\Excel\Facades\Excel;


Auth::routes();
Auth::routes(['register' => false]);

Route::post('/payment/create', [FakturPaymentController::class, 'store'])->name('payment.store');
Route::post('/payment/retry', [FakturPaymentController::class, 'retry'])->name('payment.retry');
Route::post('/payment/callback', [FakturPaymentController::class, 'callback'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])->name('payment.callback');

Route::get('/mac-launcher', function (\Illuminate\Http\Request $request) {
    $mac = $request->query('mac');
    return view('mac-launcher', ['mac' => $mac]);
});

Route::post('/check-mac', [MacCheckController::class, 'check'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->middleware('auth', CheckMacAccess::class);

Route::middleware(['auth', CheckMacAccess::class, RoleMiddleware::class . ':sales'])->group(function () {
    Route::resource('/data-user', DataUSerController::class)->middleware('auth');
    Route::resource('/data-gudang', DataGudangController::class)->middleware('auth');
    Route::resource('/data-barang', DataBarangController::class)->middleware('auth');
    Route::get('/mass-edit-barang', [DataBarangController::class, 'massedit'])->middleware('auth');
    Route::post('/mass-update-data-barang', [DataBarangController::class, 'massUpdateDataBarang'])->middleware('auth')->name('mass-update.dataBarang');
    Route::get('/mass-edit-barang-user', [DataBarangController::class, 'masseditUser'])->middleware('auth');
    Route::post('/mass-update-data-barang-user', [DataBarangController::class, 'massUpdateDataBarangUser'])->middleware('auth')->name('mass-update.dataBarangUser');
    Route::put('/update-data-barang/{lok_spk}', [DataBarangController::class, 'updateDataBarang'])->middleware('auth');
    Route::get('/request-masuk-gudang', [StokGudangController::class, 'request_masuk'])->middleware('auth');
    Route::post('/handle-request', [StokGudangController::class, 'handleRequest'])->middleware('auth')->name('handleRequest');
    Route::get('/stok-opname', [StokGudangController::class, 'stok_opname'])->middleware('auth')->name('stokOpname');
    Route::get('/choice-gudang', [StokGudangController::class, 'choice_gudang'])->middleware('auth')->name('choiceGudang');
    Route::get('/buku-stok', [StokGudangController::class, 'index'])->name('buku-stok.index');
    
    Route::get('/riwayat-barang', [RiwayatBarangController::class, 'index'])->name('riwayat-barang.index')->middleware('auth');
    Route::post('/riwayat-barang/{history}/upload-foto', [RiwayatBarangController::class, 'uploadFoto'])->name('riwayat-barang.uploadFoto');
    Route::post('/riwayat-barang/{history}/hapus-foto', [RiwayatBarangController::class, 'hapusFoto'])->name('riwayat-barang.hapusFoto');

    Route::get('/master-harga', [MasterHargaController::class, 'index'])->name('master-harga.index');
    Route::post('/master-harga', [MasterHargaController::class, 'store'])->name('master-harga.store');
    Route::get('/master-harga/export', [MasterHargaController::class, 'export'])->name('master-harga.export')->middleware('auth');
    Route::post('/master-harga/import-pivot', [MasterHargaController::class, 'importPivot'])->name('master-harga.importPivot');
    Route::post('/master-harga/update-cell', [MasterHargaController::class, 'updateCell'])->name('master-harga.updateCell')->middleware('auth');
    Route::post('/master-harga/update-row', [MasterHargaController::class, 'updateRow'])->name('master-harga.updateRow')->middleware('auth');

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

    Route::get('/transaksi-return/suggest', [TransaksiReturnController::class, 'getSuggest'])->name('transaksi-return.suggest');
    Route::resource('/transaksi-return', TransaksiReturnController::class)->middleware('auth');
    Route::delete('/transaksi-return-barang/{id}', [TransaksiReturnController::class, 'destroyBarang'])->name('transaksi-return-barang.delete');
    Route::put('/transaksi-return-barang', [TransaksiReturnController::class, 'updateBarang'])->name('transaksi-return-barang.update');
    Route::post('/transaksi-return-barang/addbarang', [TransaksiReturnController::class, 'addBarang'])->name('transaksi-return-barang.addbarang');

    Route::get('/export-barang/{id}', function (Request $request, $id) {
        $jenis = $request->query('jenis');
        return Excel::download(new BarangExport($id, $jenis), 'stok_barang_' . $id . '.xlsx');
    })->name('export.barang');

    Route::get('/transaksi-faktur/print-kesimpulan', [TransaksiFakturController::class, 'printKesimpulan'])->name('transaksi-faktur.printKesimpulan');
    Route::get('/transaksi-faktur/print-multiple', [TransaksiFakturController::class, 'printMultiple'])->name('transaksi-faktur.printMultiple');
    Route::get('/transaksi-faktur/export', [TransaksiFakturController::class, 'exportMultiple'])->name('transaksi-faktur.exportMultiple');
    Route::resource('/transaksi-faktur', TransaksiFakturController::class)->middleware('auth');
    Route::delete('/transaksi-faktur/{nomor_faktur}', [TransaksiFakturController::class, 'destroy'])->name('transaksi-faktur.delete');
    Route::get('/transaksi-rekap', [TransaksiFakturController::class, 'rekap'])->name('transaksi-faktur.rekap');
    Route::get('/transaksi-faktur/{nomor_faktur}', [TransaksiFakturController::class, 'show'])->name('transaksi-faktur.show');
    Route::get('/transaksi-faktur/{nomor_faktur}/print', [TransaksiFakturController::class, 'printPdf'])->name('transaksi-faktur.print');
    Route::put('/transaksi-faktur/update/{nomor_faktur}', [TransaksiFakturController::class, 'update'])->name('transaksi-faktur.update');
    Route::post('/transaksi-faktur/upload-bukti', [TransaksiFakturController::class, 'uploadBukti'])->name('transaksi-faktur.upload-bukti');
    Route::put('/transaksi-faktur/{id}/tandai-sudah-dicek', [TransaksiFakturController::class, 'tandaiSudahDicek'])->name('transaksi-faktur.tandai-sudah-dicek');
    Route::put('/transaksi-faktur/{id}/tandai-belum-dicek', [TransaksiFakturController::class, 'tandaiBelumDicek'])->name('transaksi-faktur.tandai-belum-dicek');
    Route::post('/transaksi-faktur/bukti', [TransaksiFakturController::class, 'storeBukti'])->name('transaksi-faktur.bukti.store');
    Route::delete('/transaksi-faktur/bukti/{id}', [TransaksiFakturController::class, 'deleteBukti'])->name('transaksi-faktur.bukti.delete');

    Route::get('/transaksi-jual/data', [TransaksiController::class, 'getData'])->name('transaksi-jual.data');
    Route::resource('/transaksi-jual', TransaksiController::class)->middleware('auth');
    Route::post('/transaksi-jual/addbarang', [TransaksiController::class, 'addbarang'])->name('transaksi-jual.addbarang');
    Route::get('/suggest-no-fak', [TransaksiController::class, 'getSuggestNoFak'])->name('suggest.no.fak');
    Route::get('/history-edit-faktur-atas', [HistoryEditFakturAtasController::class, 'index'])->name('history-edit-faktur-atas.index')->middleware('auth');

    Route::get('/transaksi-faktur-bawah/print-kesimpulan', [TransaksiFakturBawahController::class, 'printKesimpulan'])->name('transaksi-faktur-bawah.printKesimpulan');
    Route::get('/transaksi-faktur-bawah/print-multiple', [TransaksiFakturBawahController::class, 'printMultiple'])->name('transaksi-faktur-bawah.printMultiple');
    Route::get('/transaksi-faktur-bawah/export', [TransaksiFakturBawahController::class, 'exportMultiple'])->name('transaksi-faktur-bawah.exportMultiple');
    Route::resource('/transaksi-faktur-bawah', TransaksiFakturBawahController::class)->middleware('auth');
    Route::delete('/transaksi-faktur-bawah/{nomor_faktur}', [TransaksiFakturBawahController::class, 'destroy'])->name('transaksi-faktur-bawah.delete');
    Route::get('/transaksi-faktur-bawah/{nomor_faktur}', [TransaksiFakturBawahController::class, 'show'])->name('transaksi-faktur-bawah.show');
    Route::get('/transaksi-faktur-bawah/{nomor_faktur}/print', [TransaksiFakturBawahController::class, 'printPdf'])->name('transaksi-faktur-bawah.print');
    Route::put('/transaksi-faktur-bawah/update/{nomor_faktur}', [TransaksiFakturBawahController::class, 'update'])->name('transaksi-faktur-bawah.update');
    Route::put('/transaksi-faktur-bawah/{id}/tandai-sudah-dicek', [TransaksiFakturBawahController::class, 'tandaiSudahDicek'])->name('transaksi-faktur-bawah.tandai-sudah-dicek');
    Route::put('/transaksi-faktur-bawah/{id}/tandai-belum-dicek', [TransaksiFakturBawahController::class, 'tandaiBelumDicek'])->name('transaksi-faktur-bawah.tandai-belum-dicek');

    Route::get('/transaksi-jual-bawah/data', [TransaksiBawahController::class, 'getData'])->name('transaksi-jual-bawah.data');
    Route::resource('/transaksi-jual-bawah', TransaksiBawahController::class)->middleware('auth');
    Route::post('/transaksi-jual-bawah/addbarang', [TransaksiBawahController::class, 'addbarang'])->name('transaksi-jual-bawah.addbarang');
    Route::get('/suggest-no-fak-bawah', [TransaksiBawahController::class, 'getSuggestNoFak'])->name('suggest.no.fak.bawah');
    Route::get('/history-edit-faktur-bawah', [HistoryEditFakturBawahController::class, 'index'])->name('history-edit-faktur-bawah.index')->middleware('auth');

    Route::resource('/transaksi-kesimpulan', TransaksiKesimpulanController::class)->middleware('auth');
    Route::delete('/transaksi-kesimpulan/{kesimpulan_id}', [TransaksiKesimpulanController::class, 'destroy'])->name('transaksi-kesimpulan.delete');
    Route::get('/transaksi-kesimpulan/{kesimpulan_id}', [TransaksiKesimpulanController::class, 'show'])->name('transaksi-kesimpulan.show');
    Route::get('/transaksi-kesimpulan/{kesimpulan_id}/print', [TransaksiKesimpulanController::class, 'printPdf'])->name('transaksi-kesimpulan.print');
    Route::get('/transaksi-kesimpulan/{kesimpulan_id}/print-all', [TransaksiKesimpulanController::class, 'printAllPdf'])->name('transaksi-kesimpulan.print-all');
    Route::put('/transaksi-kesimpulan/update/{kesimpulan_id}', [TransaksiKesimpulanController::class, 'update'])->name('transaksi-kesimpulan.update');
    Route::post('/transaksi-kesimpulan/upload-bukti', [TransaksiKesimpulanController::class, 'uploadBukti'])->name('transaksi-kesimpulan.upload-bukti');
    Route::put('/transaksi-kesimpulan/{id}/tandai-sudah-dicek', [TransaksiKesimpulanController::class, 'tandaiSudahDicek'])->name('transaksi-kesimpulan.tandai-sudah-dicek');
    Route::post('/transaksi-kesimpulan/bukti', [TransaksiKesimpulanController::class, 'storeBukti'])->name('transaksi-kesimpulan.bukti.store');
    Route::delete('/transaksi-kesimpulan/bukti/{id}', [TransaksiKesimpulanController::class, 'deleteBukti'])->name('transaksi-kesimpulan.bukti.delete');
    
    Route::get('/transaksi-jual-online/data', [TransaksiOnlineController::class, 'getData'])->name('transaksi-jual-online.data');
    Route::resource('/transaksi-jual-online', TransaksiOnlineController::class)->middleware('auth');
    Route::post('/transaksi-jual-online/addbarang', [TransaksiOnlineController::class, 'addbarang'])->name('transaksi-jual-online.addbarang');
    Route::get('/suggest-no-fak-online', [TransaksiOnlineController::class, 'getSuggestNoFak'])->name('suggest.no.fak.online');
    Route::get('/history-edit-faktur-online', [HistoryEditFakturOnlineController::class, 'index'])->name('history-edit-faktur-online.index')->middleware('auth');
    
    Route::get('/transaksi-faktur-online/print-kesimpulan', [TransaksiFakturOnlineController::class, 'printKesimpulan'])->name('transaksi-faktur-online.printKesimpulan');
    Route::resource('/transaksi-faktur-online', TransaksiFakturOnlineController::class)->middleware('auth');
    Route::delete('/transaksi-faktur-online/{nomor_faktur}', [TransaksiFakturOnlineController::class, 'destroy'])->name('transaksi-faktur-online.delete');
    Route::get('/transaksi-rekap-online', [TransaksiFakturOnlineController::class, 'rekap'])->name('transaksi-faktur-online.rekap');
    Route::get('/transaksi-faktur-online/{nomor_faktur}', [TransaksiFakturOnlineController::class, 'show'])->name('transaksi-faktur-online.show');
    Route::get('/transaksi-faktur-online/{nomor_faktur}/print', [TransaksiFakturOnlineController::class, 'printPdf'])->name('transaksi-faktur-online.print');
    Route::put('/transaksi-faktur-online/update/{nomor_faktur}', [TransaksiFakturOnlineController::class, 'update'])->name('transaksi-faktur-online.update');
    Route::post('/transaksi-faktur-online/upload-bukti', [TransaksiFakturOnlineController::class, 'uploadBukti'])->name('transaksi-faktur-online.upload-bukti');
    Route::put('/transaksi-faktur-online/{id}/tandai-sudah-dicek', [TransaksiFakturOnlineController::class, 'tandaiSudahDicek'])->name('transaksi-faktur-online.tandai-sudah-dicek');
    Route::put('/transaksi-faktur-online/{id}/tandai-belum-dicek', [TransaksiFakturOnlineController::class, 'tandaiBelumDicek'])->name('transaksi-faktur-online.tandai-belum-dicek');

    Route::resource('/transaksi-faktur-outlet', TransaksiFakturOutletController::class)->middleware('auth');
    Route::delete('/transaksi-faktur-outlet/{nomor_faktur}', [TransaksiFakturOutletController::class, 'destroy'])->name('transaksi-faktur-outlet.delete');
    Route::get('/transaksi-rekap-outlet', [TransaksiFakturOutletController::class, 'rekap'])->name('transaksi-faktur-outlet.rekap');
    Route::get('/transaksi-faktur-outlet/{nomor_faktur}', [TransaksiFakturOutletController::class, 'show'])->name('transaksi-faktur-outlet.show');
    Route::get('/transaksi-faktur-outlet/{nomor_faktur}/print', [TransaksiFakturOutletController::class, 'printPdf'])->name('transaksi-faktur-outlet.print');
    Route::put('/transaksi-faktur-outlet/update/{nomor_faktur}', [TransaksiFakturOutletController::class, 'update'])->name('transaksi-faktur-outlet.update');
    Route::post('/transaksi-faktur-outlet/upload-bukti', [TransaksiFakturOutletController::class, 'uploadBukti'])->name('transaksi-faktur-outlet.upload-bukti');
    Route::put('/transaksi-faktur-outlet/{id}/tandai-sudah-dicek', [TransaksiFakturOutletController::class, 'tandaiSudahDicek'])->name('transaksi-faktur-outlet.tandai-sudah-dicek');
    Route::put('/transaksi-faktur-outlet/{id}/tandai-belum-dicek', [TransaksiFakturOutletController::class, 'tandaiBelumDicek'])->name('transaksi-faktur-outlet.tandai-belum-dicek');
    Route::post('/transaksi-faktur-outlet/bukti', [TransaksiFakturOutletController::class, 'storeBukti'])->name('transaksi-faktur-outlet.bukti.store');
    Route::delete('/transaksi-faktur-outlet/bukti/{id}', [TransaksiFakturOutletController::class, 'deleteBukti'])->name('transaksi-faktur-outlet.bukti.delete');

    Route::get('/transaksi-jual-outlet/data', [TransaksiOutletController::class, 'getData'])->name('transaksi-jual-outlet.data');
    Route::resource('/transaksi-jual-outlet', TransaksiOutletController::class)->middleware('auth');
    Route::post('/transaksi-jual-outlet/addbarang', [TransaksiOutletController::class, 'addbarang'])->name('transaksi-jual-outlet.addbarang');
    Route::get('/suggest-no-fak-outlet', [TransaksiOutletController::class, 'getSuggestNoFak'])->name('suggest.no.fak.outlet');
    Route::get('/history-edit-faktur-outlet', [HistoryEditFakturOutletController::class, 'index'])->name('history-edit-faktur-outlet.index')->middleware('auth');
    
    Route::resource('/cek-so', CekSOController::class)->middleware('auth');
    Route::get('/get-last-kode/{gudang_id}', [CekSOController::class, 'getLastKode']);
    Route::get('/get-cek-so/{id}/barangs', [CekSOController::class, 'getCekSOBarangs'])->name('get-cekso.barangs');
    Route::post('/scan-cek-so', [CekSOController::class, 'scan'])->name('cekso.scan');
    Route::post('/upload-cek-so', [CekSOController::class, 'upload'])->name('cekso.upload');
    Route::post('/finish-cek-so', [CekSOController::class, 'finish'])->name('cekso.finish');
    Route::get('/finish-cek-so/{id}', [CekSOController::class, 'showFinish'])->name('cekso.showFinish');
    Route::get('/get-cek-so-finish/{id}', [CekSOController::class, 'getCekSOFinish'])->name('get-cekso.finish');

    Route::get('/negoan/harga-awal', [NegoanController::class, 'getHargaAwal'])->name('negoan.harga-awal');
    Route::post('/negoan/chat', [NegoanController::class, 'storeChat'])->name('negoan.chat.store');
    Route::post('/negoan/upload', [NegoanController::class, 'storeUpload'])->name('negoan.upload');
    Route::resource('/negoan', NegoanController::class)->middleware('auth');

    Route::resource('/notification', NotificationController::class)->middleware('auth');

    Route::get('/data-barang-pendingan', [DataBarangController::class, 'pendingan'])->name('data-barang-pendingan.index');
    Route::post('/data-barang-pendingan', [DataBarangController::class, 'storePendingan'])->name('data-barang-pendingan.store');
    Route::delete('/data-barang-pendingan/{id}', [DataBarangController::class, 'deletePendingan'])->name('data-barang-pendingan.delete');
    Route::resource('/mac-address', MacCheckController::class)->middleware('auth');
    
    Route::get('/tokped-deposit/rekap', [TokpedDepositController::class, 'rekap'])->name('tokped-deposit.rekap');
    Route::get('/tokped-deposit/export', [TokpedDepositController::class, 'export'])->name('tokped-deposit.export');
    Route::resource('/tokped-deposit', TokpedDepositController::class)->middleware('auth');
    
    Route::get('/transaksi-faktur-online/{id}/export', function ($id) {
        return Excel::download(new FakturOnlineExport($id), 'faktur-online-'.$id.'.xlsx');
    })->name('transaksi-faktur-online.export');

    Route::resource('/tokped-order', TokpedOrderController::class)->middleware('auth');

    Route::get('/pulsa-master/export-template', [PulsaMasterController::class, 'exportTemplate'])->name('pulsa-master.exportTemplate');
    Route::resource('/pulsa-master', PulsaMasterController::class)->middleware('auth');
    Route::get('/pulsa-report/export', [PulsaReportController::class, 'exportExcel'])->name('pulsa-report.exportExcel');
    Route::resource('/pulsa-report', PulsaReportController::class)->middleware('auth');

});

Route::middleware(['auth', RoleMiddleware::class . ':adm'])->group(function () {
    Route::get('/todo-transfer/export', [AdmTodoTransferController::class, 'export'])->name('todo-transfer.export');
    Route::get('/todo-transfer', [AdmTodoTransferController::class, 'index'])->name('todo-transfer.index');
    Route::get('/todo-transfer/create', [AdmTodoTransferController::class, 'create'])->name('todo-transfer.create');
    Route::post('/todo-transfer', [AdmTodoTransferController::class, 'store'])->name('todo-transfer.store');
    Route::get('/todo-transfer/{id}', [AdmTodoTransferController::class, 'show'])->name('todo-transfer.show');
    Route::get('/todo-transfer/{id}/edit', [AdmTodoTransferController::class, 'edit'])->name('todo-transfer.edit');
    Route::put('/todo-transfer/{id}', [AdmTodoTransferController::class, 'update'])->name('todo-transfer.update');
    Route::put('/todo-transfer/update-status/{id}/{status}', [AdmTodoTransferController::class, 'updateStatus'])->name('todo-transfer.updateStatus');
    Route::delete('/todo-transfer/{id}', [AdmTodoTransferController::class, 'destroy'])->name('todo-transfer.destroy');
    Route::post('/todo-transfer/bukti', [AdmTodoTransferController::class, 'storeBukti'])->name('todo-transfer.bukti.store');
    Route::delete('/todo-transfer/bukti/{id}', [AdmTodoTransferController::class, 'deleteBukti'])->name('todo-transfer.bukti.delete');
    
    Route::resource('adm-setting', AdmSettingController::class);
    
    Route::get('/req-tokped/export', [AdmReqTokpedController::class, 'export'])->name('req-tokped.export');
    Route::get('/req-tokped/history', [AdmReqTokpedController::class, 'historyBarang'])->name('req-tokped.history');
    Route::get('/req-tokped', [AdmReqTokpedController::class, 'index'])->name('req-tokped.index');
    Route::get('/req-tokped/create', [AdmReqTokpedController::class, 'create'])->name('req-tokped.create');
    Route::post('/req-tokped', [AdmReqTokpedController::class, 'store'])->name('req-tokped.store');
    Route::get('/req-tokped/{id}', [AdmReqTokpedController::class, 'show'])->name('req-tokped.show');
    Route::get('/req-tokped/{id}/edit', [AdmReqTokpedController::class, 'edit'])->name('req-tokped.edit');
    Route::put('/req-tokped/{id}', [AdmReqTokpedController::class, 'update'])->name('req-tokped.update');
    Route::put('/req-tokped/update-status/{id}/{status}', [AdmReqTokpedController::class, 'updateStatus'])->name('req-tokped.updateStatus');
    Route::delete('/req-tokped/{id}', [AdmReqTokpedController::class, 'destroy'])->name('req-tokped.destroy');
    Route::post('/req-tokped/bukti', [AdmReqTokpedController::class, 'storeBukti'])->name('req-tokped.bukti.store');
    Route::delete('/req-tokped/bukti/{id}', [AdmReqTokpedController::class, 'deleteBukti'])->name('req-tokped.bukti.delete');
    Route::post('/req-tokped/item', [AdmReqTokpedController::class, 'storeItem'])->name('req-tokped.item.store');
    Route::delete('/req-tokped/item/{id}', [AdmReqTokpedController::class, 'deleteItem'])->name('req-tokped.item.delete');
    Route::put('/req-tokped/item/{id}', [AdmReqTokpedController::class, 'updateItem'])->name('req-tokped.item.update');
    
    Route::resource('item-tokped', AdmItemTokpedController::class);
});