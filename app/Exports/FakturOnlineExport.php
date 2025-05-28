<?php

namespace App\Exports;

use App\Models\FakturOnline;
use App\Models\ReturnBarang;
use App\Models\TokpedDataDeposit;
use App\Models\TransaksiJualOnline;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Carbon\Carbon;
use Illuminate\Support\Str;

class FakturOnlineExport implements FromView, WithEvents
{
    protected $nomor_faktur;

    public function __construct($nomor_faktur)
    {
        $this->nomor_faktur = $nomor_faktur;
    }

    public function view(): View
    {
        $faktur = FakturOnline::with('barangs')->where('id', $this->nomor_faktur)->firstOrFail();
        
        $transaksiJualsOriginal = TransaksiJualOnline::with('barang')
            ->where('faktur_online_id', $this->nomor_faktur)
            // ->orderBy('invoice') // Pengurutan awal bisa dipertahankan atau dihilangkan
            ->get();
    
        // 1. Buat daftar invoice unik (7 digit terakhir yang sudah dibersihkan) dari transaksiJuals
        $uniqueCleanedInvoices = $transaksiJualsOriginal->pluck('invoice')
            ->filter() // Hapus nomor invoice yang null atau kosong
            ->map(function ($invoice) {
                $clean = preg_replace('/\D/', '', trim($invoice));
                return Str::substr($clean, -7);
            })
            ->unique()
            ->toArray();
    
        // 2. Ambil uangMasukPerInvoice, cocokkan dengan invoice 7 digit yang sudah dibersihkan
        //    dan kunci hasilnya dengan invoice_end 7 digit yang sudah dibersihkan.
        $uangMasukPerInvoice = TokpedDataDeposit::where(function ($query) use ($uniqueCleanedInvoices) {
                foreach ($uniqueCleanedInvoices as $inv) {
                    // $inv sudah merupakan string 7 digit yang bersih
                    $query->orWhereRaw("RIGHT(REGEXP_REPLACE(TRIM(invoice_end), '[^0-9]', ''), 7) = ?", [$inv]);
                }
            })
            ->selectRaw('invoice_end, SUM(nominal) as total_uang_masuk, MIN(date) as tanggal_masuk')
            ->groupBy('invoice_end') // Group by invoice_end asli sebelum di-re-key
            ->get()
            ->mapWithKeys(function ($item) {
                $cleanInvoiceEnd = preg_replace('/\D/', '', trim($item->invoice_end));
                $last7DigitsKey = Str::substr($cleanInvoiceEnd, -7);
                return [$last7DigitsKey => $item]; // Kunci dengan invoice_end 7 digit bersih
            });
    
        // 3. Urutkan transaksiJuals berdasarkan tanggal_masuk dari uangMasukPerInvoice yang sudah dinormalisasi
        $transaksiJuals = $transaksiJualsOriginal->sortBy(function ($item) use ($uangMasukPerInvoice) {
            $cleanItemInvoice = preg_replace('/\D/', '', trim($item->invoice));
            $lookupKey = Str::substr($cleanItemInvoice, -7); // Kunci lookup 7 digit bersih
            
            $tanggal = optional($uangMasukPerInvoice[$lookupKey] ?? null)->tanggal_masuk ?? now()->addYears(100);
            return $tanggal;
        })->values(); // reset index
    
        // 4. Map transaksiJuals untuk menambahkan tgl_return
        $transaksiJuals = $transaksiJuals->map(function ($trx) {
            $returnBarang = ReturnBarang::where('lok_spk', $trx->lok_spk)
                ->with('returnModel') 
                ->orderByDesc('id')
                ->first();
    
            if ($returnBarang && $returnBarang->returnModel && $returnBarang->returnModel->tgl_return > $trx->t_jual) {
                $trx->tgl_return = $returnBarang->returnModel->tgl_return;
            } else {
                $trx->tgl_return = null;
            }
            return $trx;
        });
    
        // Kirim uangMasukPerInvoice yang sudah dinormalisasi ke view
        return view('exports.faktur-online', compact('faktur', 'transaksiJuals', 'uangMasukPerInvoice'));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate(); // Gunakan getDelegate() untuk akses PhpSpreadsheet
    
                // ... (Definisi style yang sudah ada untuk A1:B7, A10:J10, AutoSize) ...
                // Style untuk info section (A1:B7)
                $sheet->getStyle('A1:B7')->getFont()->setBold(true);
                $sheet->getStyle('A1:A7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFCE4D6');
                $sheet->getStyle('A1:B7')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                if ($sheet->getCell('B6')->getValue()) { // Cek jika B6 ada nilainya
                     $sheet->getStyle('B6')->getNumberFormat()->setFormatCode('"Rp." #,##0');
                }
    
    
                // Header row (A10:J10)
                $sheet->getStyle('A10:J10')->getFont()->setBold(true);
                $sheet->getStyle('A10:J10')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9E1F2');
                $sheet->getStyle('A10:J10')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
                // Auto size columns A sampai J
                foreach (range('A', 'J') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
    
                // --- Ambil ulang dan proses data dengan normalisasi ---
                $transaksiJualsOriginal = TransaksiJualOnline::with('barang')
                    ->where('faktur_online_id', $this->nomor_faktur)
                    ->get();
    
                $uniqueCleanedInvoices = $transaksiJualsOriginal->pluck('invoice')
                    ->filter()
                    ->map(function ($invoice) {
                        return Str::substr(preg_replace('/\D/', '', trim($invoice)), -7);
                    })
                    ->unique()
                    ->toArray();
    
                $uangMasukPerInvoice = TokpedDataDeposit::where(function ($query) use ($uniqueCleanedInvoices) {
                        foreach ($uniqueCleanedInvoices as $inv) {
                            $query->orWhereRaw("RIGHT(REGEXP_REPLACE(TRIM(invoice_end), '[^0-9]', ''), 7) = ?", [$inv]);
                        }
                    })
                    ->selectRaw('invoice_end, SUM(nominal) as total_uang_masuk, MIN(date) as tanggal_masuk')
                    ->groupBy('invoice_end')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        $cleanInvoiceEnd = preg_replace('/\D/', '', trim($item->invoice_end));
                        $last7DigitsKey = Str::substr($cleanInvoiceEnd, -7);
                        return [$last7DigitsKey => $item];
                    });
                
                $transaksiJuals = $transaksiJualsOriginal->sortBy(function ($item) use ($uangMasukPerInvoice) {
                    $cleanItemInvoice = preg_replace('/\D/', '', trim($item->invoice));
                    $lookupKey = Str::substr($cleanItemInvoice, -7);
                    $tanggal = optional($uangMasukPerInvoice[$lookupKey] ?? null)->tanggal_masuk ?? now()->addYears(100);
                    return $tanggal;
                })->values();
    
                $transaksiJuals = $transaksiJuals->map(function ($trx) {
                    $returnBarang = ReturnBarang::where('lok_spk', $trx->lok_spk)
                        ->with('returnModel')
                        ->orderByDesc('id')
                        ->first();
                    if ($returnBarang && $returnBarang->returnModel && $returnBarang->returnModel->tgl_return > $trx->t_jual) {
                        $trx->tgl_return = $returnBarang->returnModel->tgl_return;
                    } else {
                        $trx->tgl_return = null;
                    }
                    return $trx;
                });
                // --- Selesai ambil ulang data ---
    
                $rowIndex = 11; // Data dimulai dari baris 11
                $no = 1;
                $startDataRow = $rowIndex; // Simpan baris awal data untuk formula SUM
    
                foreach ($transaksiJuals->groupBy('invoice') as $invoice => $items) { // $invoice di sini asli
                    $rowCount = count($items);
                    if ($rowCount == 0) continue; // Lewati jika tidak ada item
    
                    $mergeStart = $rowIndex;
                    $mergeEnd = $rowIndex + $rowCount - 1;
    
                    // Normalisasi $invoice untuk lookup
                    $cleanedInvoiceKey = Str::substr(preg_replace('/\D/', '', trim($invoice)), -7);
                    $uangMasukData = $uangMasukPerInvoice[$cleanedInvoiceKey] ?? null;
                    
                    $totalUangMasukVal = optional($uangMasukData)->total_uang_masuk ?? 0;
                    $tanggalMasukFormatted = ($uangMasukData && optional($uangMasukData)->tanggal_masuk) ? Carbon::parse($uangMasukData->tanggal_masuk)->translatedFormat('d F Y') : 'Belum Ada Info';
    
                    // Merge dan isi data untuk Invoice (B), Uang Masuk (H), Tanggal Masuk (I)
                    $sheet->mergeCells("B{$mergeStart}:B{$mergeEnd}");
                    $sheet->setCellValueExplicit("B{$mergeStart}", $invoice, DataType::TYPE_STRING); // Invoice asli
    
                    $sheet->mergeCells("H{$mergeStart}:H{$mergeEnd}");
                    $sheet->setCellValue("H{$mergeStart}", $totalUangMasukVal);
                    
                    $sheet->mergeCells("I{$mergeStart}:I{$mergeEnd}");
                    $sheet->setCellValue("I{$mergeStart}", $tanggalMasukFormatted);
    
                    if (!$uangMasukData || $totalUangMasukVal == 0) {
                        $sheet->getStyle("H{$mergeStart}")->getFont()->getColor()->setARGB('FFFF0000'); // Merah
                        $sheet->getStyle("I{$mergeStart}")->getFont()->getColor()->setARGB('FFFF0000'); // Merah
                    }
                    
                    foreach (['B', 'H', 'I'] as $col) {
                         $sheet->getStyle("{$col}{$mergeStart}")->getAlignment()->setVertical('center')->setHorizontal('center');
                    }
    
                    foreach ($items as $itemIndex => $item) {
                        $currentRow = $mergeStart + $itemIndex; // Baris saat ini untuk item
                        $sheet->setCellValue("A{$currentRow}", $no++);
                        // Kolom B sudah di-merge dan diisi
                        $sheet->setCellValue("C{$currentRow}", $item->lok_spk);
                        $sheet->setCellValue("D{$currentRow}", $item->barang->tipe ?? '-');
                        $sheet->setCellValue("E{$currentRow}", $item->harga);
                        $sheet->setCellValue("F{$currentRow}", $item->pj);
    
                        if ($item->pj == 0) {
                            $sheet->setCellValue("G{$currentRow}", '-');
                        } else {
                            $selisih = $item->harga - $item->pj;
                            $sheet->setCellValue("G{$currentRow}", $selisih);
                            $color = $selisih < 0 ? 'FFFF0000' : 'FF008000'; // Merah : Hijau (ARGB)
                            $sheet->getStyle("G{$currentRow}")->getFont()->getColor()->setARGB($color);
                        }
                        
                        $tglReturnFormatted = $item->tgl_return ? Carbon::parse($item->tgl_return)->translatedFormat('d F Y') : '-';
                        $sheet->setCellValue("J{$currentRow}", $tglReturnFormatted); // Kolom Tgl Return
                    }
                    $rowIndex += $rowCount; // Maju sebanyak item yang diproses
                }
    
                $lastDataRow = $rowIndex - 1;
    
                // Border untuk data baris (A sampai J)
                if ($lastDataRow >= $startDataRow) {
                     $sheet->getStyle("A{$startDataRow}:J{$lastDataRow}")
                        ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
                    // Format angka: kolom E, F, G, H (Harga, PJ, Selisih, Uang Masuk)
                    foreach (['E', 'F', 'G', 'H'] as $col) {
                        $sheet->getStyle("{$col}{$startDataRow}:{$col}{$lastDataRow}")
                            ->getNumberFormat()->setFormatCode('"Rp." #,##0');
                    }
                }
    
    
                // Tambah baris total
                $totalRowIndex = $rowIndex; // Baris setelah data terakhir
                $sheet->mergeCells("A{$totalRowIndex}:D{$totalRowIndex}");
                $sheet->setCellValue("A{$totalRowIndex}", 'TOTAL');
                $sheet->getStyle("A{$totalRowIndex}:D{$totalRowIndex}")->getFont()->setBold(true);
                $sheet->getStyle("A{$totalRowIndex}:D{$totalRowIndex}")->getAlignment()->setHorizontal('center');
    
                // Formula jumlah di kolom E, F, G, H
                if ($lastDataRow >= $startDataRow) { // Hanya jika ada data untuk di-sum
                    $sheet->setCellValue("E{$totalRowIndex}", "=SUM(E{$startDataRow}:E{$lastDataRow})");
                    $sheet->setCellValue("F{$totalRowIndex}", "=SUM(F{$startDataRow}:F{$lastDataRow})");
                    $sheet->setCellValue("G{$totalRowIndex}", "=SUM(G{$startDataRow}:G{$lastDataRow})");
                    $sheet->setCellValue("H{$totalRowIndex}", "=SUM(H{$startDataRow}:H{$lastDataRow})");
                } else { // Jika tidak ada data, set total ke 0 atau string kosong
                     $sheet->setCellValue("E{$totalRowIndex}", 0);
                     $sheet->setCellValue("F{$totalRowIndex}", 0);
                     $sheet->setCellValue("G{$totalRowIndex}", 0);
                     $sheet->setCellValue("H{$totalRowIndex}", 0);
                }
    
    
                foreach (['E', 'F', 'G', 'H'] as $col) {
                    $sheet->getStyle("{$col}{$totalRowIndex}")->getFont()->setBold(true);
                    $sheet->getStyle("{$col}{$totalRowIndex}")->getNumberFormat()->setFormatCode('"Rp." #,##0');
                }
    
                $sheet->getStyle("A{$totalRowIndex}:J{$totalRowIndex}") // Style total hingga kolom J
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFEDEDED'); // Abu-abu terang
                $sheet->getStyle("A{$totalRowIndex}:J{$totalRowIndex}")
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            }
        ];
    }
}
