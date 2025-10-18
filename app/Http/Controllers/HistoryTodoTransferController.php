<?php

namespace App\Http\Controllers;

use App\Exports\HistoryTodoTransferTemplateExport;
use App\Models\HistoryTodoTransfer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class HistoryTodoTransferController extends Controller
{
    public function index(Request $request)
    {
        // Dropdown kode_toko & nama_bank unik
        $kodeTokos = HistoryTodoTransfer::select('kode_toko')->distinct()->orderBy('kode_toko')->pluck('kode_toko');
        $namaBanks = HistoryTodoTransfer::select('nama_bank')->whereNotNull('nama_bank')->distinct()->orderBy('nama_bank')->pluck('nama_bank');

        $q = HistoryTodoTransfer::query();

        // Filters
        if ($request->filled('start_date')) $q->where('tgl_transfer', '>=', $request->start_date);
        if ($request->filled('end_date'))   $q->where('tgl_transfer', '<=', $request->end_date);
        if ($request->filled('kode_toko'))  $q->where('kode_toko', $request->kode_toko);
        if ($request->filled('nama_bank'))  $q->where('nama_bank', $request->nama_bank);
        if ($request->filled('min_nominal')) $q->where('nominal', '>=', (float)$request->min_nominal);
        if ($request->filled('max_nominal')) $q->where('nominal', '<=', (float)$request->max_nominal);
        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $q->where(function($x) use ($s) {
                $x->where('nama_toko','like',$s)
                  ->orWhere('nama_am','like',$s)
                  ->orWhere('keterangan','like',$s);
            });
        }

        $items = $q->orderBy('tgl_transfer', 'desc')->orderBy('id','desc')->get();

        return view('pages.history-todo-transfer.index', [
            'items'      => $items,
            'kodeTokos'  => $kodeTokos,
            'namaBanks'  => $namaBanks,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRow($request->all(), isBatch:false);

        // Cek unik gabungan di DB
        $exists = HistoryTodoTransfer::where('tgl_transfer', $data['tgl_transfer'])
          ->where('kode_toko', $data['kode_toko'])
          ->where('norek_bank', $data['norek_bank'])
          ->where('nominal', $data['nominal'])
          ->exists();

        if ($exists) {
            return back()->withInput()->withErrors(['form' => 'Data duplikat (tgl_transfer, kode_toko, norek_bank, nominal) sudah ada.']);
        }

        HistoryTodoTransfer::create($data);

        return redirect()->route('history-todo-transfer.index')->with('success', 'Data berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $item  = HistoryTodoTransfer::findOrFail($id);
        $data  = $this->validateRow($request->all(), isBatch:false);

        // Cek unik gabungan exclude current
        $exists = HistoryTodoTransfer::where('tgl_transfer', $data['tgl_transfer'])
          ->where('kode_toko', $data['kode_toko'])
          ->where('norek_bank', $data['norek_bank'])
          ->where('nominal', $data['nominal'])
          ->where('id','<>',$item->id)
          ->exists();

        if ($exists) {
            return back()->withInput()->withErrors(['form' => 'Update gagal: kombinasi (tgl_transfer, kode_toko, norek_bank, nominal) sudah ada.']);
        }

        $item->update($data);
        return redirect()->route('history-todo-transfer.index')->with('success', 'Data berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $item = HistoryTodoTransfer::findOrFail($id);
        $item->delete();
        return response()->json(['status'=>'success','message'=>'Data terhapus']);
    }

    public function exportTemplate()
    {
        $fileName = 'template-history-todo-transfer-'.now()->format('Ymd').'.xlsx';
        return Excel::download(new HistoryTodoTransferTemplateExport, $fileName);
    }

    /**
     * Batch Paste:
     * - Input: textarea berisi data TABEL (copy dari Excel/Spreadsheet).
     * - Mendukung delimiter TAB (\t) atau koma (,).
     * - Baris pertama = header. Wajib sesuai header template.
     * - Validasi:
     *   - tgl_transfer <= today
     *   - kode_toko & norek_bank hanya angka
     *   - tidak boleh ada duplikat (di file maupun vs DB) berdasarkan 4 kolom gabungan
     *   - Jika ada error, GAGAL TOTAL (rollback).
     */
    public function batchPaste(Request $request)
    {
        $request->validate([
            'pasted_table' => 'required|string'
        ]);

        $raw = trim($request->pasted_table);
        $lines = preg_split('/\r\n|\r|\n/', $raw);
        if (count($lines) < 2) {
            return back()->with('error', 'Format paste tidak valid. Minimal harus ada header + 1 baris data.');
        }

        // Parse header
        $header = $this->splitRow($lines[0]);
        $expected = ['tgl_transfer','kode_toko','nama_toko','nama_am','keterangan','nama_bank','norek_bank','nama_norek','nominal'];

        // Normalisasi header (lowercase)
        $header = array_map(fn($h)=>strtolower(trim($h)), $header);
        if ($header !== $expected) {
            return back()->with('error',
                'Header tidak sesuai template. Harus persis: ' . implode(', ', $expected)
            );
        }

        $rows = [];
        $errors = [];
        $seenKeys = []; // deteksi duplikat di batch: key = tgl|kode|norek|nominal

        // Kumpulkan kombinasi untuk cek DB massal
        $combosForDb = [];

        // Parse rows
        for ($i=1; $i<count($lines); $i++) {
            if (trim($lines[$i])==='') continue;
            $cols = $this->splitRow($lines[$i]);

            // Safe pad
            for ($c=count($cols); $c<count($expected); $c++) $cols[] = null;

            $row = array_combine($expected, $cols);

            // Trim string
            foreach ($row as $k=>$v) {
                if (is_string($v)) $row[$k] = trim($v);
            }

            // Casting nominal
            $row['nominal'] = is_null($row['nominal']) || $row['nominal']==='' ? null : (float)str_replace([','], [''], $row['nominal']);

            // Validasi baris
            $validator = Validator::make($row, [
                'tgl_transfer' => ['required','date','before_or_equal:today'],
                'kode_toko'    => ['required','regex:/^[0-9]+$/'],
                'nama_toko'    => ['nullable','max:255'],
                'nama_am'      => ['nullable','max:255'],
                'keterangan'   => ['nullable'],
                'nama_bank'    => ['nullable','max:255'],
                'norek_bank'   => ['required','regex:/^[0-9]+$/'],
                'nama_norek'   => ['nullable','max:255'],
                'nominal'      => ['required','numeric','min:0'],
            ],[
                'kode_toko.regex'  => 'kode_toko hanya boleh angka.',
                'norek_bank.regex' => 'norek_bank hanya boleh angka.',
                'tgl_transfer.before_or_equal' => 'tgl_transfer tidak boleh lebih dari hari ini.',
            ]);

            if ($validator->fails()) {
                $errors[] = "Baris #".($i+1).": ".implode(' | ', $validator->errors()->all());
                continue;
            }

            // Duplikat dalam batch
            $key = $row['tgl_transfer'].'|'.$row['kode_toko'].'|'.$row['norek_bank'].'|'.$row['nominal'];
            if (isset($seenKeys[$key])) {
                $errors[] = "Baris #".($i+1).": Duplikat di dalam batch (tgl_transfer, kode_toko, norek_bank, nominal).";
                continue;
            }
            $seenKeys[$key] = true;

            $rows[] = $row;
            $combosForDb[] = $key;
        }

        if (!empty($errors)) {
            return back()->with('error', 'Upload Gagal:<br>'.implode('<br>', $errors));
        }

        if (empty($rows)) {
            return back()->with('error', 'Tidak ada baris data yang valid untuk diproses.');
        }

        // Cek duplikat vs DB (massal)
        // Pisahkan key jadi kolom
        $existsErrors = [];

        // Potong cek dalam chunk agar aman
        $chunks = array_chunk($rows, 300);
        foreach ($chunks as $chunk) {
            $query = HistoryTodoTransfer::query();
            foreach ($chunk as $r) {
                $query->orWhere(function($q) use ($r){
                    $q->where('tgl_transfer', $r['tgl_transfer'])
                      ->where('kode_toko',  $r['kode_toko'])
                      ->where('norek_bank', $r['norek_bank'])
                      ->where('nominal',    $r['nominal']);
                });
            }
            $dups = $query->get(['tgl_transfer','kode_toko','norek_bank','nominal']);
            foreach ($dups as $d) {
                $existsErrors[] = "Sudah ada di DB: {$d->tgl_transfer} | {$d->kode_toko} | {$d->norek_bank} | {$d->nominal}";
            }
        }

        if (!empty($existsErrors)) {
            return back()->with('error', 'Upload Gagal karena duplikat vs DB:<br>'.implode('<br>', $existsErrors));
        }

        // Insert transaksi (all-or-nothing)
        DB::transaction(function() use ($rows) {
            $now = now();
            $insert = [];
            foreach ($rows as $r) {
                $insert[] = [
                    'tgl_transfer' => Carbon::parse($r['tgl_transfer'])->format('Y-m-d'),
                    'kode_toko'    => $r['kode_toko'],
                    'nama_toko'    => $r['nama_toko'] ?: null,
                    'nama_am'      => $r['nama_am'] ?: null,
                    'keterangan'   => $r['keterangan'] ?: null,
                    'nama_bank'    => $r['nama_bank'] ?: null,
                    'norek_bank'   => $r['norek_bank'],
                    'nama_norek'   => $r['nama_norek'] ?: null,
                    'nominal'      => $r['nominal'],
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
            HistoryTodoTransfer::insert($insert);
        });

        return redirect()->route('history-todo-transfer.index')->with('success', 'Batch data berhasil diunggah.');
    }

    /** Helpers */
    private function splitRow(string $line): array
    {
        // Deteksi delimiter: TAB lebih prioritas, lalu koma
        if (str_contains($line, "\t")) return explode("\t", $line);
        // CSV sederhana (tanpa quote kompleks); cukup untuk template kita
        return array_map('trim', explode(',', $line));
    }

    private function validateRow(array $input, bool $isBatch = false): array
    {
        $rules = [
            'tgl_transfer' => ['required','date','before_or_equal:today'],
            'kode_toko'    => ['required','regex:/^[0-9]+$/'],
            'nama_toko'    => ['nullable','max:255'],
            'nama_am'      => ['nullable','max:255'],
            'keterangan'   => ['nullable'],
            'nama_bank'    => ['nullable','max:255'],
            'norek_bank'   => ['required','regex:/^[0-9]+$/'],
            'nama_norek'   => ['nullable','max:255'],
            'nominal'      => ['required','numeric','min:0'],
        ];
        $msg = [
            'kode_toko.regex'  => 'kode_toko hanya boleh angka.',
            'norek_bank.regex' => 'norek_bank hanya boleh angka.',
            'tgl_transfer.before_or_equal' => 'tgl_transfer tidak boleh lebih dari hari ini.',
        ];

        $data = validator($input, $rules, $msg)->validate();
        // Normalisasi tanggal (Y-m-d)
        $data['tgl_transfer'] = Carbon::parse($data['tgl_transfer'])->format('Y-m-d');
        return $data;
    }
}
