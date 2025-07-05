<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HistoryEditBarang;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class RiwayatBarangController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = HistoryEditBarang::with('user')->orderBy('created_at', 'desc');

            return DataTables::of($query)
                ->editColumn('created_at', fn($history) => Carbon::parse($history->created_at)->translatedFormat('d F Y H:i:s'))
                ->addColumn('user.name', fn($history) => $history->user->name ?? 'User Tidak Dikenal')
                ->editColumn('update', fn($history) => nl2br(e($history->update)))

                ->addColumn('foto_barang', function ($history) {
                    $containerClass = 'image-action-container';
                    $imageWrapperClass = 'image-wrapper';
                    $buttonWrapperClass = 'button-wrapper';

                    if ($history->foto_barang) {
                        $url = Storage::url($history->foto_barang);
                        return '
                            <div class="' . $containerClass . '">
                                <div class="' . $imageWrapperClass . '">
                                    <a href="' . $url . '" target="_blank">
                                        <img src="' . $url . '" alt="Foto Barang">
                                    </a>
                                </div>
                                <div class="' . $buttonWrapperClass . '">
                                    <button class="btn btn-sm btn-warning btn-edit-foto" data-id="' . $history->id . '" data-tipe="foto_barang">Edit</button>
                                    <button class="btn btn-sm btn-danger btn-hapus-foto" data-id="' . $history->id . '" data-tipe="foto_barang">Hapus</button>
                                </div>
                            </div>
                        ';
                    }
                    return '<div class="' . $containerClass . '"><button class="btn btn-sm btn-primary btn-upload-foto" data-id="' . $history->id . '" data-tipe="foto_barang">Upload</button></div>';
                })
                ->addColumn('foto_imei', function ($history) {
                    $containerClass = 'image-action-container';
                    $imageWrapperClass = 'image-wrapper';
                    $buttonWrapperClass = 'button-wrapper';

                    if ($history->foto_imei) {
                        $url = Storage::url($history->foto_imei);
                        return '
                            <div class="' . $containerClass . '">
                                <div class="' . $imageWrapperClass . '">
                                    <a href="' . $url . '" target="_blank">
                                        <img src="' . $url . '" alt="Foto IMEI">
                                    </a>
                                </div>
                                <div class="' . $buttonWrapperClass . '">
                                    <button class="btn btn-sm btn-warning btn-edit-foto" data-id="' . $history->id . '" data-tipe="foto_imei">Edit</button>
                                    <button class="btn btn-sm btn-danger btn-hapus-foto" data-id="' . $history->id . '" data-tipe="foto_imei">Hapus</button>
                                </div>
                            </div>
                        ';
                    }
                    return '<div class="' . $containerClass . '"><button class="btn btn-sm btn-primary btn-upload-foto" data-id="' . $history->id . '" data-tipe="foto_imei">Upload</button></div>';
                })
                ->addColumn('foto_device_cek', function ($history) {
                    $containerClass = 'image-action-container';
                    $imageWrapperClass = 'image-wrapper';
                    $buttonWrapperClass = 'button-wrapper';
                    
                    if ($history->foto_device_cek) {
                        $url = Storage::url($history->foto_device_cek);
                        return '
                            <div class="' . $containerClass . '">
                                <div class="' . $imageWrapperClass . '">
                                    <a href="' . $url . '" target="_blank">
                                        <img src="' . $url . '" alt="Foto Device Cek">
                                    </a>
                                </div>
                                <div class="' . $buttonWrapperClass . '">
                                    <button class="btn btn-sm btn-warning btn-edit-foto" data-id="' . $history->id . '" data-tipe="foto_device_cek">Edit</button>
                                    <button class="btn btn-sm btn-danger btn-hapus-foto" data-id="' . $history->id . '" data-tipe="foto_device_cek">Hapus</button>
                                </div>
                            </div>
                        ';
                    }
                    return '<div class="' . $containerClass . '"><button class="btn btn-sm btn-primary btn-upload-foto" data-id="' . $history->id . '" data-tipe="foto_device_cek">Upload</button></div>';
                })

                ->rawColumns(['update', 'foto_barang', 'foto_imei', 'foto_device_cek'])
                ->make(true);
        }
        return view('pages.riwayat-barang.index');
    }

    public function uploadFoto(Request $request, HistoryEditBarang $history)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,gif', // Maks 2MB
            'tipe_foto' => 'required|string|in:foto_barang,foto_imei,foto_device_cek',
        ]);

        $tipeFoto = $request->tipe_foto;

        // Hapus foto lama jika ada
        if ($history->$tipeFoto) {
            Storage::disk('public')->delete($history->$tipeFoto);
        }

        // Simpan foto baru dan dapatkan path-nya
        $path = $request->file('foto')->store('history_images', 'public');

        // Update database dengan path foto baru
        $history->$tipeFoto = $path;
        $history->save();

        return response()->json(['success' => 'Foto berhasil di-upload!', 'path' => $path]);
    }

    public function hapusFoto(Request $request, HistoryEditBarang $history)
    {
        $request->validate([
            'tipe_foto' => 'required|string|in:foto_barang,foto_imei,foto_device_cek',
        ]);
        
        $tipeFoto = $request->tipe_foto;

        // Hapus file dari storage
        if ($history->$tipeFoto) {
            Storage::disk('public')->delete($history->$tipeFoto);
        }

        // Hapus path dari database
        $history->$tipeFoto = null;
        $history->save();

        return response()->json(['success' => 'Foto berhasil dihapus!']);
    }
}