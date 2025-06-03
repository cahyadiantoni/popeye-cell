<?php

namespace App\Http\Controllers;

use App\Models\PulsaMaster; // Model yang baru dibuat
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; 
use App\Exports\PulsaMasterTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class PulsaMasterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = PulsaMaster::latest()->get();
            return DataTables::of($data)
                // Tambahkan addColumn atau editColumn jika perlu format khusus
                ->addColumn('action', function($row){
                    // Contoh tombol aksi, bisa disesuaikan
                    // $btn = '<a href="javascript:void(0)" class="edit btn btn-primary btn-sm">Edit</a>';
                    // $btn = $btn.' <a href="javascript:void(0)" class="delete btn btn-danger btn-sm">Delete</a>';
                    // return $btn;
                    return '-'; // Placeholder, sesuaikan jika ada aksi
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('pages.pulsa-master.index');
    }

    /**
     * Store a newly created resource in storage.
     * Handles Excel upload for creating or updating PulsaMaster records.
     */
    public function store(Request $request)
    {
        $request->validate([
            'filedata' => 'required|mimes:xlsx,xls,csv|max:5120', // max 5MB
        ]);

        DB::beginTransaction();
        $insertedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        try {
            $path = $request->file('filedata')->getRealPath();
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();

            // Asumsi header ada di baris pertama, data mulai dari baris kedua
            // Kolom A -> kode, B -> nama_toko, ..., K -> pulsa3
            // Indeks array: 0 -> kode, 1 -> nama_toko, ..., 10 -> pulsa3

            $header = [];
            for ($col = 'A'; $col <= 'K'; $col++) { // K adalah kolom ke-11
                $header[] = strtolower(str_replace(' ', '_', $sheet->getCell($col . '1')->getValue()));
            }
            // Ekspektasi header: kode, nama_toko, pasca_bayar1, ..., pulsa3

            for ($rowIndex = 2; $rowIndex <= $highestRow; $rowIndex++) {
                $rowData = [];
                $cellIterator = $sheet->getRowIterator($rowIndex, $rowIndex)->current()->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false); // Iterate all cells, even if empty

                $currentColIndex = 0;
                foreach ($cellIterator as $cell) {
                    if ($currentColIndex < count($header)) {
                         // Ambil nilai sel, bisa null jika kosong
                        $cellValue = $cell->getValue();
                        // Untuk field 'kode', pastikan ada nilainya dan tidak hanya spasi
                        if ($header[$currentColIndex] === 'kode') {
                            $rowData[$header[$currentColIndex]] = is_string($cellValue) ? trim($cellValue) : $cellValue;
                        } else {
                            $rowData[$header[$currentColIndex]] = $cellValue;
                        }
                    }
                    $currentColIndex++;
                    if ($currentColIndex >= count($header)) break; // Hanya proses 11 kolom header
                }


                $kode = $rowData['kode'] ?? null;

                if (empty($kode)) {
                    $skippedCount++;
                    continue; // Lewati baris jika kode kosong
                }

                $master = PulsaMaster::find($kode);
                $dataToProcess = collect($rowData)->except(['kode'])->filter(function ($value) {
                    // Hanya proses field jika nilainya tidak null di Excel
                    // Untuk string kosong, kita anggap sebagai "tidak ada data baru" untuk update.
                    // Jika ingin string kosong mengosongkan field DB, ubah logika ini.
                    return !is_null($value);
                })->all();


                if ($master) { // Update
                    $updatePayload = [];
                    foreach ($dataToProcess as $field => $value) {
                        // Hanya update jika value dari Excel tidak null (ada isinya)
                        // Jika value adalah string kosong, dan Anda ingin mengosongkan field DB,
                        // maka jangan filter string kosong di atas.
                        // Jika value adalah string kosong, dan Anda ingin field DB tetap,
                        // maka filter string kosong di $dataToProcess seperti: $value !== ''
                        if (array_key_exists($field, $master->getFillable()) || in_array($field, $master->getFillable())) {
                             // Pastikan field ada di fillable model
                             if ($value !== '' && !is_null($value)) { // Hanya update jika Excel punya data (bukan string kosong)
                                $master->{$field} = $value;
                             } elseif (is_null($value) || $value === '') {
                                // Jika ingin mengosongkan field DB dengan data kosong dari Excel:
                                // $master->{$field} = null; // atau sesuai tipe data field
                             }
                        }
                    }
                    if($master->isDirty()){ // Cek apakah ada perubahan
                        $master->save();
                        $updatedCount++;
                    } else {
                        // Tidak ada perubahan yang valid dari Excel
                        // $skippedCount++; // Atau hitung sebagai tidak ada perubahan
                    }
                } else { // Create
                    // Untuk create, pastikan semua field yang dibutuhkan ada, atau fillable menangani default
                    $createPayload = ['kode' => $kode];
                     foreach ($dataToProcess as $field => $value) {
                        if (in_array($field, (new PulsaMaster())->getFillable())) {
                            $createPayload[$field] = ($value === '' || is_null($value)) ? null : $value; // Set null jika kosong
                        }
                    }
                    PulsaMaster::create($createPayload);
                    $insertedCount++;
                }
            }

            DB::commit();
            return redirect()->back()->with('success', "Upload berhasil. Data baru: $insertedCount, diperbarui: $updatedCount, dilewati/tidak berubah: $skippedCount.");

        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            DB::rollBack();
            Log::error('Kesalahan Spreadsheet: ' . $e->getMessage() . ' Stack: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Kesalahan terkait file Excel: ' . $e->getMessage());
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('Kesalahan Database: ' . $e->getMessage() . ' Stack: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Kesalahan database saat menyimpan data.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Kesalahan Umum: ' . $e->getMessage() . ' Stack: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Upload gagal: ' . $e->getMessage() . (isset($rowIndex) ? ' (Baris proses terakhir: ' . $rowIndex . ')' : ''));
        }
    }

    public function exportTemplate()
    {
        return Excel::download(new PulsaMasterTemplateExport, 'template_pulsa_master_terisi.xlsx');
    }
}