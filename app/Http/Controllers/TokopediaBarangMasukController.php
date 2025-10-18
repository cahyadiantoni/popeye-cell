<?php

// app/Http/Controllers/TokopediaBarangMasukController.php
namespace App\Http\Controllers;

use App\Exports\TokopediaBarangMasukTemplateExport;
use App\Models\TokopediaBarangMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class TokopediaBarangMasukController extends Controller
{
    public function index(Request $request)
    {
        // Filter options
        $namaBarangOpts = TokopediaBarangMasuk::select('nama_barang')->distinct()->orderBy('nama_barang')->pluck('nama_barang');

        $q = TokopediaBarangMasuk::query();

        if ($request->filled('start_date')) $q->where('tgl_beli', '>=', $request->start_date);
        if ($request->filled('end_date'))   $q->where('tgl_beli', '<=', $request->end_date);
        if ($request->filled('nama_barang')) $q->where('nama_barang', $request->nama_barang);
        if ($request->filled('min_total'))   $q->where('total_harga', '>=', (float)$request->min_total);
        if ($request->filled('max_total'))   $q->where('total_harga', '<=', (float)$request->max_total);
        if ($request->filled('search')) {
            $s = '%'.trim($request->search).'%';
            $q->where('nama_barang','like',$s);
        }

        $items = $q->orderBy('tgl_beli','desc')->orderBy('id','desc')->get();

        return view('pages.tokopedia-barang-masuk.index', [
            'items' => $items,
            'namaBarangOpts' => $namaBarangOpts,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRow($request->all());

        // Hitung total_harga
        $data['total_harga'] = $this->calcTotal($data['quantity'], $data['harga_satuan'], $data['harga_ongkir'] ?? 0, $data['harga_potongan'] ?? 0);

        // Cek duplikat
        $exists = TokopediaBarangMasuk::where('tgl_beli', $data['tgl_beli'])
            ->where('nama_barang', $data['nama_barang'])
            ->where('quantity', $data['quantity'])
            ->where('total_harga', $data['total_harga'])
            ->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['form' => 'Data duplikat (tgl_beli, nama_barang, quantity, total_harga) sudah ada.']);
        }

        TokopediaBarangMasuk::create($data);
        return redirect()->route('tokopedia-barang-masuk.index')->with('success','Data berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $item = TokopediaBarangMasuk::findOrFail($id);
        $data = $this->validateRow($request->all());

        $data['total_harga'] = $this->calcTotal($data['quantity'], $data['harga_satuan'], $data['harga_ongkir'] ?? 0, $data['harga_potongan'] ?? 0);

        $exists = TokopediaBarangMasuk::where('tgl_beli', $data['tgl_beli'])
            ->where('nama_barang', $data['nama_barang'])
            ->where('quantity', $data['quantity'])
            ->where('total_harga', $data['total_harga'])
            ->where('id','<>',$item->id)
            ->exists();
        if ($exists) {
            return back()->withInput()->withErrors(['form' => 'Update gagal: kombinasi unik sudah ada.']);
        }

        $item->update($data);
        return redirect()->route('tokopedia-barang-masuk.index')->with('success','Data berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $item = TokopediaBarangMasuk::findOrFail($id);
        $item->delete();
        return response()->json(['status'=>'success','message'=>'Data terhapus']);
    }

    public function exportTemplate()
    {
        $fileName = 'template-tokopedia-barang-masuk-'.now()->format('Ymd').'.xlsx';
        return Excel::download(new TokopediaBarangMasukTemplateExport, $fileName);
    }

    /**
     * Batch Paste (copy dari Excel/Spreadsheet)
     * Header wajib: tgl_beli,nama_barang,quantity,harga_satuan,harga_ongkir,harga_potongan
     * Jika ada 1 error -> batal semua.
     */
    public function batchPaste(Request $request)
    {
        $request->validate(['pasted_table' => 'required|string']);
        $raw = trim($request->pasted_table);
        $lines = preg_split('/\r\n|\r|\n/',$raw);
        if (count($lines) < 2) {
            return back()->with('error','Format paste tidak valid (butuh header + data).');
        }

        $expected = ['tgl_beli','nama_barang','quantity','harga_satuan','harga_ongkir','harga_potongan'];
        $header = $this->normalizeHeader($this->splitRow($lines[0]));
        if ($header !== $expected) {
            return back()->with('error', 'Header harus persis: '.implode(',', $expected));
        }

        $rows = [];
        $errors = [];
        $seen = []; // key: tgl|nama|qty|total

        for ($i=1; $i<count($lines); $i++) {
            if (trim($lines[$i])==='') continue;
            $cols = $this->splitRow($lines[$i]);
            for ($c=count($cols); $c<count($expected); $c++) $cols[] = null;
            $row = array_combine($expected, array_map(fn($v)=>is_string($v)?trim($v):$v, $cols));

            // Pre-normalisasi nama_barang (UPPERCASE & trim)
            $row['nama_barang'] = strtoupper(trim((string)($row['nama_barang'] ?? '')));

            // Casting angka
            $row['quantity'] = (int)($row['quantity'] ?? 0);
            $row['harga_satuan']   = (float)str_replace([','],[''], $row['harga_satuan'] ?? 0);
            $row['harga_ongkir']   = (float)str_replace([','],[''], $row['harga_ongkir'] ?? 0);
            $row['harga_potongan'] = (float)str_replace([','],[''], $row['harga_potongan'] ?? 0);

            // Validasi baris
            $validator = Validator::make($row, [
                'tgl_beli'        => ['required','date','before_or_equal:today'],
                'nama_barang'     => ['required','max:255','regex:/^(?!\s)(.*\S)?$/'], // no leading/trailing space
                'quantity'        => ['required','integer','min:1'],
                'harga_satuan'    => ['required','numeric','min:0'],
                'harga_ongkir'    => ['nullable','numeric','min:0'],
                'harga_potongan'  => ['nullable','numeric','min:0'],
            ],[
                'nama_barang.regex' => 'nama_barang tidak boleh diawali/diakhiri spasi.',
                'tgl_beli.before_or_equal' => 'tgl_beli tidak boleh lebih dari hari ini.',
            ]);

            if ($validator->fails()) {
                $errors[] = "Baris #".($i+1).": ".implode(' | ', $validator->errors()->all());
                continue;
            }

            $total = $this->calcTotal($row['quantity'], $row['harga_satuan'], $row['harga_ongkir'] ?? 0, $row['harga_potongan'] ?? 0);

            // Duplikat dalam batch
            $key = ($row['tgl_beli']??'').'|'.$row['nama_barang'].'|'.$row['quantity'].'|'.$total;
            if (isset($seen[$key])) {
                $errors[] = "Baris #".($i+1).": Duplikat di batch (tgl_beli, nama_barang, quantity, total_harga).";
                continue;
            }
            $seen[$key] = true;

            $rows[] = [
                'tgl_beli'       => Carbon::parse($row['tgl_beli'])->format('Y-m-d'),
                'nama_barang'    => $row['nama_barang'],
                'quantity'       => $row['quantity'],
                'harga_satuan'   => $row['harga_satuan'],
                'harga_ongkir'   => $row['harga_ongkir'] ?? 0,
                'harga_potongan' => $row['harga_potongan'] ?? 0,
                'total_harga'    => $total,
            ];
        }

        if (!empty($errors)) {
            return back()->with('error','Upload Gagal:<br>'.implode('<br>', $errors));
        }
        if (empty($rows)) {
            return back()->with('error','Tidak ada baris data valid yang diproses.');
        }

        // Cek duplikat vs DB (massal)
        $dupDbErrors = [];
        $chunks = array_chunk($rows, 300);
        foreach ($chunks as $chunk) {
            $query = TokopediaBarangMasuk::query();
            foreach ($chunk as $r) {
                $query->orWhere(function($x) use ($r){
                    $x->where('tgl_beli',$r['tgl_beli'])
                      ->where('nama_barang',$r['nama_barang'])
                      ->where('quantity',$r['quantity'])
                      ->where('total_harga',$r['total_harga']);
                });
            }
            $dups = $query->get(['tgl_beli','nama_barang','quantity','total_harga']);
            foreach ($dups as $d) {
                $dupDbErrors[] = "Sudah ada di DB: {$d->tgl_beli} | {$d->nama_barang} | {$d->quantity} | {$d->total_harga}";
            }
        }
        if (!empty($dupDbErrors)) {
            return back()->with('error', 'Upload Gagal karena duplikat vs DB:<br>'.implode('<br>', $dupDbErrors));
        }

        // Insert all-or-nothing
        DB::transaction(function() use ($rows){
            $now = now();
            foreach ($rows as &$r) { $r['created_at'] = $now; $r['updated_at'] = $now; }
            TokopediaBarangMasuk::insert($rows);
        });

        return redirect()->route('tokopedia-barang-masuk.index')->with('success','Batch data berhasil diunggah.');
    }

    /** Helpers */
    private function calcTotal(int $qty, float $hargaSatuan, float $ongkir=0, float $potongan=0): float
    {
        return $qty * $hargaSatuan - $ongkir - $potongan;
    }

    private function splitRow(string $line): array
    {
        return str_contains($line, "\t") ? explode("\t",$line) : array_map('trim', explode(',',$line));
    }

    private function normalizeHeader(array $hdr): array
    {
        return array_map(fn($h)=>strtolower(trim($h)), $hdr);
    }

    private function validateRow(array $in): array
    {
        // Normalisasi nama_barang dulu (trim + upper)
        $in['nama_barang'] = isset($in['nama_barang']) ? strtoupper(trim((string)$in['nama_barang'])) : null;

        $data = validator($in, [
            'tgl_beli'       => ['required','date','before_or_equal:today'],
            'nama_barang'    => ['required','max:255','regex:/^(?!\s)(.*\S)?$/'],
            'quantity'       => ['required','integer','min:1'],
            'harga_satuan'   => ['required','numeric','min:0'],
            'harga_ongkir'   => ['nullable','numeric','min:0'],
            'harga_potongan' => ['nullable','numeric','min:0'],
        ],[
            'nama_barang.regex' => 'nama_barang tidak boleh diawali/diakhiri spasi.',
            'tgl_beli.before_or_equal' => 'tgl_beli tidak boleh lebih dari hari ini.',
        ])->validate();

        // Format tanggal Y-m-d
        $data['tgl_beli'] = Carbon::parse($data['tgl_beli'])->format('Y-m-d');
        // Default nol jika null
        $data['harga_ongkir']   = $data['harga_ongkir'] ?? 0;
        $data['harga_potongan'] = $data['harga_potongan'] ?? 0;

        return $data;
    }
}

